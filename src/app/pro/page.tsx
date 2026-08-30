import type { Metadata } from "next";
import Link from "next/link";
import { AppointmentRow } from "@/components/appointment-row";
import { db } from "@/lib/db";
import { requirePartnerContext } from "@/lib/pro";
import { dateOnly } from "@/lib/format";
import { StatTile } from "@/components/stat-tile";

export const metadata: Metadata = { title: "Espace professionnel" };
export const dynamic = "force-dynamic";

export default async function ProDashboard({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const query = await searchParams;
  const { partner, permissions } = await requirePartnerContext("/pro");

  if (!partner) {
    return (
      <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-12 sm:px-8">
        <h1 className="font-display text-2xl font-bold text-ink-900">
          Aucun établissement rattaché
        </h1>
        <p className="mt-3 text-[15px] leading-relaxed text-ink-600">
          Votre compte n&apos;est rattaché à aucune fiche. Inscrivez votre
          structure pour en créer une, ou demandez à son responsable de vous
          ajouter comme secrétaire.
        </p>
        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <Link
            href="/inscription/professionnel"
            className="inline-flex min-h-11 items-center justify-center bg-rod-500 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-rod-950 uppercase hover:bg-rod-400"
          >
            Inscrire ma structure
          </Link>
          <Link
            href="/"
            className="inline-flex min-h-11 items-center justify-center border border-rod-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:bg-rod-100"
          >
            Retour à l&apos;accueil
          </Link>
        </div>
      </main>
    );
  }

  const now = new Date();
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

  const [pending, upcoming, monthCount, completedCount, subscription] =
    await Promise.all([
      db.appointment.count({
        where: { partnerId: partner.id, status: "PENDING", startAt: { gte: now } },
      }),
      db.appointment.findMany({
        where: {
          partnerId: partner.id,
          startAt: { gte: now },
          status: { in: ["PENDING", "CONFIRMED"] },
        },
        orderBy: { startAt: "asc" },
        take: 5,
        select: {
          id: true,
          startAt: true,
          status: true,
          reason: true,
          serviceName: true,
          partner: {
            select: {
              slug: true,
              displayName: true,
              commune: { select: { name: true } },
            },
          },
          patient: {
            select: { firstName: true, lastName: true, phone: true },
          },
        },
      }),
      db.appointment.count({
        where: { partnerId: partner.id, startAt: { gte: monthStart } },
      }),
      db.appointment.count({
        where: { partnerId: partner.id, status: "COMPLETED" },
      }),
      Promise.resolve(partner.subscriptions[0] ?? null),
    ]);

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
        <div>
          <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
            {partner.displayName}
          </h1>
          <p className="mt-1 text-[15px] text-ink-600">
            {partner.specialty?.name ?? partner.category.name} ·{" "}
            {partner.commune.name}, {partner.wilaya.name}
          </p>
        </div>
        <Link
          href={`/partenaire/${partner.slug}`}
          className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:underline"
        >
          Voir ma fiche publique
        </Link>
      </div>

      {query.erreur === "droits" && (
        <p
          role="alert"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
        >
          Vous n&apos;avez pas les droits nécessaires pour cette action.
        </p>
      )}

      {query.bienvenue && (
        <p
          role="status"
          className="mt-5 border border-rod-600/40 bg-rod-100 px-4 py-3 text-[15px] leading-relaxed text-ink-900"
        >
          Bienvenue sur DOCTORY. Votre fiche est enregistrée et attend la
          validation de notre équipe. Vous pouvez dès maintenant renseigner vos
          horaires et vos prestations&nbsp;: elles seront en place le jour où
          elle passe en ligne.
        </p>
      )}

      {partner.status === "PENDING" && (
        <p
          role="status"
          className="mt-5 border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3 text-[15px] leading-relaxed text-ink-900"
        >
          <strong className="font-display">
            Fiche en attente de validation.
          </strong>{" "}
          Elle n&apos;apparaît pas encore dans les recherches et ne reçoit pas
          encore de demandes de rendez-vous. Notre équipe la relit avant
          publication.
        </p>
      )}

      {partner.status === "SUSPENDED" && (
        <p
          role="alert"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] leading-relaxed text-ink-900"
        >
          <strong className="font-display">Fiche suspendue.</strong> Elle
          n&apos;est plus visible des patients. Contactez l&apos;administration
          de DOCTORY pour en connaître la raison.
        </p>
      )}

      {partner.status === "ACTIVE" && partner.verificationStatus !== "VERIFIED" && (
        <p className="mt-5 border border-carbon-amber/50 bg-carbon-amber-soft px-4 py-3 text-[15px] text-ink-900">
          Votre profil n&apos;est pas encore vérifié. Les profils vérifiés
          apparaissent avec un badge et inspirent davantage confiance aux
          patients.
        </p>
      )}

      <section className="mt-8 grid gap-px bg-ink-900/10 sm:grid-cols-2 lg:grid-cols-4">
        <StatTile
          label="Demandes en attente"
          value={pending}
          tone={pending > 0 ? "attention" : "neutral"}
        />
        <StatTile label="Rendez-vous ce mois" value={monthCount} />
        <StatTile label="Consultations terminées" value={completedCount} />
        <StatTile
          label="Abonnement"
          value={subscription ? subscription.plan.name : "Aucun"}
          hint={
            subscription
              ? `${subscription.status === "ACTIVE" ? "Actif jusqu'au" : "Expiré le"} ${dateOnly.format(subscription.expiresAt)}`
              : "Aucun abonnement enregistré"
          }
        />
      </section>

      <section className="mt-10">
        <div className="flex items-baseline justify-between gap-4">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Prochains rendez-vous
          </h2>
          <Link
            href="/pro/agenda"
            className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:underline"
          >
            Ouvrir l&apos;agenda
          </Link>
        </div>

        {upcoming.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-10 text-center text-[15px] text-ink-600">
            Aucun rendez-vous à venir.
          </p>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {upcoming.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="partner"
              />
            ))}
          </div>
        )}
      </section>

      {!permissions.canManageProfile && (
        <p className="mt-8 text-sm leading-relaxed text-ink-500">
          Vous êtes connecté·e comme secrétaire de {partner.displayName}&nbsp;:
          vous gérez l&apos;agenda et les rendez-vous. La fiche et
          l&apos;abonnement relèvent du praticien, qui doit s&apos;y connecter
          lui-même.
        </p>
      )}
    </main>
  );
}
