import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { Prisma } from "@/generated/prisma";
import { db } from "@/lib/db";
import { requireAdmin, recordActivity } from "@/lib/admin";
import { dateOnly } from "@/lib/format";

export const metadata: Metadata = { title: "Utilisateurs" };
export const dynamic = "force-dynamic";

const PAGE_SIZE = 25;

const ROLE_LABELS: Record<string, string> = {
  PATIENT: "Patient",
  PROFESSIONAL: "Professionnel",
  SECRETARY: "Secrétaire",
  ADMIN: "Administrateur",
};

async function toggleActive(formData: FormData) {
  "use server";
  const admin = await requireAdmin();
  const id = String(formData.get("id") ?? "");
  const back = String(formData.get("back") ?? "/admin/utilisateurs");

  const user = await db.user.findUnique({
    where: { id },
    select: { id: true, isActive: true, firstName: true, lastName: true, role: true },
  });
  if (!user) redirect(back);

  // An administrator must not be able to lock themselves out, and the last
  // active administrator must not be deactivated at all: nobody would be left
  // who could undo it.
  if (user.id === admin.id) redirect(`${back}${back.includes("?") ? "&" : "?"}erreur=soi`);
  if (user.role === "ADMIN" && user.isActive) {
    const remaining = await db.user.count({
      where: { role: "ADMIN", isActive: true, id: { not: user.id } },
    });
    if (remaining === 0) {
      redirect(`${back}${back.includes("?") ? "&" : "?"}erreur=dernier-admin`);
    }
  }

  await db.user.update({
    where: { id: user.id },
    data: { isActive: !user.isActive },
  });
  await recordActivity({
    actorId: admin.id,
    action: user.isActive ? "user.deactivated" : "user.activated",
    summary: `${user.firstName} ${user.lastName} — compte ${user.isActive ? "désactivé" : "réactivé"}`,
    targetType: "user",
    targetId: user.id,
  });

  revalidatePath("/admin/utilisateurs");
  redirect(back);
}

export default async function AdminUsersPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  await requireAdmin();
  const raw = await searchParams;
  const one = (k: string) => {
    const v = raw[k];
    return Array.isArray(v) ? v[0] : v;
  };

  const q = one("q") ?? "";
  const role = one("role") ?? "";
  const page = Math.max(1, Number(one("page")) || 1);
  const erreur = one("erreur");

  const where: Prisma.UserWhereInput = {};
  if (role) where.role = role as Prisma.UserWhereInput["role"];
  if (q) {
    where.OR = [
      { firstName: { contains: q } },
      { lastName: { contains: q } },
      { email: { contains: q } },
    ];
  }

  const [users, total] = await Promise.all([
    db.user.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        email: true,
        firstName: true,
        lastName: true,
        phone: true,
        role: true,
        isActive: true,
        createdAt: true,
        _count: { select: { appointments: true } },
      },
    }),
    db.user.count({ where }),
  ]);

  const pageCount = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const query = new URLSearchParams();
  for (const [k, v] of Object.entries(raw)) {
    if (v === undefined || k === "erreur") continue;
    query.set(k, Array.isArray(v) ? v[0] : v);
  }
  const back = `/admin/utilisateurs?${query}`;

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Utilisateurs
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">
        {total} compte{total === 1 ? "" : "s"} correspondant aux filtres.
      </p>

      {erreur === "soi" && (
        <p role="alert" className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900">
          Vous ne pouvez pas désactiver votre propre compte.
        </p>
      )}
      {erreur === "dernier-admin" && (
        <p role="alert" className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900">
          Impossible&nbsp;: c&apos;est le dernier compte administrateur actif.
          Personne ne pourrait annuler l&apos;opération.
        </p>
      )}

      <form method="get" className="mt-6 flex flex-wrap items-end gap-3 border border-enamel-300 bg-enamel-50 p-4">
        <div className="min-w-[14rem] flex-1">
          <label htmlFor="q" className="mb-1 block font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
            Recherche
          </label>
          <input id="q" name="q" defaultValue={q} placeholder="Nom ou e-mail"
            className="w-full min-h-11 border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900" />
        </div>
        <div>
          <label htmlFor="role" className="mb-1 block font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
            Rôle
          </label>
          <select id="role" name="role" defaultValue={role}
            className="min-h-11 border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900">
            <option value="">Tous</option>
            {Object.entries(ROLE_LABELS).map(([value, label]) => (
              <option key={value} value={value}>{label}</option>
            ))}
          </select>
        </div>
        <button type="submit" className="min-h-11 bg-rod-500 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-rod-950 uppercase hover:bg-rod-400">
          Filtrer
        </button>
        <Link href="/admin/utilisateurs" className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-ink-500 uppercase underline underline-offset-4">
          Réinitialiser
        </Link>
      </form>

      <div className="mt-6 overflow-x-auto border border-enamel-300">
        <table className="w-full min-w-[48rem] border-collapse bg-enamel-50 text-left">
          <thead>
            <tr className="border-b border-enamel-300">
              {["Nom", "E-mail", "Rôle", "Rendez-vous", "Inscrit le", "État", "Action"].map((h) => (
                <th key={h} className="px-3 py-2.5 font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody className="ruled">
            {users.map((user) => (
              <tr key={user.id} className="align-top">
                <td className="px-3 py-3 text-sm text-ink-900">
                  {user.firstName} {user.lastName}
                  {user.phone && <span className="block text-ink-400">{user.phone}</span>}
                </td>
                <td className="px-3 py-3 text-sm break-all text-ink-600">{user.email}</td>
                <td className="px-3 py-3 text-sm text-ink-600">{ROLE_LABELS[user.role] ?? user.role}</td>
                <td className="px-3 py-3 text-sm tabular-nums text-ink-600">{user._count.appointments}</td>
                <td className="px-3 py-3 text-sm tabular-nums text-ink-600">{dateOnly.format(user.createdAt)}</td>
                <td className="px-3 py-3">
                  <span className={`border px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${
                    user.isActive
                      ? "border-rod-600/40 bg-rod-100 text-rod-800"
                      : "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose"
                  }`}>
                    {user.isActive ? "Actif" : "Désactivé"}
                  </span>
                </td>
                <td className="px-3 py-3">
                  <form action={toggleActive}>
                    <input type="hidden" name="id" value={user.id} />
                    <input type="hidden" name="back" value={back} />
                    <button type="submit" className={`min-h-11 w-full border px-2 py-1.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${
                      user.isActive
                        ? "border-carbon-rose/60 text-carbon-rose hover:bg-carbon-rose-soft"
                        : "border-rod-700 text-rod-700 hover:bg-rod-100"
                    }`}>
                      {user.isActive ? "Désactiver" : "Réactiver"}
                    </button>
                  </form>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {pageCount > 1 && (
        <nav aria-label="Pagination" className="mt-5 flex items-center justify-between gap-4">
          {page > 1 ? <PageLink query={query} page={page - 1} label="← Précédent" /> : <span />}
          <span className="font-display text-sm tabular-nums text-ink-500">Page {page} sur {pageCount}</span>
          {page < pageCount ? <PageLink query={query} page={page + 1} label="Suivant →" /> : <span />}
        </nav>
      )}
    </main>
  );
}

function PageLink({ query, page, label }: { query: URLSearchParams; page: number; label: string }) {
  const next = new URLSearchParams(query);
  next.set("page", String(page));
  return (
    <Link href={`/admin/utilisateurs?${next}`} className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:underline">
      {label}
    </Link>
  );
}
