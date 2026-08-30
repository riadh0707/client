import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { FavouriteButton } from "@/components/favourite-button";
import { db } from "@/lib/db";
import { formatDzd } from "@/lib/format";
import {
  groupByWeekday,
  resolveOpenState,
  type Interval,
  type OpenState,
} from "@/lib/hours";

export const dynamic = "force-dynamic";

async function getPartner(slug: string) {
  return db.partner.findUnique({
    where: { slug },
    include: {
      category: true,
      specialty: true,
      wilaya: true,
      commune: true,
      services: { orderBy: { name: "asc" } },
      openingHours: true,
      sponsorships: {
        where: { startsAt: { lte: new Date() }, endsAt: { gte: new Date() } },
        take: 1,
      },
    },
  });
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const partner = await getPartner(slug);
  if (!partner) return { title: "Profil introuvable" };

  return {
    title: partner.displayName,
    description: `${partner.specialty?.name ?? partner.category.name} à ${partner.commune.name}, wilaya de ${partner.wilaya.name}.`,
  };
}

export default async function PartnerPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const partner = await getPartner(slug);

  // A suspended partner must not be reachable by direct link either: hiding it
  // from search while leaving the URL live would defeat the moderation action.
  // Only a listed partner has a public page. A PENDING profile is one nobody has
  // reviewed yet: excluding it from search but serving it on a direct link would
  // publish an unvetted practitioner to anyone holding the URL, which is the one
  // thing the moderation step exists to prevent — and would make both the
  // registration notice and the 404 copy false.
  if (!partner || partner.status !== "ACTIVE") notFound();

  const openState = partner.category.supportsOpeningHours
    ? resolveOpenState(partner.openingHours as Interval[])
    : ({ status: "unknown" } as const);
  const schedule = groupByWeekday(partner.openingHours as Interval[]);
  const isSponsored = partner.sponsorships.length > 0;
  const mapsHref = `https://www.google.com/maps/dir/?api=1&destination=${partner.lat},${partner.lng}`;

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      {/* Identity plaque. Enamel field so the profile reads as the engraved
          nameplate outside a practice, which is the direction's core object. */}
      <section className="bg-rod-700 text-enamel-50">
        <div className="mx-auto w-full max-w-5xl px-5 py-8 sm:px-8 sm:py-12">
          <nav aria-label="Fil d'Ariane" className="mb-6 text-sm text-rod-100">
            <Link href="/recherche" className="inline-flex min-h-11 items-center py-2 hover:underline">
              Recherche
            </Link>
            <span aria-hidden> / </span>
            <Link
              href={`/recherche?wilaya=${partner.wilaya.code}`}
              className="inline-flex min-h-11 items-center py-2 hover:underline"
            >
              {partner.wilaya.name}
            </Link>
          </nav>

          <div className="flex flex-wrap items-center gap-2.5">
            <span className="border border-rod-400/50 px-2 py-1 font-display text-[11px] font-bold tracking-[0.1em] uppercase">
              {partner.category.name}
            </span>
            {partner.verificationStatus === "VERIFIED" ? (
              <span className="bg-rod-500 px-2 py-1 font-display text-[11px] font-bold tracking-[0.1em] text-rod-950 uppercase">
                Profil vérifié
              </span>
            ) : (
              <span className="border border-rod-200/40 px-2 py-1 font-display text-[11px] font-bold tracking-[0.1em] text-rod-100 uppercase">
                Non vérifié
              </span>
            )}
            {isSponsored && (
              <span className="border border-carbon-amber/60 bg-carbon-amber-soft px-2 py-1 font-display text-[11px] font-bold tracking-[0.1em] text-carbon-amber uppercase">
                Mise en avant sponsorisée
              </span>
            )}
          </div>

          <h1 className="mt-4 font-display text-3xl font-bold tracking-[-0.02em] sm:text-5xl">
            {partner.displayName}
          </h1>
          <p className="mt-2 text-lg text-rod-100">
            {partner.specialty?.name ?? partner.category.name}
            {partner.subSpecialty ? ` · ${partner.subSpecialty}` : ""}
          </p>

          {partner.category.supportsOpeningHours && (
            <p className="mt-5">
              <OpenNow state={openState} />
            </p>
          )}
        </div>
      </section>

      <div className="mx-auto grid w-full max-w-5xl flex-1 gap-px bg-ink-900/10 lg:grid-cols-[1fr_20rem]">
        <div className="flex flex-col gap-px bg-enamel-50">
          {partner.bio && (
            <Panel title="Présentation">
              <p className="text-[15px] leading-relaxed text-ink-600">
                {partner.bio}
              </p>
            </Panel>
          )}

          {partner.services.length > 0 && (
            <Panel title="Services">
              <ul className="ruled -my-3">
                {partner.services.map((service) => (
                  <li
                    key={service.id}
                    className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3"
                  >
                    <span className="font-display text-[15px] font-bold text-ink-900">
                      {service.name}
                    </span>
                    <span className="text-sm tabular-nums text-ink-500">
                      {/* Null price means "not published", which is common in
                          Algeria. Rendering it as free would be a lie. */}
                      {service.priceDzd !== null
                        ? formatDzd(service.priceDzd)
                        : "Tarif non communiqué"}
                      {service.durationMinutes
                        ? ` · ${service.durationMinutes} min`
                        : ""}
                    </span>
                  </li>
                ))}
              </ul>
            </Panel>
          )}

          {partner.category.supportsOpeningHours && (
            <Panel title="Horaires">
              <ul className="ruled -my-2.5">
                {schedule.map((day) => (
                  <li
                    key={day.weekday}
                    className="flex items-baseline justify-between gap-4 py-2.5"
                  >
                    <span className="text-[15px] text-ink-900">{day.label}</span>
                    <span className="text-right text-sm tabular-nums text-ink-500">
                      {day.intervals.length === 0
                        ? "Fermé"
                        : day.intervals
                            .map((i) => `${i.opensAt} – ${i.closesAt}`)
                            .join(" · ")}
                    </span>
                  </li>
                ))}
              </ul>
            </Panel>
          )}
        </div>

        <aside className="flex flex-col gap-px bg-enamel-50">
          <Panel title="Coordonnées">
            <address className="not-italic">
              <p className="text-[15px] leading-relaxed text-ink-900">
                {partner.address}
              </p>
              <p className="mt-1 text-sm text-ink-500">
                {partner.commune.name}, {partner.wilaya.name} (
                {String(partner.wilaya.code).padStart(2, "0")})
              </p>
              {/* On an urgent lookup the phone number is the conversion, so it
                  carries the weight of one on the page rather than sitting at
                  body size among the address lines. */}
              <a
                href={`tel:${partner.phone.replace(/\s/g, "")}`}
                className="mt-4 flex min-h-11 items-center font-display text-2xl font-bold tracking-[-0.01em] text-rod-700 hover:underline"
              >
                {partner.phone}
              </a>
            </address>

            <a
              href={mapsHref}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-4 flex min-h-11 items-center justify-center border border-rod-700 px-3 py-2 text-center font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:bg-rod-100"
            >
              Itinéraire
            </a>

            <FavouriteButton partnerId={partner.id} slug={partner.slug} />
          </Panel>

          <Panel title="Rendez-vous">
            {partner.category.supportsAppointments ? (
              <>
                <p className="text-[15px] leading-relaxed text-ink-600">
                  Consultations sur rendez-vous, créneaux de{" "}
                  {partner.slotDurationMinutes} minutes.
                </p>
                <Link
                  href={`/partenaire/${partner.slug}/rendez-vous`}
                  className="mt-4 block bg-rod-500 px-4 py-3 text-center font-display text-sm font-bold tracking-[0.06em] text-rod-950 uppercase hover:bg-rod-400"
                >
                  Prendre rendez-vous
                </Link>
              </>
            ) : (
              // Reading the category flag, not the slug — the whole point of the
              // capability model.
              <p className="text-[15px] leading-relaxed text-ink-600">
                {partner.category.name} sans rendez-vous. Présentez-vous
                directement pendant les heures d&apos;ouverture, ou appelez.
              </p>
            )}
          </Panel>
        </aside>
      </div>
    </main>
  );
}

function Panel({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <section className="bg-enamel-50 p-5 sm:p-6">
      <h2 className="mb-3 font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
        {title}
      </h2>
      {children}
    </section>
  );
}

function OpenNow({ state }: { state: OpenState }) {
  if (state.status === "open") {
    return (
      <span className="inline-flex items-center gap-2 bg-rod-500 px-3 py-1.5 font-display text-sm font-bold text-rod-950">
        <span aria-hidden className="h-2 w-2 bg-rod-950" />
        Ouvert · ferme à {state.closesAt}
      </span>
    );
  }
  if (state.status === "closed") {
    return (
      <span className="inline-flex items-center gap-2 border border-rod-200/50 px-3 py-1.5 font-display text-sm text-rod-100">
        <span aria-hidden className="h-2 w-2 bg-carbon-rose" />
        Fermé · ouvre à {state.opensAt}
      </span>
    );
  }
  return (
    <span className="font-display text-sm text-rod-100">
      Horaires non renseignés
    </span>
  );
}
