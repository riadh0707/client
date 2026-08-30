import type { Metadata } from "next";
import Link from "next/link";
import { db } from "@/lib/db";
import {
  requireAdmin,
  PARTNER_STATUS_LABELS,
  VERIFICATION_LABELS,
} from "@/lib/admin";
import { StatTile } from "@/components/stat-tile";
import { StatusBadge, formatAppointmentDate } from "@/components/appointment-row";
import { dateTimeShort } from "@/lib/format";

export const metadata: Metadata = { title: "Administration" };
export const dynamic = "force-dynamic";

export default async function AdminDashboard() {
  await requireAdmin();
  const now = new Date();
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

  const [
    partnerTotal,
    partnersPending,
    partnersSuspended,
    verificationPending,
    patients,
    appointmentsMonth,
    activeSubscriptions,
    expiringSubscriptions,
    moderationQueue,
    recentActivity,
  ] = await Promise.all([
    db.partner.count(),
    db.partner.count({ where: { status: "PENDING" } }),
    db.partner.count({ where: { status: "SUSPENDED" } }),
    db.partner.count({ where: { verificationStatus: "PENDING" } }),
    db.user.count({ where: { role: "PATIENT" } }),
    db.appointment.count({ where: { createdAt: { gte: monthStart } } }),
    db.subscription.count({ where: { status: "ACTIVE" } }),
    db.subscription.count({
      where: {
        status: "ACTIVE",
        expiresAt: { lte: new Date(now.getTime() + 30 * 86400000) },
      },
    }),
    db.partner.findMany({
      where: {
        OR: [{ status: "PENDING" }, { verificationStatus: "PENDING" }],
      },
      orderBy: { createdAt: "desc" },
      take: 8,
      select: {
        id: true,
        slug: true,
        displayName: true,
        status: true,
        verificationStatus: true,
        category: { select: { name: true } },
        wilaya: { select: { name: true } },
      },
    }),
    db.activityLog.findMany({
      orderBy: { createdAt: "desc" },
      take: 8,
      select: { id: true, summary: true, action: true, createdAt: true },
    }),
  ]);

  const upcoming = await db.appointment.findMany({
    where: { startAt: { gte: now } },
    orderBy: { startAt: "asc" },
    take: 5,
    select: {
      id: true,
      startAt: true,
      status: true,
      partner: { select: { displayName: true } },
      patient: { select: { firstName: true, lastName: true } },
    },
  });

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Tableau de bord
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">
        Vue d&apos;ensemble de la plateforme.
      </p>

      <section className="mt-8 grid gap-px bg-ink-900/10 sm:grid-cols-2 lg:grid-cols-4">
        <StatTile label="Partenaires" value={partnerTotal} />
        <StatTile label="Patients inscrits" value={patients} />
        <StatTile label="Rendez-vous ce mois" value={appointmentsMonth} />
        <StatTile
          label="Abonnements actifs"
          value={activeSubscriptions}
          hint={
            expiringSubscriptions > 0
              ? `${expiringSubscriptions} expirent sous 30 jours`
              : undefined
          }
        />
      </section>

      <section className="mt-px grid gap-px bg-ink-900/10 sm:grid-cols-3">
        <StatTile
          label="En attente de validation"
          value={partnersPending}
          tone={partnersPending > 0 ? "attention" : "neutral"}
        />
        <StatTile
          label="Vérifications demandées"
          value={verificationPending}
          tone={verificationPending > 0 ? "attention" : "neutral"}
        />
        <StatTile label="Comptes suspendus" value={partnersSuspended} />
      </section>

      <section className="mt-10">
        <div className="flex items-baseline justify-between gap-4">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            File de modération
          </h2>
          <Link
            href="/admin/partenaires?statut=PENDING"
            className="font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:underline"
          >
            Gérer les partenaires
          </Link>
        </div>

        {moderationQueue.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Rien à traiter. Aucun partenaire en attente de validation ou de
            vérification.
          </p>
        ) : (
          <ul className="ruled mt-3 border-y border-enamel-300 bg-enamel-50">
            {moderationQueue.map((partner) => (
              <li
                key={partner.id}
                className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5 px-4 py-3"
              >
                <div className="min-w-0">
                  <Link
                    href={`/partenaire/${partner.slug}`}
                    className="font-display text-[15px] font-bold text-ink-900 hover:text-cross-700"
                  >
                    {partner.displayName}
                  </Link>
                  <span className="block text-sm text-ink-500">
                    {partner.category.name} · {partner.wilaya.name}
                  </span>
                </div>
                {/* The badge names why the row is queued. Showing the account
                    status instead labelled a partner awaiting verification
                    "ACTIF", which reads as though it needed nothing. */}
                {(() => {
                  const reason =
                    partner.status === "PENDING"
                      ? PARTNER_STATUS_LABELS.PENDING
                      : VERIFICATION_LABELS.PENDING;
                  return (
                    <span
                      className={`shrink-0 border px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] uppercase ${reason.className}`}
                    >
                      {reason.label}
                    </span>
                  );
                })()}
              </li>
            ))}
          </ul>
        )}
      </section>

      <div className="mt-10 grid gap-8 lg:grid-cols-2">
        <section>
          <div className="flex items-baseline justify-between gap-4">
            <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
              Prochains rendez-vous
            </h2>
            <Link
              href="/admin/rendez-vous"
              className="font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:underline"
            >
              Tout voir
            </Link>
          </div>
          <ul className="ruled mt-3 border-y border-enamel-300 bg-enamel-50">
            {upcoming.length === 0 ? (
              <li className="px-4 py-6 text-center text-[15px] text-ink-600">
                Aucun rendez-vous à venir.
              </li>
            ) : (
              upcoming.map((appointment) => (
                <li key={appointment.id} className="px-4 py-3">
                  <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span className="font-display text-[15px] font-bold text-ink-900">
                      {appointment.patient.firstName}{" "}
                      {appointment.patient.lastName}
                    </span>
                    <StatusBadge status={appointment.status} />
                  </div>
                  <span className="block text-sm text-ink-500">
                    {appointment.partner.displayName} ·{" "}
                    {formatAppointmentDate(appointment.startAt)}
                  </span>
                </li>
              ))
            )}
          </ul>
        </section>

        <section>
          <div className="flex items-baseline justify-between gap-4">
            <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
              Activité récente
            </h2>
            <Link
              href="/admin/activite"
              className="font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:underline"
            >
              Journal complet
            </Link>
          </div>
          <ul className="ruled mt-3 border-y border-enamel-300 bg-enamel-50">
            {recentActivity.length === 0 ? (
              <li className="px-4 py-6 text-center text-[15px] text-ink-600">
                Aucune action enregistrée.
              </li>
            ) : (
              recentActivity.map((entry) => (
                <li key={entry.id} className="px-4 py-3">
                  <span className="block text-[15px] text-ink-900">
                    {entry.summary}
                  </span>
                  <span className="block text-xs tabular-nums text-ink-400">
                    {dateTimeShort.format(entry.createdAt)} · {entry.action}
                  </span>
                </li>
              ))
            )}
          </ul>
        </section>
      </div>
    </main>
  );
}
