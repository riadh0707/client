import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { requirePartnerContext } from "@/lib/pro";
import { dateOnly, formatDzd } from "@/lib/format";

export const metadata: Metadata = { title: "Abonnement" };
export const dynamic = "force-dynamic";

const STATUS_LABELS: Record<string, { label: string; className: string }> = {
  ACTIVE: { label: "Actif", className: "border-rod-600/40 bg-rod-100 text-rod-800" },
  EXPIRED: { label: "Expiré", className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose" },
  CANCELLED: { label: "Résilié", className: "border-ink-300/50 bg-enamel-200 text-ink-600" },
  PENDING_PAYMENT: { label: "En attente de paiement", className: "border-carbon-amber/50 bg-carbon-amber-soft text-carbon-amber" },
};

export default async function SubscriptionPage() {
  const { partner, permissions } = await requirePartnerContext("/pro/abonnement");
  if (!partner) redirect("/pro");
  if (!permissions.canManageProfile) redirect("/pro?erreur=droits");

  const [history, plans] = await Promise.all([
    db.subscription.findMany({
      where: { partnerId: partner.id },
      orderBy: { createdAt: "desc" },
      include: { plan: true },
    }),
    db.plan.findMany({ where: { isActive: true }, orderBy: { sortOrder: "asc" } }),
  ]);

  const current = history.find((s) => s.status === "ACTIVE") ?? history[0] ?? null;

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Abonnement
      </h1>

      {/* PRODUCT.md records that no payment provider has been chosen and that
          plan pricing is not settled. Saying so here is more honest than a
          checkout button that leads nowhere. */}
      <p className="mt-5 border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3 text-[15px] leading-relaxed text-ink-900">
        Le paiement en ligne n&apos;est pas encore raccordé. Les abonnements
        ci-dessous sont enregistrés en base et gérés par l&apos;administration de
        DOCTORY&nbsp;; les tarifs affichés sont indicatifs et restent à arrêter.
      </p>

      <section className="mt-8">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Formule actuelle
        </h2>
        {current ? (
          <div className="mt-3 bg-enamel-50 p-5">
            <div className="flex flex-wrap items-center gap-3">
              <span className="font-display text-xl font-bold text-ink-900">
                {current.plan.name}
              </span>
              <span
                className={`border px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${
                  (STATUS_LABELS[current.status] ?? STATUS_LABELS.PENDING_PAYMENT).className
                }`}
              >
                {(STATUS_LABELS[current.status] ?? STATUS_LABELS.PENDING_PAYMENT).label}
              </span>
            </div>
            <p className="mt-2 text-[15px] text-ink-600">
              {current.plan.description}
            </p>
            <dl className="ruled mt-4 border-t border-enamel-300">
              <Row term="Tarif indicatif" value={formatDzd(current.plan.priceDzd)} />
              <Row term="Début" value={dateOnly.format(current.startsAt)} />
              <Row
                term={current.status === "ACTIVE" ? "Échéance" : "Expiré le"}
                value={dateOnly.format(current.expiresAt)}
              />
              <Row term="Règlement" value="Simulation (aucun paiement réel)" />
            </dl>
          </div>
        ) : (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Aucun abonnement enregistré.
          </p>
        )}
      </section>

      <section className="mt-10">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Formules disponibles
        </h2>
        <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
          {plans.map((plan) => (
            <div key={plan.id} className="bg-enamel-50 p-5">
              <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <span className="font-display text-lg font-bold text-ink-900">
                  {plan.name}
                </span>
                <span className="font-display text-sm tabular-nums text-ink-600">
                  {plan.priceDzd === 0 ? "Gratuit" : `${formatDzd(plan.priceDzd)} / mois`}
                </span>
              </div>
              <p className="mt-1.5 text-[15px] text-ink-600">{plan.description}</p>
              <ul className="mt-3 flex flex-wrap gap-2">
                {plan.features.split(",").map((feature) => (
                  <li
                    key={feature}
                    className="border border-enamel-300 px-2 py-0.5 font-display text-[11px] font-bold tracking-[0.06em] text-ink-500 uppercase"
                  >
                    {feature.replace(/_/g, " ")}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </section>

      {history.length > 1 && (
        <section className="mt-10">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Historique
          </h2>
          <ul className="ruled mt-3 border-y border-enamel-300">
            {history.map((subscription) => (
              <li key={subscription.id} className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3">
                <span className="font-display text-[15px] font-bold text-ink-900">
                  {subscription.plan.name}
                </span>
                <span className="text-sm tabular-nums text-ink-500">
                  {dateOnly.format(subscription.startsAt)} –{" "}
                  {dateOnly.format(subscription.expiresAt)} ·{" "}
                  {(STATUS_LABELS[subscription.status] ?? STATUS_LABELS.PENDING_PAYMENT).label}
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}
    </main>
  );
}

function Row({ term, value }: { term: string; value: string }) {
  return (
    <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-2.5">
      <dt className="text-[15px] text-ink-600">{term}</dt>
      <dd className="font-display text-[15px] tabular-nums text-ink-900">{value}</dd>
    </div>
  );
}
