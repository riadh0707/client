import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { Prisma } from "@/generated/prisma";
import { db } from "@/lib/db";
import {
  requireAdmin,
  recordActivity,
  PARTNER_STATUS_LABELS,
  VERIFICATION_LABELS,
} from "@/lib/admin";

export const metadata: Metadata = { title: "Partenaires" };
export const dynamic = "force-dynamic";

const PAGE_SIZE = 20;

async function moderate(formData: FormData) {
  "use server";

  const admin = await requireAdmin();
  const id = String(formData.get("id") ?? "");
  const action = String(formData.get("action") ?? "");
  const back = String(formData.get("back") ?? "/admin/partenaires");

  const partner = await db.partner.findUnique({
    where: { id },
    select: { id: true, displayName: true, status: true },
  });
  if (!partner) redirect(back);

  const changes: Record<string, Prisma.PartnerUpdateInput> = {
    verify: { verificationStatus: "VERIFIED", verifiedAt: new Date() },
    reject: { verificationStatus: "REJECTED", verifiedAt: null },
    activate: { status: "ACTIVE" },
    suspend: { status: "SUSPENDED" },
  };
  const data = changes[action];
  if (!data) redirect(back);

  await db.partner.update({ where: { id: partner.id }, data });

  const summaries: Record<string, string> = {
    verify: `${partner.displayName} — profil vérifié`,
    reject: `${partner.displayName} — vérification rejetée`,
    activate: `${partner.displayName} — activé`,
    suspend: `${partner.displayName} — suspendu`,
  };
  await recordActivity({
    actorId: admin.id,
    action: `partner.${action}`,
    summary: summaries[action],
    targetType: "partner",
    targetId: partner.id,
  });

  // Verification outcomes are the partner's business, so the owners hear about
  // them rather than discovering the change on their own profile.
  if (action === "activate" && partner.status === "PENDING") {
    const members = await db.partnerMember.findMany({
      where: { partnerId: partner.id },
      select: { userId: true },
    });
    if (members.length > 0) {
      await db.notification.createMany({
        data: members.map((member) => ({
          userId: member.userId,
          kind: "PARTNER_REGISTERED" as const,
          title: "Votre fiche est en ligne",
          body: `${partner.displayName} apparaît désormais dans les recherches.`,
          href: "/pro",
        })),
      });
    }
  }

  if (action === "verify" || action === "reject") {
    const members = await db.partnerMember.findMany({
      where: { partnerId: partner.id },
      select: { userId: true },
    });
    if (members.length > 0) {
      await db.notification.createMany({
        data: members.map((member) => ({
          userId: member.userId,
          kind:
            action === "verify"
              ? ("VERIFICATION_APPROVED" as const)
              : ("VERIFICATION_REJECTED" as const),
          title:
            action === "verify"
              ? "Profil vérifié"
              : "Vérification refusée",
          body:
            action === "verify"
              ? "Votre profil affiche désormais le badge « vérifié »."
              : "Votre demande de vérification a été refusée. Contactez l'administration.",
          href: "/pro",
        })),
      });
    }
  }

  revalidatePath("/admin/partenaires");
  redirect(back);
}

