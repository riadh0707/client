import type { Metadata } from "next";
import { db } from "@/lib/db";
import { requireAdmin } from "@/lib/admin";
import { StatTile } from "@/components/stat-tile";
import { BarChart, LineChart, type Point } from "@/components/charts";
import { STATUS_STYLES } from "@/components/appointment-row";

export const metadata: Metadata = { title: "Statistiques" };
export const dynamic = "force-dynamic";

const MONTHS = [
  "janv.", "févr.", "mars", "avr.", "mai", "juin",
  "juil.", "août", "sept.", "oct.", "nov.", "déc.",
];

/** Cumulative count per month over the last `months` months. */
function cumulativeByMonth(dates: Date[], months: number): Point[] {
  const now = new Date();
  const buckets: Point[] = [];

  for (let offset = months - 1; offset >= 0; offset -= 1) {
    const end = new Date(now.getFullYear(), now.getMonth() - offset + 1, 1);
    const label = MONTHS[(now.getMonth() - offset + 12 * 2) % 12];
    buckets.push({
      label,
      value: dates.filter((date) => date < end).length,
    });
  }

  return buckets;
}

export default async function StatisticsPage() {
  await requireAdmin();

  const [
    partnerDates,
    patientDates,
    byCategory,
    byWilaya,
    byStatus,
    appointmentTotal,
    revenueRows,
  ] = await Promise.all([
    db.partner.findMany({ select: { createdAt: true } }),
    db.user.findMany({
      where: { role: "PATIENT" },
      select: { createdAt: true },
    }),
    db.partnerCategory.findMany({
      orderBy: { sortOrder: "asc" },
      select: { name: true, _count: { select: { partners: true } } },
    }),
    db.partner.groupBy({
      by: ["wilayaCode"],
      _count: { _all: true },
      orderBy: { _count: { wilayaCode: "desc" } },
      take: 10,
    }),
    db.appointment.groupBy({ by: ["status"], _count: { _all: true } }),
    db.appointment.count(),
    db.subscription.findMany({
      where: { status: "ACTIVE" },
      select: { plan: { select: { priceDzd: true, name: true } } },
    }),
  ]);

  const wilayaNames = new Map(
    (
      await db.wilaya.findMany({
        where: { code: { in: byWilaya.map((row) => row.wilayaCode) } },
        select: { code: true, name: true },
      })
    ).map((w) => [w.code, w.name]),
  );

  const partnerGrowth = cumulativeByMonth(
    partnerDates.map((row) => row.createdAt),
    12,
  );
  const patientGrowth = cumulativeByMonth(
    patientDates.map((row) => row.createdAt),
    12,
  );

  const categoryPoints: Point[] = byCategory.map((category) => ({
    label: category.name,
    value: category._count.partners,
  }));

  const wilayaPoints: Point[] = byWilaya.map((row) => ({
    label: wilayaNames.get(row.wilayaCode) ?? `Wilaya ${row.wilayaCode}`,
    value: row._count._all,
  }));

  const monthlyRevenue = revenueRows.reduce(
    (total, row) => total + row.plan.priceDzd,
    0,
  );

  const statusOrder = [
    "PENDING",
    "CONFIRMED",
    "COMPLETED",
    "CANCELLED",
    "NO_SHOW",
  ];
  const statusCounts = statusOrder.map((status) => ({
    status,
    count: byStatus.find((row) => row.status === status)?._count._all ?? 0,
  }));

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Statistiques
      </h1>
      <p className="mt-1 max-w-2xl text-[15px] leading-relaxed text-ink-600">
        Données de démonstration. Les revenus sont calculés à partir des tarifs
        indicatifs des formules, qui ne sont pas encore arrêtés.
      </p>

      <section className="mt-8 grid gap-px bg-ink-900/10 sm:grid-cols-2 lg:grid-cols-4">
        <StatTile label="Partenaires" value={partnerDates.length} />
        <StatTile label="Patients" value={patientDates.length} />
        <StatTile label="Rendez-vous" value={appointmentTotal} />
        <StatTile
          label="Revenu mensuel indicatif"
          value={`${monthlyRevenue.toLocaleString("fr-DZ")} DZD`}
          hint="Sur la base des abonnements actifs"
        />
      </section>

      <div className="mt-8 grid gap-px bg-ink-900/10 lg:grid-cols-2">
        <LineChart
          title="Croissance des partenaires (12 mois)"
          points={partnerGrowth}
          unit="Partenaires"
        />
        <LineChart
          title="Croissance des patients (12 mois)"
          points={patientGrowth}
          unit="Patients"
        />
      </div>

      <div className="mt-px grid gap-px bg-ink-900/10 lg:grid-cols-2">
        <BarChart
          title="Partenaires par type"
          points={categoryPoints}
          unit="Partenaires"
        />
        <BarChart
          title="Dix premières wilayas"
          points={wilayaPoints}
          unit="Partenaires"
        />
      </div>

      {/* Appointment status is identity across five reserved status colours, not
          a measure. A chart would spend the colour channel on what these badges
          already say, so the numbers are the chart. */}
      <section className="mt-8">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Rendez-vous par statut
        </h2>
        <ul className="ruled mt-3 border-y border-enamel-300 bg-enamel-50">
          {statusCounts.map(({ status, count }) => {
            const style = STATUS_STYLES[status] ?? STATUS_STYLES.PENDING;
            const share =
              appointmentTotal > 0
                ? Math.round((count / appointmentTotal) * 100)
                : 0;
            return (
              <li
                key={status}
                className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3"
              >
                <span
                  className={`border px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] uppercase ${style.className}`}
                >
                  {style.label}
                </span>
                <span className="text-sm tabular-nums text-ink-600">
                  {count} · {share}&nbsp;%
                </span>
              </li>
            );
          })}
        </ul>
      </section>
    </main>
  );
}
