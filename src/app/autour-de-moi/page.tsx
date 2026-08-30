import type { Metadata } from "next";
import Link from "next/link";
import { SiteHeader } from "@/components/site-header";
import { PartnerCard } from "@/components/partner-card";
import { ProximityPlot, type PlotPoint } from "@/components/proximity-plot";
import { Locator } from "./locator";
import { db } from "@/lib/db";
import { distanceKm, resolveOpenState, type Interval } from "@/lib/hours";

export const metadata: Metadata = {
  title: "Professionnels près de vous",
  description:
    "Trouvez les professionnels et établissements de santé DOCTORY autour de vous, ou dans votre wilaya et votre commune.",
};

export const dynamic = "force-dynamic";

const RESULT_LIMIT = 12;

export default async function NearbyPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const raw = await searchParams;
  const one = (key: string) => {
    const value = raw[key];
    return Array.isArray(value) ? value[0] : value;
  };
  const num = (key: string) => {
    const value = Number(one(key));
    return Number.isFinite(value) && value !== 0 ? value : undefined;
  };

  const lat = num("lat");
  const lng = num("lng");
  const wilayaCode = num("wilaya");
  const communeCode = num("commune");
  const categorie = one("categorie");

  const [wilayas, communes, categories] = await Promise.all([
    db.wilaya.findMany({ select: { code: true, name: true }, orderBy: { code: "asc" } }),
    wilayaCode
      ? db.commune.findMany({
          where: { wilayaCode },
          select: { code: true, name: true },
          orderBy: { name: "asc" },
        })
      : Promise.resolve([]),
    db.partnerCategory.findMany({
      orderBy: { sortOrder: "asc" },
      select: { slug: true, name: true },
    }),
  ]);

  // The centre is the granted position, else the chosen commune, else the
  // chosen wilaya's chef-lieu. Nothing chosen means nothing to measure from.
  let centre: { lat: number; lng: number } | null = null;
  let centreLabel = "";

  if (lat !== undefined && lng !== undefined) {
    centre = { lat, lng };
    centreLabel = "votre position";
  } else if (communeCode) {
    const commune = await db.commune.findUnique({
      where: { code: communeCode },
      select: { name: true, wilaya: { select: { lat: true, lng: true, name: true } } },
    });
    if (commune) {
      // The dataset carries no commune-level coordinates, so the wilaya centre
      // stands in. Distances inside one wilaya are indicative, and the label
      // says which reference point is being used rather than implying more
      // precision than exists.
      centre = { lat: commune.wilaya.lat, lng: commune.wilaya.lng };
      centreLabel = `${commune.name} (centre de ${commune.wilaya.name})`;
    }
  } else if (wilayaCode) {
    const wilaya = await db.wilaya.findUnique({
      where: { code: wilayaCode },
      select: { name: true, lat: true, lng: true },
    });
    if (wilaya) {
      centre = { lat: wilaya.lat, lng: wilaya.lng };
      centreLabel = `le centre de ${wilaya.name}`;
    }
  }

  const partners = centre
    ? await db.partner.findMany({
        where: {
          status: "ACTIVE",
          ...(communeCode ? { communeCode } : wilayaCode ? { wilayaCode } : {}),
          ...(categorie ? { category: { slug: categorie } } : {}),
        },
        select: {
          id: true,
          slug: true,
          displayName: true,
          address: true,
          phone: true,
          lat: true,
          lng: true,
          subSpecialty: true,
          verificationStatus: true,
          category: {
            select: {
              slug: true,
              name: true,
              supportsAppointments: true,
              supportsOpeningHours: true,
            },
          },
          specialty: { select: { name: true } },
          wilaya: { select: { code: true, name: true } },
          commune: { select: { name: true } },
          openingHours: { select: { weekday: true, opensAt: true, closesAt: true } },
        },
        // Bounded: without a wilaya filter this would otherwise measure every
        // partner in the country to return twelve.
        take: wilayaCode ? 400 : 800,
      })
    : [];

  const now = new Date();
  const nearest = centre
    ? partners
        .map((partner) => ({
          ...partner,
          distance: distanceKm(centre, partner),
          openState: partner.category.supportsOpeningHours
            ? resolveOpenState(partner.openingHours as Interval[], now)
            : ({ status: "unknown" } as const),
        }))
        .sort((a, b) => a.distance - b.distance)
        .slice(0, RESULT_LIMIT)
    : [];

  const plotPoints: PlotPoint[] = nearest.map((partner) => ({
    slug: partner.slug,
    name: partner.displayName,
    lat: partner.lat,
    lng: partner.lng,
    distance: partner.distance,
  }));

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
        <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
          Professionnels près de vous
        </h1>
        <p className="mt-2 max-w-2xl text-[15px] leading-relaxed text-ink-600">
          Autorisez la géolocalisation, ou choisissez simplement votre wilaya et
          votre commune. Les deux fonctionnent.
        </p>

        <div className="mt-6">
          <Locator wilayas={wilayas} communes={communes} />
        </div>

        <div className="mt-px flex flex-wrap gap-px bg-ink-900/10">
          <FilterChip
            href={buildHref(raw, { categorie: null })}
            label="Tous les types"
            active={!categorie}
          />
          {categories.map((category) => (
            <FilterChip
              key={category.slug}
              href={buildHref(raw, { categorie: category.slug })}
              label={category.name}
              active={categorie === category.slug}
            />
          ))}
        </div>

        {!centre ? (
          <div className="mt-8 bg-enamel-50 px-5 py-16 text-center">
            <span aria-hidden className="cross-mark mx-auto block h-10 w-10 text-enamel-300" />
            <h2 className="mt-5 font-display text-xl font-bold text-ink-900">
              Où cherchez-vous&nbsp;?
            </h2>
            <p className="mx-auto mt-2 max-w-md text-[15px] leading-relaxed text-ink-600">
              Choisissez votre wilaya ci-dessus, ou autorisez la géolocalisation
              pour trier par distance réelle.
            </p>
          </div>
        ) : nearest.length === 0 ? (
          <div className="mt-8 bg-enamel-50 px-5 py-16 text-center">
            <span aria-hidden className="cross-mark mx-auto block h-10 w-10 text-enamel-300" />
            <h2 className="mt-5 font-display text-xl font-bold text-ink-900">
              Aucun partenaire dans cette zone
            </h2>
            <p className="mx-auto mt-2 max-w-md text-[15px] leading-relaxed text-ink-600">
              Élargissez à toute la wilaya, ou retirez le filtre de type.
            </p>
            <Link
              href="/recherche"
              className="mt-6 inline-block border border-cross-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100"
            >
              Aller à la recherche
            </Link>
          </div>
        ) : (
          <div className="mt-8 grid gap-px bg-ink-900/10 lg:grid-cols-[1fr_20rem]">
            <div className="flex flex-col gap-px bg-ink-900/10">
              {nearest.map((partner) => (
                <PartnerCard key={partner.id} partner={partner} />
              ))}
            </div>
            <aside className="bg-enamel-50">
              <ProximityPlot
                centre={centre}
                points={plotPoints}
                centreLabel={centreLabel}
              />
              <p className="px-5 pb-5 text-sm leading-relaxed text-ink-500">
                Ce plan situe les partenaires les uns par rapport aux autres. Pour
                un itinéraire, ouvrez une fiche&nbsp;: le bouton renvoie vers une
                application de cartographie.
              </p>
            </aside>
          </div>
        )}
      </div>
    </main>
  );
}

function buildHref(
  raw: Record<string, string | string[] | undefined>,
  changes: Record<string, string | null>,
) {
  const query = new URLSearchParams();
  for (const [key, value] of Object.entries(raw)) {
    if (value === undefined) continue;
    query.set(key, Array.isArray(value) ? value[0] : value);
  }
  for (const [key, value] of Object.entries(changes)) {
    if (value === null) query.delete(key);
    else query.set(key, value);
  }
  return `/autour-de-moi?${query}`;
}

function FilterChip({
  href,
  label,
  active,
}: {
  href: string;
  label: string;
  active: boolean;
}) {
  return (
    <Link
      href={href}
      aria-current={active ? "true" : undefined}
      className={`flex-1 px-4 py-3 text-center font-display text-xs font-bold tracking-[0.08em] uppercase ${
        active
          ? "bg-cross-700 text-enamel-50"
          : "bg-enamel-50 text-ink-600 hover:bg-cross-50 hover:text-cross-700"
      }`}
    >
      {label}
    </Link>
  );
}