export default async function AdminPartnersPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  await requireAdmin();
  const raw = await searchParams;
  const one = (key: string) => {
    const value = raw[key];
    return Array.isArray(value) ? value[0] : value;
  };

  const q = one("q") ?? "";
  const statut = one("statut") ?? "";
  const verification = one("verification") ?? "";
  const categorie = one("categorie") ?? "";
  const wilayaCode = Number(one("wilaya"));
  const page = Math.max(1, Number(one("page")) || 1);

  const where: Prisma.PartnerWhereInput = {};
  if (q) where.displayName = { contains: q };
  if (statut) where.status = statut as Prisma.PartnerWhereInput["status"];
  if (verification)
    where.verificationStatus =
      verification as Prisma.PartnerWhereInput["verificationStatus"];
  if (categorie) where.category = { slug: categorie };
  if (Number.isFinite(wilayaCode) && wilayaCode > 0) where.wilayaCode = wilayaCode;

  const [partners, total, categories, wilayas] = await Promise.all([
    db.partner.findMany({
      where,
      orderBy: [{ status: "asc" }, { displayName: "asc" }],
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        slug: true,
        displayName: true,
        status: true,
        verificationStatus: true,
        category: { select: { name: true } },
        wilaya: { select: { code: true, name: true } },
        commune: { select: { name: true } },
        subscriptions: {
          orderBy: { createdAt: "desc" },
          take: 1,
          select: { status: true, plan: { select: { name: true } } },
        },
      },
    }),
    db.partner.count({ where }),
    db.partnerCategory.findMany({
      orderBy: { sortOrder: "asc" },
      select: { slug: true, name: true },
    }),
    db.wilaya.findMany({
      orderBy: { code: "asc" },
      select: { code: true, name: true },
    }),
  ]);

  const pageCount = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const currentQuery = new URLSearchParams();
  for (const [key, value] of Object.entries(raw)) {
    if (value === undefined) continue;
    currentQuery.set(key, Array.isArray(value) ? value[0] : value);
  }
  // `back` must not carry the confirm state, or cancelling would re-open it.
  const backQuery = new URLSearchParams(currentQuery);
  backQuery.delete("confirmer");
  backQuery.delete("acte");
  const back = `/admin/partenaires?${backQuery}`;

  const confirmerId = one("confirmer");
  const confirmerAction = one("acte");
  const confirming =
    confirmerId && (confirmerAction === "suspend" || confirmerAction === "reject")
      ? { id: confirmerId, action: confirmerAction }
      : null;

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Partenaires
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">
        {total} partenaire{total === 1 ? "" : "s"} correspondant aux filtres.
      </p>

      <form
        method="get"
        className="mt-6 grid gap-3 border border-enamel-300 bg-enamel-50 p-4 sm:grid-cols-2 lg:grid-cols-5"
      >
        <Filter label="Recherche" htmlFor="q">
          <input
            id="q"
            name="q"
            defaultValue={q}
            placeholder="Nom du partenaire"
            className="w-full min-h-11 border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900"
          />
        </Filter>
        <Filter label="Statut" htmlFor="statut">
          <Select id="statut" name="statut" value={statut} options={[
            { value: "PENDING", label: "En attente" },
            { value: "ACTIVE", label: "Actif" },
            { value: "SUSPENDED", label: "Suspendu" },
          ]} empty="Tous" />
        </Filter>
        <Filter label="Vérification" htmlFor="verification">
          <Select id="verification" name="verification" value={verification} options={[
            { value: "UNVERIFIED", label: "Non vérifié" },
            { value: "PENDING", label: "Demandée" },
            { value: "VERIFIED", label: "Vérifié" },
            { value: "REJECTED", label: "Rejeté" },
          ]} empty="Toutes" />
        </Filter>
        <Filter label="Type" htmlFor="categorie">
          <Select
            id="categorie"
            name="categorie"
            value={categorie}
            options={categories.map((c) => ({ value: c.slug, label: c.name }))}
            empty="Tous"
          />
        </Filter>
        <Filter label="Wilaya" htmlFor="wilaya">
          <Select
            id="wilaya"
            name="wilaya"
            value={Number.isFinite(wilayaCode) && wilayaCode > 0 ? String(wilayaCode) : ""}
            options={wilayas.map((w) => ({
              value: String(w.code),
              label: `${String(w.code).padStart(2, "0")} · ${w.name}`,
            }))}
            empty="Toutes"
          />
        </Filter>

        <div className="flex items-end gap-3 sm:col-span-2 lg:col-span-5">
          <button
            type="submit"
            className="min-h-11 bg-rod-500 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-rod-950 uppercase hover:bg-rod-400"
          >
            Filtrer
          </button>
          <Link
            href="/admin/partenaires"
            className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-ink-500 uppercase underline underline-offset-4"
          >
            Réinitialiser
          </Link>
        </div>
      </form>

      {partners.length === 0 ? (
        <p className="mt-6 bg-enamel-50 px-5 py-12 text-center text-[15px] text-ink-600">
          Aucun partenaire ne correspond à ces filtres.
        </p>
      ) : (
        <div className="mt-6 overflow-x-auto border border-enamel-300">
          <table className="w-full min-w-[52rem] border-collapse bg-enamel-50 text-left">
            <thead>
              <tr className="border-b border-enamel-300">
                <Th>Partenaire</Th>
                <Th>Type</Th>
                <Th>Localisation</Th>
                <Th>Statut</Th>
                <Th>Vérification</Th>
                <Th>Abonnement</Th>
                <Th>Actions</Th>
              </tr>
            </thead>
            <tbody className="ruled">
              {partners.map((partner) => {
                const status =
                  PARTNER_STATUS_LABELS[partner.status] ??
                  PARTNER_STATUS_LABELS.PENDING;
                const verif =
                  VERIFICATION_LABELS[partner.verificationStatus] ??
                  VERIFICATION_LABELS.UNVERIFIED;
                const subscription = partner.subscriptions[0];

                return (
                  <tr key={partner.id} className="align-top">
                    <Td>
                      <Link
                        href={`/partenaire/${partner.slug}`}
                        className="-my-3 flex min-h-11 items-center py-3 font-display font-bold text-ink-900 hover:text-rod-700"
                      >
                        {partner.displayName}
                      </Link>
                    </Td>
                    <Td className="text-ink-600">{partner.category.name}</Td>
                    <Td className="text-ink-600">
                      {partner.commune.name}
                      <span className="block text-ink-400">
                        {partner.wilaya.name} (
                        {String(partner.wilaya.code).padStart(2, "0")})
                      </span>
                    </Td>
                    <Td>
                      <Badge {...status} />
                    </Td>
                    <Td>
                      <Badge {...verif} />
                    </Td>
                    <Td className="text-ink-600">
                      {subscription
                        ? `${subscription.plan.name} · ${subscription.status === "ACTIVE" ? "actif" : "expiré"}`
                        : "—"}
                    </Td>
                    <Td>
                      {confirming?.id === partner.id ? (
                        <ConfirmAction
                          id={partner.id}
                          action={confirming.action}
                          partnerName={partner.displayName}
                          partnerStatus={partner.status}
                          back={back}
                        />
                      ) : (
                        <div className="flex flex-col gap-1.5">
                          {partner.verificationStatus !== "VERIFIED" && (
                            <Action id={partner.id} action="verify" label="Vérifier" back={back} />
                          )}
                          {partner.verificationStatus === "PENDING" && (
                            <Action
                              id={partner.id}
                              action="reject"
                              label="Rejeter"
                              back={back}
                              tone="reject"
                              confirmNeeded
                              confirmHref={confirmHref(currentQuery, partner.id, "reject")}
                            />
                          )}
                          {/* A registration that nobody has published yet is
                              approved, not "reactivated"; and refusing one is
                              not the same act as suspending a live profile,
                              even though both land on SUSPENDED. The row said
                              "Suspendre" over a PENDING file and offered no way
                              at all to put it online. */}
                          {partner.status === "PENDING" && (
                            <Action id={partner.id} action="activate" label="Publier" back={back} />
                          )}
                          {partner.status === "SUSPENDED" && (
                            <Action id={partner.id} action="activate" label="Réactiver" back={back} />
                          )}
                          {partner.status !== "SUSPENDED" && (
                            <Action
                              id={partner.id}
                              action="suspend"
                              label={partner.status === "PENDING" ? "Refuser" : "Suspendre"}
                              back={back}
                              tone="reject"
                              confirmNeeded
                              confirmHref={confirmHref(currentQuery, partner.id, "suspend")}
                            />
                          )}
                        </div>
                      )}
                    </Td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {pageCount > 1 && (
        <nav
          aria-label="Pagination"
          className="mt-5 flex items-center justify-between gap-4"
        >
          {page > 1 ? (
            <PageLink query={currentQuery} page={page - 1} label="← Précédent" />
          ) : (
            <span />
          )}
          <span className="font-display text-sm tabular-nums text-ink-500">
            Page {page} sur {pageCount}
          </span>
          {page < pageCount ? (
            <PageLink query={currentQuery} page={page + 1} label="Suivant →" />
          ) : (
            <span />
          )}
        </nav>
      )}
    </main>
  );
}

function confirmHref(
  query: URLSearchParams,
  id: string,
  action: string,
) {
  const next = new URLSearchParams(query);
  next.set("confirmer", id);
  next.set("acte", action);
  return `/admin/partenaires?${next}`;
}

function PageLink({
  query,
  page,
  label,
}: {
  query: URLSearchParams;
  page: number;
  label: string;
}) {
  const next = new URLSearchParams(query);
  next.set("page", String(page));
  return (
    <Link
      href={`/admin/partenaires?${next}`}
      className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:underline"
    >
      {label}
    </Link>
  );
}

/**
 * Suspending a partner pulls a paying listing off the public site, and
 * rejecting a verification is a decision the partner is told about. Both get a
 * confirm step naming the partner; verifying and reactivating do not, because
 * they are additive and trivially reversible.
 *
 * The confirm lives in a query parameter rather than a dialog, so it works
 * without JavaScript and survives a reload.
 */
function Action({
  id,
  action,
  label,
  back,
  tone = "accept",
  confirmNeeded = false,
  confirmHref,
}: {
  id: string;
  action: string;
  label: string;
  back: string;
  tone?: "accept" | "reject";
  confirmNeeded?: boolean;
  confirmHref?: string;
}) {
  const className = `inline-flex min-h-11 w-full items-center justify-center border px-2 py-1.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${
    tone === "reject"
      ? "border-carbon-rose/60 text-carbon-rose hover:bg-carbon-rose-soft"
      : "border-rod-700 text-rod-700 hover:bg-rod-100"
  }`;

  if (confirmNeeded && confirmHref) {
    return (
      <Link href={confirmHref} scroll={false} className={className}>
        {label}
      </Link>
    );
  }

  return (
    <form action={moderate}>
      <input type="hidden" name="id" value={id} />
      <input type="hidden" name="action" value={action} />
      <input type="hidden" name="back" value={back} />
      <button type="submit" className={className}>
        {label}
      </button>
    </form>
  );
}

function ConfirmAction({
  id,
  action,
  partnerName,
  partnerStatus,
  back,
}: {
  id: string;
  action: string;
  partnerName: string;
  partnerStatus: string;
  back: string;
}) {
  const prompts: Record<string, { question: string; consequence: string; confirm: string }> = {
    suspend:
      partnerStatus === "PENDING"
        ? {
            question: `Refuser l'inscription de ${partnerName} ?`,
            consequence:
              "La fiche ne sera pas publiée. Son responsable garde l'accès à son espace et peut la corriger.",
            confirm: "Oui, refuser",
          }
        : {
            question: `Suspendre ${partnerName} ?`,
            consequence:
              "Sa fiche disparaît de la recherche et son lien direct renvoie une page introuvable.",
            confirm: "Oui, suspendre",
          },
    reject: {
      question: `Rejeter la vérification de ${partnerName} ?`,
      consequence: "Le partenaire en est notifié.",
      confirm: "Oui, rejeter",
    },
  };
  const prompt = prompts[action] ?? prompts.suspend;

  return (
    <div className="border border-carbon-rose/50 bg-carbon-rose-soft p-2.5">
      <p className="text-xs leading-snug text-ink-900">{prompt.question}</p>
      <p className="mt-1 text-xs leading-snug text-ink-600">
        {prompt.consequence}
      </p>
      <div className="mt-2 flex flex-col gap-1.5">
        <form action={moderate}>
          <input type="hidden" name="id" value={id} />
          <input type="hidden" name="action" value={action} />
          <input type="hidden" name="back" value={back} />
          <button
            type="submit"
            className="min-h-11 w-full border border-carbon-rose bg-carbon-rose px-2 py-1.5 font-display text-[11px] font-bold tracking-[0.08em] text-enamel-50 uppercase"
          >
            {prompt.confirm}
          </button>
        </form>
        <Link
          href={back}
          scroll={false}
          className="inline-flex min-h-11 w-full items-center justify-center border border-ink-300 bg-enamel-50 px-2 py-1.5 font-display text-[11px] font-bold tracking-[0.08em] text-ink-900 uppercase"
        >
          Annuler
        </Link>
      </div>
    </div>
  );
}

function Badge({ label, className }: { label: string; className: string }) {
  return (
    <span
      className={`inline-block border px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${className}`}
    >
      {label}
    </span>
  );
}

function Th({ children }: { children: React.ReactNode }) {
  return (
    <th className="px-3 py-2.5 font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
      {children}
    </th>
  );
}

function Td({
  children,
  className = "",
}: {
  children: React.ReactNode;
  className?: string;
}) {
  return <td className={`px-3 py-3 text-sm ${className}`}>{children}</td>;
}

function Filter({
  label,
  htmlFor,
  children,
}: {
  label: string;
  htmlFor: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label
        htmlFor={htmlFor}
        className="mb-1 block font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase"
      >
        {label}
      </label>
      {children}
    </div>
  );
}

function Select({
  id,
  name,
  value,
  options,
  empty,
}: {
  id: string;
  name: string;
  value: string;
  options: { value: string; label: string }[];
  empty: string;
}) {
  return (
    <select
      id={id}
      name={name}
      defaultValue={value}
      className="w-full min-h-11 border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900"
    >
      <option value="">{empty}</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );
}
