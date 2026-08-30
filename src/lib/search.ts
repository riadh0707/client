import { Prisma } from "@/generated/prisma";
import { db } from "@/lib/db";
import { distanceKm, resolveOpenState, type Interval } from "@/lib/hours";

export type SearchParams = {
  q?: string;
  categorie?: string;
  wilaya?: number;
  commune?: number;
  specialite?: string;
  verifie?: boolean;
  ouvert?: boolean;
  lat?: number;
  lng?: number;
  page?: number;
};

export const PAGE_SIZE = 12;

/**
 * Builds the Prisma filter. Only SUSPENDED and PENDING partners are excluded —
 * an unverified profile is still a real listing and hiding it would misrepresent
 * coverage; the card says plainly that it is unverified instead.
 */
function buildWhere(params: SearchParams): Prisma.PartnerWhereInput {
  const where: Prisma.PartnerWhereInput = { status: "ACTIVE" };

  if (params.q) {
    // SQLite's LIKE is case-insensitive for ASCII, which covers the Latin
    // transliterations used throughout the demo data.
    where.OR = [
      { displayName: { contains: params.q } },
      { bio: { contains: params.q } },
      { specialty: { name: { contains: params.q } } },
      // Patients type the practitioner ("cardiologue"), not the discipline
      // ("Cardiologie"). Specialty.aliases carries those synonyms; without this
      // clause the brief's own example searches return nothing.
      { specialty: { aliases: { contains: params.q } } },
      { category: { name: { contains: params.q } } },
      { commune: { name: { contains: params.q } } },
      { wilaya: { name: { contains: params.q } } },
    ];
  }

  if (params.categorie) where.category = { slug: params.categorie };
  if (params.wilaya) where.wilayaCode = params.wilaya;
  if (params.commune) where.communeCode = params.commune;
  if (params.specialite) where.specialty = { slug: params.specialite };
  if (params.verifie) where.verificationStatus = "VERIFIED";

  return where;
}

export type SearchResult = Awaited<ReturnType<typeof searchPartners>>;

export async function searchPartners(
  params: SearchParams,
  /**
   * Injectable so the sponsored-filter rule can be pinned to a known hour in a
   * test. PRODUCT.md forbids covert ranking manipulation, and "a paid listing
   * must obey the visitor's own filter" is exactly the kind of rule that decays
   * silently unless something enforces it.
   */
  now: Date = new Date(),
) {
  const where = buildWhere(params);
  const page = Math.max(1, params.page ?? 1);

  // Sponsored placement is fetched as its own list and shown in a labelled
  // block above the organic results. PRODUCT.md forbids covert ranking
  // manipulation, so paid results never merge silently into the ordinary ones.
  const sponsoredIds = (
    await db.sponsorship.findMany({
      where: {
        startsAt: { lte: now },
        endsAt: { gte: now },
        partner: where,
      },
      orderBy: { weight: "desc" },
      select: { partnerId: true },
      take: 3,
    })
  ).map((row) => row.partnerId);

  const select = {
    id: true,
    slug: true,
    displayName: true,
    bio: true,
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
        isIndividual: true,
        supportsAppointments: true,
        supportsOpeningHours: true,
      },
    },
    specialty: { select: { name: true, slug: true } },
    wilaya: { select: { code: true, name: true } },
    commune: { select: { code: true, name: true } },
    openingHours: { select: { weekday: true, opensAt: true, closesAt: true } },
  } satisfies Prisma.PartnerSelect;

  // The organic query excludes sponsored ids, so the count must exclude them
  // too. Counting `where` alone made totals larger than the rows that can ever
  // be paginated, which manufactured a final page that renders empty.
  const organicWhere: Prisma.PartnerWhereInput =
    sponsoredIds.length > 0
      ? { AND: [where, { id: { notIn: sponsoredIds } }] }
      : where;

  const [sponsoredRows, organicRows, total] = await Promise.all([
    sponsoredIds.length > 0
      ? db.partner.findMany({ where: { id: { in: sponsoredIds } }, select })
      : Promise.resolve([]),
    db.partner.findMany({
      where: organicWhere,
      select,
      orderBy: { displayName: "asc" },
      // Distance and "open now" are computed in JS, so when either is active the
      // page is filtered and sorted after fetching rather than by the database.
      skip: params.ouvert || params.lat === undefined ? (page - 1) * PAGE_SIZE : 0,
      take: params.ouvert || params.lat !== undefined ? 500 : PAGE_SIZE,
    }),
    db.partner.count({ where: organicWhere }),
  ]);

  const decorate = (row: (typeof organicRows)[number]) => {
    const openState = row.category.supportsOpeningHours
      ? resolveOpenState(row.openingHours as Interval[], now)
      : ({ status: "unknown" } as const);
    const distance =
      params.lat !== undefined && params.lng !== undefined
        ? distanceKm({ lat: params.lat, lng: params.lng }, row)
        : null;
    return { ...row, openState, distance };
  };

  let organic = organicRows.map(decorate);
  // Paid placement buys position, never exemption from the visitor's own
  // filter. Showing a closed pharmacy to someone who asked for open ones is the
  // covert ranking manipulation PRODUCT.md forbids, disclosure or not.
  let sponsored = sponsoredRows.map(decorate);

  if (params.ouvert) {
    organic = organic.filter((row) => row.openState.status === "open");
    sponsored = sponsored.filter((row) => row.openState.status === "open");
  }
  if (params.lat !== undefined) {
    organic.sort((a, b) => (a.distance ?? 0) - (b.distance ?? 0));
    sponsored.sort((a, b) => (a.distance ?? 0) - (b.distance ?? 0));
  }

  // When post-filtering ran, the true count is what survived it, and pagination
  // has to be applied here rather than in SQL.
  const postFiltered = params.ouvert || params.lat !== undefined;
  const effectiveTotal = postFiltered ? organic.length : total;
  const items = postFiltered
    ? organic.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)
    : organic;

  return {
    // Sponsored rows lead page one only; repeating them on every page would
    // pad each page with the same three paid listings.
    sponsored: page === 1 ? sponsored : [],
    items,
    // What the visitor is told they found. Sponsored partners match the query
    // like any other — they are pulled out of the organic set to avoid printing
    // them twice, not because they are not results — so leaving them out of the
    // headline produced "0 résultats" above a visible listing. Counted on every
    // page, so the number does not change under the reader when they paginate.
    total: effectiveTotal + sponsored.length,
    page,
    // Pagination still runs over the organic set alone: the sponsored block is
    // an addition to page one, not a row in the paged sequence.
    pageCount: Math.max(1, Math.ceil(effectiveTotal / PAGE_SIZE)),
  };
}

/** Facets for the filter panel. Counts respect the current query. */
export async function getSearchFacets(params: SearchParams) {
  const [categories, wilayas, communes, specialties] = await Promise.all([
    db.partnerCategory.findMany({
      orderBy: { sortOrder: "asc" },
      select: {
        slug: true,
        name: true,
        _count: { select: { partners: { where: { status: "ACTIVE" } } } },
      },
    }),
    db.wilaya.findMany({
      orderBy: { code: "asc" },
      select: { code: true, name: true },
    }),
    params.wilaya
      ? db.commune.findMany({
          where: { wilayaCode: params.wilaya },
          orderBy: { name: "asc" },
          select: { code: true, name: true },
        })
      : Promise.resolve([]),
    params.categorie
      ? db.specialty.findMany({
          where: { category: { slug: params.categorie } },
          orderBy: { name: "asc" },
          select: { slug: true, name: true },
        })
      : Promise.resolve([]),
  ]);

  return { categories, wilayas, communes, specialties };
}
