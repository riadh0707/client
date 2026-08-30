import type { Metadata } from "next";
import Link from "next/link";
import { db } from "@/lib/db";
import { requireAdmin } from "@/lib/admin";
import { StatTile } from "@/components/stat-tile";
import { dateOnly, formatDzd } from "@/lib/format";

export const metadata: Metadata = { title: "Abonnements" };
export const dynamic = "force-dynamic";

const STATUS: Record<string, { label: string; className: string }> = {
  ACTIVE: { label: "Actif", className: "border-cross-600/40 bg-cross-100 text-cross-800" },
  EXPIRED: { label: "Expiré", className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose" },
  CANCELLED: { label: "Résilié", className: "border-ink-300/50 bg-enamel-200 text-ink-600" },
  PENDING_PAYMENT: { label: "Paiement attendu", className: "border-carbon-amber/50 bg-carbon-amber-soft text-carbon-amber" },
};

export default async function AdminSubscriptionsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  await requireAdmin();
  const raw = await searchParams;
  const statut = typeof raw.statut === "string" ? raw.statut : "";
  const now = new Date();
  const soon = new Date(now.getTime() + 30 * 86400000);

  const [active, expired, expiringSoon, plans, subscriptions] = await Promise.all([
    db.subscription.count({ where: { status: "ACTIVE" } }),
    db.subscription.count({ where: { status: "EXPIRED" } }),
    db.subscription.count({ where: { status: "ACTIVE", expiresAt: { lte: soon } } }),
    db.plan.findMany({
      orderBy: { sortOrder: "asc" },
      select: {
        id: true, name: true, priceDzd: true, isActive: true,
        _count: { select: { subscriptions: { where: { status: "ACTIVE" } } } },
      },
    }),
    db.subscription.findMany({
      where: statut ? { status: statut as "ACTIVE" } : {},
      orderBy: { createdAt: "desc" },
      take: 40,
      select: {
        id: true, status: true, startsAt: true, expiresAt: true, paymentReference: true,
        plan: { select: { name: true, priceDzd: true } },
        partner: { select: { slug: true, displayName: true, wilaya: { select: { name: true } } } },
      },
    }),
  ]);

  const monthlyRevenue = plans.reduce(
    (total, plan) => total + plan.priceDzd * plan._count.subscriptions,
    0,
  );

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">Abonnements</h1>

      <p className="mt-3 max-w-2xl border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3 text-[15px] leading-relaxed text-ink-900">
        Aucun prestataire de paiement n&apos;est raccordé. Les règlements affichés
        sont des références de simulation, et les tarifs des formules restent à
        arrêter avec la cliente.
      </p>

      <section className="mt-8 grid gap-px bg-ink-900/10 sm:grid-cols-2 lg:grid-cols-4">
        <StatTile label="Abonnements actifs" value={active} />
        <StatTile label="Expirés" value={expired} />
        <StatTile label="Expirent sous 30 jours" value={expiringSoon} tone={expiringSoon > 0 ? "attention" : "neutral"} />
        <StatTile label="Revenu mensuel indicatif" value={formatDzd(monthlyRevenue)} hint="Formules actives × tarif indicatif" />
      </section>

      <section className="mt-10">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">Formules</h2>
        <div className="mt-3 overflow-x-auto border border-enamel-300">
          <table className="w-full min-w-[36rem] border-collapse bg-enamel-50 text-left">
            <thead>
              <tr className="border-b border-enamel-300">
                {["Formule", "Tarif indicatif", "Abonnés actifs", "État"].map((h) => (
                  <th key={h} className="px-3 py-2.5 font-display text-[10px] font-bold tracking-[0.12em] text-ink-500 uppercase">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="ruled">
              {plans.map((plan) => (
                <tr key={plan.id}>
                  <td className="px-3 py-3 font-display text-sm font-bold text-ink-900">{plan.name}</td>
                  <td className="px-3 py-3 text-sm tabular-nums text-ink-600">
                    {plan.priceDzd === 0 ? "Gratuit" : `${formatDzd(plan.priceDzd)} / mois`}
                  </td>
                  <td className="px-3 py-3 text-sm tabular-nums text-ink-600">{plan._count.subscriptions}</td>
                  <td className="px-3 py-3 text-sm text-ink-600">{plan.isActive ? "Proposée" : "Retirée"}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="mt-10">
        <div className="flex flex-wrap items-baseline justify-between gap-3">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Derniers abonnements
          </h2>
          <form method="get" className="flex items-center gap-2">
            <label htmlFor="statut" className="font-display text-[10px] font-bold tracking-[0.12em] text-ink-500 uppercase">Statut</label>
            <select id="statut" name="statut" defaultValue={statut}
              className="border border-enamel-300 bg-white px-2.5 py-1.5 text-sm text-ink-900">
              <option value="">Tous</option>
              {Object.entries(STATUS).map(([value, meta]) => (
                <option key={value} value={value}>{meta.label}</option>
              ))}
            </select>
            <button type="submit" className="border border-cross-700 px-3 py-1.5 font-display text-[10px] font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100">
              Filtrer
            </button>
          </form>
        </div>

        <div className="mt-3 overflow-x-auto border border-enamel-300">
          <table className="w-full min-w-[44rem] border-collapse bg-enamel-50 text-left">
            <thead>
              <tr className="border-b border-enamel-300">
                {["Partenaire", "Wilaya", "Formule", "Période", "Règlement", "Statut"].map((h) => (
                  <th key={h} className="px-3 py-2.5 font-display text-[10px] font-bold tracking-[0.12em] text-ink-500 uppercase">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="ruled">
              {subscriptions.map((s) => {
                const meta = STATUS[s.status] ?? STATUS.PENDING_PAYMENT;
                return (
                  <tr key={s.id} className="align-top">
                    <td className="px-3 py-3 text-sm">
                      <Link href={`/partenaire/${s.partner.slug}`} className="text-ink-900 hover:text-cross-700">
                        {s.partner.displayName}
                      </Link>
                    </td>
                    <td className="px-3 py-3 text-sm text-ink-600">{s.partner.wilaya.name}</td>
                    <td className="px-3 py-3 text-sm text-ink-600">{s.plan.name}</td>
                    <td className="px-3 py-3 text-sm tabular-nums text-ink-600">
                      {dateOnly.format(s.startsAt)} – {dateOnly.format(s.expiresAt)}
                    </td>
                    <td className="px-3 py-3 text-sm text-ink-400">{s.paymentReference ?? "—"}</td>
                    <td className="px-3 py-3">
                      <span className={`border px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] uppercase ${meta.className}`}>
                        {meta.label}
                      </span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>
    </main>
  );
}
