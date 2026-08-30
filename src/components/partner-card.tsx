import Link from "next/link";
import { formatDistance, type OpenState } from "@/lib/hours";

export type PartnerCardData = {
  slug: string;
  displayName: string;
  address: string;
  phone: string;
  subSpecialty: string | null;
  verificationStatus: string;
  category: {
    slug: string;
    name: string;
    supportsAppointments: boolean;
    supportsOpeningHours: boolean;
  };
  specialty: { name: string } | null;
  wilaya: { code: number; name: string };
  commune: { name: string };
  openState: OpenState;
  distance: number | null;
};

/**
 * One partner, as a plaque. Ruled rows rather than floating cards — the Carnet
 * donation — so a long results list scans as a register instead of a pile of
 * boxes.
 *
 * `sponsored` renders a visible label. PRODUCT.md requires a paid result to be
 * distinguishable, so this is not optional styling: the badge, plus the carbon
 * paper colour and the labelled band above the block, are the disclosure.
 */
export function PartnerCard({
  partner,
  sponsored = false,
}: {
  partner: PartnerCardData;
  sponsored?: boolean;
}) {
  const { openState } = partner;

  return (
    <article
      className={
        sponsored
          ? // The carbon-copy flimsy: a different paper colour means a different
            // state. It groups the sponsored block without a side rule, which
            // added no disclosure the amber band and the badge do not already
            // carry.
            "bg-carbon-amber-soft transition-colors hover:bg-carbon-amber-soft/60"
          : "bg-enamel-50 transition-colors hover:bg-cross-50"
      }
    >
      <Link
        href={`/partenaire/${partner.slug}`}
        className="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:gap-6 sm:p-6"
      >
        <span
          aria-hidden
          className="cross-mark mt-1 hidden h-5 w-5 shrink-0 text-cross-500 sm:block"
        />

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <h3 className="font-display text-lg font-bold text-ink-900 sm:text-xl">
              {partner.displayName}
            </h3>
            {sponsored && (
              <span className="border border-carbon-amber/50 bg-enamel-50 px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] text-carbon-amber uppercase">
                Sponsorisé
              </span>
            )}
            {partner.verificationStatus === "VERIFIED" && (
              <span className="border border-cross-600/40 bg-cross-100 px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] text-cross-800 uppercase">
                Vérifié
              </span>
            )}
          </div>

          <p className="mt-1 text-sm text-ink-600">
            {partner.specialty?.name ?? partner.category.name}
            {partner.subSpecialty ? ` · ${partner.subSpecialty}` : ""}
          </p>

          <p className="mt-2.5 text-sm leading-snug text-ink-500">
            {partner.address}
            <span className="block text-ink-400">
              {partner.commune.name}, {partner.wilaya.name} (
              {String(partner.wilaya.code).padStart(2, "0")})
            </span>
          </p>
        </div>

        <div className="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 sm:flex-col sm:items-end sm:gap-2">
          {partner.distance !== null && (
            <span className="font-display text-sm font-bold tabular-nums text-ink-900">
              {formatDistance(partner.distance)}
            </span>
          )}

          {partner.category.supportsOpeningHours && (
            <OpenBadge state={openState} />
          )}

          {partner.category.supportsAppointments ? (
            <span className="font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase">
              Rendez-vous
            </span>
          ) : (
            <span className="font-display text-xs tracking-[0.04em] text-ink-400">
              {partner.phone}
            </span>
          )}
        </div>
      </Link>
    </article>
  );
}

function OpenBadge({ state }: { state: OpenState }) {
  if (state.status === "open") {
    return (
      <span className="inline-flex items-center gap-1.5 font-display text-xs font-bold text-cross-700">
        <span aria-hidden className="h-2 w-2 bg-cross-500" />
        Ouvert · ferme à {state.closesAt}
      </span>
    );
  }
  if (state.status === "closed") {
    return (
      <span className="inline-flex items-center gap-1.5 font-display text-xs text-carbon-rose">
        <span aria-hidden className="h-2 w-2 bg-carbon-rose" />
        Fermé · ouvre à {state.opensAt}
      </span>
    );
  }
  // Never claim "closed" from an empty schedule — see resolveOpenState.
  return (
    <span className="font-display text-xs text-ink-400">Horaires non renseignés</span>
  );
}
