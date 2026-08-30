import Link from "next/link";
import type { Metadata } from "next";
import { SiteHeader } from "@/components/site-header";
import { SearchInstrument } from "@/components/search-instrument";
import { SearchFilters } from "@/components/search-filters";
import { PartnerCard } from "@/components/partner-card";
import { getSearchFacets, searchPartners, type SearchParams } from "@/lib/search";
import { db } from "@/lib/db";
import { RodMark } from "@/components/rod-mark";

export const metadata: Metadata = {
  title: "Recherche",
};

// Results depend on the query string and on the current time ("open now"), so
// this page is always rendered per request.
export const dynamic = "force-dynamic";

function parseParams(raw: Record<string, string | string[] | undefined>) {
  const one = (key: string) => {
    const value = raw[key];
    return Array.isArray(value) ? value[0] : value;
  };
  const num = (key: string) => {
    const value = Number(one(key));
    return Number.isFinite(value) ? value : undefined;
  };

  const params: SearchParams = {
    q: one("q") || undefined,
    categorie: one("categorie") || undefined,
    specialite: one("specialite") || undefined,
    wilaya: num("wilaya"),
    commune: num("commune"),
    verifie: one("verifie") === "1",
    ouvert: one("ouvert") === "1",
    lat: num("lat"),
    lng: num("lng"),
    page: num("page") ?? 1,
  };
  return params;
}

export default async function SearchPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const raw = await searchParams;
  const params = parseParams(raw);

  const [results, facets, wilayaOptions] = await Promise.all([
    searchPartners(params),
    getSearchFacets(params),
    db.wilaya.findMany({
      select: { code: true, name: true },
      orderBy: { code: "asc" },
    }),
  ]);

  const hasActiveFilters = Boolean(
    params.categorie ||
      params.specialite ||
      params.wilaya ||
      params.commune ||
      params.verifie ||
      params.ouvert ||
      params.lat !== undefined,
  );

  const describedPlace =
    facets.communes.find((c) => c.code === params.commune)?.name ??
    facets.wilayas.find((w) => w.code === params.wilaya)?.name ??
    null;

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader>
        <SearchInstrument
          // Remounted when the URL's own search changes, so a wilaya picked in
          // the sidebar is reflected in the header field rather than leaving the
          // two disagreeing about where the patient is looking.
          key={`${params.q ?? ""}|${params.wilaya ?? ""}`}
          wilayas={wilayaOptions}
          defaultQuery={params.q ?? ""}
          defaultWilaya={params.wilaya ? String(params.wilaya) : ""}
          compact
        />
      </SiteHeader>

      <div className="mx-auto grid w-full max-w-6xl flex-1 gap-px bg-ink-900/10 lg:grid-cols-[17rem_1fr]">
        {/* bg on the aside itself, not just its panel: the grid gap colour
            otherwise shows through below the filters and reads as an
            unfinished block. */}
        <aside className="bg-enamel-50 lg:border-r lg:border-enamel-300">
          <SearchFilters
            categories={facets.categories.map((c) => ({
              value: c.slug,
              label: c.name,
              count: c._count.partners,
            }))}
            wilayas={facets.wilayas.map((w) => ({
              value: String(w.code),
              label: `${String(w.code).padStart(2, "0")} · ${w.name}`,
            }))}
            communes={facets.communes.map((c) => ({
              value: String(c.code),
              label: c.name,
            }))}
            specialties={facets.specialties.map((s) => ({
              value: s.slug,
              label: s.name,
            }))}
          />
        </aside>

        <section className="bg-enamel-100">
          <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-5 py-5 sm:px-6">
            <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
              {results.total} résultat{results.total === 1 ? "" : "s"}
              {describedPlace ? ` à ${describedPlace}` : ""}
            </h1>
            {params.q && (
              <p className="text-sm text-ink-500">
                pour «&nbsp;{params.q}&nbsp;»
              </p>
            )}
          </div>

          {results.sponsored.length > 0 && (
            <div className="mb-px">
              <p className="bg-carbon-amber-soft px-5 py-2 font-display text-[11px] font-bold tracking-[0.12em] text-carbon-amber uppercase sm:px-6">
                Résultats sponsorisés
              </p>
              <div className="flex flex-col gap-px bg-ink-900/10">
                {results.sponsored.map((partner) => (
                  <PartnerCard key={partner.id} partner={partner} sponsored />
                ))}
              </div>
            </div>
          )}

          {results.items.length === 0 ? (
            // "Aucun résultat" over a visible sponsored listing is a
            // contradiction; when the only match is a paid one, say that.
            results.sponsored.length > 0 ? (
              <p className="bg-enamel-50 px-5 py-8 text-center text-[15px] leading-relaxed text-ink-600 sm:px-6">
                Aucun autre partenaire ne correspond à cette recherche.
                Élargissez la wilaya ou retirez un filtre pour en voir
                davantage.
              </p>
            ) : (
              <EmptyState filtersActive={hasActiveFilters} query={params.q} />
            )
          ) : (
            <div className="flex flex-col gap-px bg-ink-900/10">
              {results.items.map((partner) => (
                <PartnerCard key={partner.id} partner={partner} />
              ))}
            </div>
          )}

          {results.pageCount > 1 && (
            <Pagination
              page={results.page}
              pageCount={results.pageCount}
              raw={raw}
            />
          )}
        </section>
      </div>
    </main>
  );
}

function EmptyState({
  filtersActive,
  query,
}: {
  /** Whether the visitor actually narrowed anything — the advice depends on it. */
  filtersActive: boolean;
  query?: string;
}) {
  return (
    <div className="bg-enamel-50 px-5 py-16 text-center sm:px-6">
      <RodMark className="mx-auto block h-12 w-12 text-enamel-300" />
      <h2 className="mt-5 font-display text-xl font-bold text-ink-900">
        Aucun résultat
      </h2>
      <p className="mx-auto mt-2 max-w-md text-[15px] leading-relaxed text-ink-600">
        {query
          ? `Aucun partenaire ne correspond à « ${query} » avec ces filtres.`
          : "Aucun partenaire ne correspond à ces filtres."}{" "}
        {filtersActive
          ? "Retirez un filtre, élargissez la wilaya, ou changez le type de partenaire."
          : "Essayez un autre terme, ou parcourez les partenaires par type."}
      </p>
      <Link
        href="/recherche"
        className="mt-6 inline-flex min-h-11 items-center border border-azur-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:bg-azur-100"
      >
        Voir tous les partenaires
      </Link>
    </div>
  );
}

function Pagination({
  page,
  pageCount,
  raw,
}: {
  page: number;
  pageCount: number;
  raw: Record<string, string | string[] | undefined>;
}) {
  const href = (target: number) => {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(raw)) {
      if (key === "page" || value === undefined) continue;
      query.set(key, Array.isArray(value) ? value[0] : value);
    }
    query.set("page", String(target));
    return `/recherche?${query}`;
  };

  return (
    <nav
      aria-label="Pagination"
      className="flex items-center justify-between gap-4 bg-enamel-50 px-5 py-5 sm:px-6"
    >
      {page > 1 ? (
        <Link
          href={href(page - 1)}
          className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:underline"
        >
          &larr; Précédent
        </Link>
      ) : (
        <span />
      )}
      <span className="font-display text-sm tabular-nums text-ink-500">
        Page {page} sur {pageCount}
      </span>
      {page < pageCount ? (
        <Link
          href={href(page + 1)}
          className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:underline"
        >
          Suivant &rarr;
        </Link>
      ) : (
        <span />
      )}
    </nav>
  );
}
