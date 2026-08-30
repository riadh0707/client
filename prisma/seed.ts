/**
 * Seeds DOCTORY.
 *
 * Geography is real reference data (data/generated/geography.json, built from the
 * vendored leblad dataset). Everything else is fictional demo content: PRODUCT.md
 * forbids using real people as fake partners, and forbids inventing commercial
 * claims. Partner names are constructed from common Algerian family names paired
 * with invented practice names; none refers to an actual practitioner.
 */

import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { randomUUID } from "node:crypto";
import { scryptSync, randomBytes } from "node:crypto";
import { PrismaBetterSqlite3 } from "@prisma/adapter-better-sqlite3";
import { PrismaClient } from "../src/generated/prisma/client.js";

const adapter = new PrismaBetterSqlite3({
  url: process.env.DATABASE_URL ?? "file:./prisma/dev.db",
});
const db = new PrismaClient({ adapter });

// Deterministic pseudo-random so reseeding produces the same demo, which makes
// screenshots and review comments stable across runs.
let seedState = 0x2590fc2b;
function rand() {
  seedState = (seedState * 1664525 + 1013904223) >>> 0;
  return seedState / 0x100000000;
}
function pick<T>(items: readonly T[]): T {
  return items[Math.floor(rand() * items.length)];
}
function pickSome<T>(items: readonly T[], count: number): T[] {
  const pool = [...items];
  const out: T[] = [];
  for (let i = 0; i < count && pool.length > 0; i += 1) {
    out.push(...pool.splice(Math.floor(rand() * pool.length), 1));
  }
  return out;
}

function hashPassword(plain: string) {
  const salt = randomBytes(16).toString("hex");
  const hash = scryptSync(plain, salt, 64).toString("hex");
  return `${salt}:${hash}`;
}

type Geography = {
  wilayas: {
    code: number;
    name: string;
    nameAr: string | null;
    nameBer: string | null;
    lat: number;
    lng: number;
    phoneCodes: number[];
    postalCodes: number[];
    promotedFrom?: number;
  }[];
  communes: {
    code: number;
    wilayaCode: number;
    daira: string;
    name: string;
    nameAr: string | null;
  }[];
};

const CATEGORIES = [
  {
    slug: "doctor",
    name: "Médecin",
    nameAr: "طبيب",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 1,
    specialties: [
      "Médecine générale",
      "Cardiologie",
      "Pédiatrie",
      "Gynécologie",
      "Dermatologie",
      "Ophtalmologie",
      "Oto-rhino-laryngologie",
      "Gastro-entérologie",
      "Endocrinologie",
      "Neurologie",
      "Pneumologie",
      "Rhumatologie",
      "Psychiatrie",
      "Chirurgie générale",
      "Orthopédie",
      "Urologie",
      "Néphrologie",
    ],
  },
  {
    slug: "dentist",
    name: "Dentiste",
    nameAr: "طبيب أسنان",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 2,
    specialties: [
      "Dentisterie générale",
      "Orthodontie",
      "Implantologie",
      "Parodontologie",
      "Chirurgie dentaire",
      "Pédodontie",
    ],
  },
  {
    slug: "pharmacy",
    name: "Pharmacie",
    nameAr: "صيدلية",
    isIndividual: false,
    // A pharmacy is found and phoned, not booked. The interface reads this flag
    // rather than testing for the slug.
    supportsAppointments: false,
    supportsOpeningHours: true,
    sortOrder: 3,
    specialties: [],
  },
  {
    slug: "lab",
    name: "Laboratoire d'analyses",
    nameAr: "مخبر تحاليل",
    isIndividual: false,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 4,
    specialties: [
      "Biochimie",
      "Hématologie",
      "Microbiologie",
      "Sérologie",
      "Anatomopathologie",
      "Toxicologie",
    ],
  },
  {
    slug: "imaging",
    name: "Centre d'imagerie",
    nameAr: "مركز التصوير الطبي",
    isIndividual: false,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 5,
    specialties: ["Radiologie", "Scanner", "IRM", "Échographie", "Mammographie"],
  },
] as const;

const PLANS = [
  {
    slug: "essentiel",
    name: "Essentiel",
    priceDzd: 0,
    billingPeriodMonths: 1,
    description:
      "Profil visible dans la recherche. Tarif non arrêté par le client.",
    features: "profile,search_listing",
    sortOrder: 1,
  },
  {
    slug: "cabinet",
    name: "Cabinet",
    priceDzd: 4500,
    billingPeriodMonths: 1,
    description:
      "Prise de rendez-vous en ligne et gestion d'équipe. Tarif indicatif, non arrêté.",
    features: "profile,search_listing,online_booking,multi_member,analytics",
    sortOrder: 2,
  },
  {
    slug: "visibilite",
    name: "Visibilité",
    priceDzd: 9000,
    billingPeriodMonths: 1,
    description:
      "Mise en avant signalée dans les résultats. Tarif indicatif, non arrêté.",
    features:
      "profile,search_listing,online_booking,multi_member,analytics,featured_placement",
    sortOrder: 3,
  },
] as const;

// Common Algerian family names. Paired with invented practice details; no entry
// refers to a real practitioner.
const FAMILY_NAMES = [
  "Benali", "Haddad", "Boumediene", "Cherif", "Zerrouki", "Belkacem",
  "Mansouri", "Bouzid", "Lounis", "Hamdi", "Meziane", "Saadi", "Kaddour",
  "Brahimi", "Ferhat", "Slimani", "Bouchama", "Amrani", "Djelloul", "Tahar",
  "Rahmani", "Bensalem", "Ouali", "Kadri", "Merabet", "Yahiaoui", "Bouras",
  "Naceri", "Larbi", "Guerrouj", "Bendjelloul", "Sahraoui",
];
const FIRST_NAMES_M = [
  "Karim", "Yacine", "Amine", "Sofiane", "Riad", "Mehdi", "Nabil", "Farid",
  "Samir", "Toufik", "Hakim", "Redouane", "Bilal", "Adel", "Djamel",
];
const FIRST_NAMES_F = [
  "Amina", "Nadia", "Leila", "Samira", "Yasmine", "Meriem", "Sabrina",
  "Hanane", "Karima", "Souad", "Nawal", "Lynda", "Feriel", "Assia",
];

const PHARMACY_PREFIXES = [
  "Pharmacie Centrale", "Pharmacie El Amel", "Pharmacie Ennour",
  "Pharmacie du Centre", "Pharmacie El Chifa", "Pharmacie Es Salam",
  "Pharmacie El Hayat", "Pharmacie Ibn Sina", "Pharmacie El Farabi",
];
const LAB_PREFIXES = [
  "Laboratoire El Chifa", "Laboratoire Ibn Rochd", "Laboratoire El Manar",
  "Laboratoire Bio Santé", "Laboratoire El Baraka", "Laboratoire Analytis",
];
const IMAGING_PREFIXES = [
  "Centre d'Imagerie El Nour", "Centre Radiologique Ibn Sina",
  "Imagerie Médicale du Centre", "Centre IRM El Hikma",
  "Centre d'Imagerie El Wafa",
];

const STREETS = [
  "Rue Didouche Mourad", "Boulevard Colonel Amirouche", "Rue Larbi Ben M'hidi",
  "Avenue de l'ALN", "Rue Abane Ramdane", "Boulevard Zighoud Youcef",
  "Rue des Frères Bouadou", "Cité 500 Logements", "Avenue Émir Abdelkader",
  "Rue Hassiba Ben Bouali",
];

/** Wilayas that carry the bulk of the demo population, per the brief. */
const FOCUS_WILAYAS = [16, 31, 25, 19, 9, 23, 39, 6, 15, 5, 22, 21];

async function main() {
  console.log("Clearing existing data…");
  await db.activityLog.deleteMany();
  await db.notification.deleteMany();
  await db.verificationRequest.deleteMany();
  await db.sponsorship.deleteMany();
  await db.subscription.deleteMany();
  await db.favorite.deleteMany();
  await db.appointment.deleteMany();
  await db.timeOff.deleteMany();
  await db.openingHours.deleteMany();
  await db.service.deleteMany();
  await db.partnerMember.deleteMany();
  await db.partner.deleteMany();
  await db.specialty.deleteMany();
  await db.partnerCategory.deleteMany();
  await db.plan.deleteMany();
  await db.user.deleteMany();
  await db.commune.deleteMany();
  await db.wilaya.deleteMany();

  // --- Geography ---------------------------------------------------------
  const geo: Geography = JSON.parse(
    readFileSync(resolve(process.cwd(), "data/generated/geography.json"), "utf8"),
  );

  console.log(`Seeding ${geo.wilayas.length} wilayas…`);
  await db.wilaya.createMany({
    data: geo.wilayas.map((w) => ({
      code: w.code,
      name: w.name,
      nameAr: w.nameAr,
      nameBer: w.nameBer,
      lat: w.lat,
      lng: w.lng,
      phoneCodes: w.phoneCodes.join(","),
      postalCodes: w.postalCodes.join(","),
      promotedFrom: w.promotedFrom ?? null,
    })),
  });

  console.log(`Seeding ${geo.communes.length} communes…`);
  await db.commune.createMany({
    data: geo.communes.map((c) => ({
      code: c.code,
      wilayaCode: c.wilayaCode,
      daira: c.daira,
      name: c.name,
      nameAr: c.nameAr,
    })),
  });

  // --- Categories and specialties ---------------------------------------
  console.log("Seeding categories and specialties…");
  const categoryBySlug = new Map<string, string>();
  const specialtiesByCategory = new Map<string, { id: string; name: string }[]>();

  for (const category of CATEGORIES) {
    const created = await db.partnerCategory.create({
      data: {
        slug: category.slug,
        name: category.name,
        nameAr: category.nameAr,
        isIndividual: category.isIndividual,
        supportsAppointments: category.supportsAppointments,
        supportsOpeningHours: category.supportsOpeningHours,
        sortOrder: category.sortOrder,
      },
    });
    categoryBySlug.set(category.slug, created.id);

    const specialties: { id: string; name: string }[] = [];
    for (const name of category.specialties) {
      const slug = `${category.slug}-${name
        .toLowerCase()
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/(^-|-$)/g, "")}`;
      const spec = await db.specialty.create({
        data: { slug, name, categoryId: created.id },
      });
      specialties.push({ id: spec.id, name: spec.name });
    }
    specialtiesByCategory.set(category.slug, specialties);
  }

  // --- Plans -------------------------------------------------------------
  console.log("Seeding plans…");
  const planBySlug = new Map<string, string>();
  for (const plan of PLANS) {
    const created = await db.plan.create({ data: plan });
    planBySlug.set(plan.slug, created.id);
  }

  // --- Accounts ----------------------------------------------------------
  console.log("Seeding accounts…");
  const admin = await db.user.create({
    data: {
      email: "admin@doctory.dz",
      passwordHash: hashPassword("doctory-demo"),
      firstName: "Yasmine",
      lastName: "Belkacem",
      role: "ADMIN",
      phone: "+213 21 00 00 00",
    },
  });

  const patients = [];
  for (let i = 0; i < 24; i += 1) {
    const female = rand() > 0.5;
    const firstName = pick(female ? FIRST_NAMES_F : FIRST_NAMES_M);
    const lastName = pick(FAMILY_NAMES);
    patients.push(
      await db.user.create({
        data: {
          email: `patient${i + 1}@exemple.dz`,
          passwordHash: hashPassword("doctory-demo"),
          firstName,
          lastName,
          role: "PATIENT",
          phone: `+213 5${Math.floor(rand() * 90000000 + 10000000)}`,
        },
      }),
    );
  }

  // --- Partners ----------------------------------------------------------
  console.log("Seeding partners…");
  const communesByWilaya = new Map<number, typeof geo.communes>();
  for (const commune of geo.communes) {
    const list = communesByWilaya.get(commune.wilayaCode) ?? [];
    list.push(commune);
    communesByWilaya.set(commune.wilayaCode, list);
  }
  const wilayaByCode = new Map(geo.wilayas.map((w) => [w.code, w]));

  const partnerIds: string[] = [];
  const usedSlugs = new Set<string>();

  function uniqueSlug(base: string) {
    const root = base
      .toLowerCase()
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/(^-|-$)/g, "");
    let slug = root;
    let n = 2;
    while (usedSlugs.has(slug)) {
      slug = `${root}-${n}`;
      n += 1;
    }
    usedSlugs.add(slug);
    return slug;
  }

  // Distribution: the focus wilayas get a dense population so search, map and
  // "near me" have something real to show; every other wilaya gets a couple so
  // no region reads as empty.
  const plan: { wilayaCode: number; count: number }[] = [];
  for (const w of geo.wilayas) {
    plan.push({
      wilayaCode: w.code,
      count: FOCUS_WILAYAS.includes(w.code) ? 14 : 2,
    });
  }

  for (const { wilayaCode, count } of plan) {
    const wilaya = wilayaByCode.get(wilayaCode);
    const communes = communesByWilaya.get(wilayaCode) ?? [];
    if (!wilaya || communes.length === 0) continue;

    for (let i = 0; i < count; i += 1) {
      const category = pick(CATEGORIES);
      const commune = pick(communes);
      const specialties = specialtiesByCategory.get(category.slug) ?? [];
      const specialty = specialties.length > 0 ? pick(specialties) : null;

      const female = rand() > 0.5;
      const firstName = pick(female ? FIRST_NAMES_F : FIRST_NAMES_M);
      const lastName = pick(FAMILY_NAMES);

      let displayName: string;
      let businessName: string | null = null;
      let personFirst: string | null = null;
      let personLast: string | null = null;

      if (category.isIndividual) {
        personFirst = firstName;
        personLast = lastName;
        displayName = `Dr ${firstName} ${lastName}`;
      } else {
        const prefix =
          category.slug === "pharmacy"
            ? pick(PHARMACY_PREFIXES)
            : category.slug === "lab"
              ? pick(LAB_PREFIXES)
              : pick(IMAGING_PREFIXES);
        businessName = `${prefix} — ${commune.name}`;
        displayName = businessName;
      }

      // Scatter partners within roughly 12 km of the wilaya centre so distance
      // sorting and the map have plausible spread without claiming a real address.
      const lat = wilaya.lat + (rand() - 0.5) * 0.2;
      const lng = wilaya.lng + (rand() - 0.5) * 0.2;

      const roll = rand();
      const status = roll > 0.92 ? "PENDING" : roll > 0.88 ? "SUSPENDED" : "ACTIVE";
      const verifyRoll = rand();
      const verificationStatus =
        status !== "ACTIVE"
          ? verifyRoll > 0.5
            ? "PENDING"
            : "UNVERIFIED"
          : verifyRoll > 0.35
            ? "VERIFIED"
            : verifyRoll > 0.15
              ? "UNVERIFIED"
              : "PENDING";

      const partner = await db.partner.create({
        data: {
          slug: uniqueSlug(displayName),
          firstName: personFirst,
          lastName: personLast,
          businessName,
          displayName,
          categoryId: categoryBySlug.get(category.slug)!,
          specialtyId: specialty?.id ?? null,
          bio: category.isIndividual
            ? `${specialty?.name ?? "Praticien"} installé${female ? "e" : ""} à ${commune.name}. Consultations sur rendez-vous.`
            : `${category.name} à ${commune.name}, wilaya de ${wilaya.name}.`,
          wilayaCode: wilaya.code,
          communeCode: commune.code,
          address: `${Math.floor(rand() * 120) + 1}, ${pick(STREETS)}, ${commune.name}`,
          lat,
          lng,
          phone: `+213 ${wilaya.phoneCodes[0] ?? 21} ${Math.floor(rand() * 900000 + 100000)}`,
          email: null,
          status,
          verificationStatus,
          verifiedAt: verificationStatus === "VERIFIED" ? new Date() : null,
          slotDurationMinutes: pick([15, 20, 30, 30, 45]),
        },
      });
      partnerIds.push(partner.id);

      // Opening hours: Algerian practices commonly split the day and close
      // Friday. Weekday 5 (Friday) is skipped; 6 (Saturday) is a working day.
      for (const weekday of [0, 1, 2, 3, 4, 6]) {
        if (weekday === 6 && rand() > 0.6) continue;
        await db.openingHours.create({
          data: {
            partnerId: partner.id,
            weekday,
            opensAt: "08:00",
            closesAt: "12:00",
          },
        });
        if (rand() > 0.25) {
          await db.openingHours.create({
            data: {
              partnerId: partner.id,
              weekday,
              opensAt: "14:00",
              closesAt: category.slug === "pharmacy" ? "19:00" : "17:00",
            },
          });
        }
      }

      // Services
      const serviceNames =
        specialties.length > 0
          ? pickSome(
              specialties.map((s) => s.name),
              Math.min(3, specialties.length),
            )
          : ["Conseil pharmaceutique", "Vente de médicaments"];
      for (const name of serviceNames) {
        await db.service.create({
          data: {
            partnerId: partner.id,
            name,
            priceDzd: rand() > 0.4 ? pick([1500, 2000, 2500, 3000, 4000]) : null,
            durationMinutes: pick([15, 20, 30, 45]),
          },
        });
      }

      // Subscription
      if (status === "ACTIVE") {
        const planSlug =
          rand() > 0.75 ? "visibilite" : rand() > 0.4 ? "cabinet" : "essentiel";
        const expired = rand() > 0.85;
        const startsAt = new Date(Date.now() - Math.floor(rand() * 200) * 86400000);
        await db.subscription.create({
          data: {
            partnerId: partner.id,
            planId: planBySlug.get(planSlug)!,
            status: expired ? "EXPIRED" : "ACTIVE",
            startsAt,
            expiresAt: new Date(
              startsAt.getTime() + (expired ? 30 : 365) * 86400000,
            ),
            paymentMethod: "mock",
            paymentReference: `MOCK-${randomUUID().slice(0, 8).toUpperCase()}`,
          },
        });

        // Sponsorship only for the plan that sells placement, and always inside
        // an explicit window so results can be labelled at render time.
        if (planSlug === "visibilite") {
          await db.sponsorship.create({
            data: {
              partnerId: partner.id,
              startsAt: new Date(Date.now() - 7 * 86400000),
              endsAt: new Date(Date.now() + 60 * 86400000),
              weight: Math.floor(rand() * 5) + 1,
            },
          });
        }
      }

      // A professional account owning the partner.
      const owner = await db.user.create({
        data: {
          email: `pro-${partner.slug}@doctory.dz`,
          passwordHash: hashPassword("doctory-demo"),
          firstName: personFirst ?? "Gérant",
          lastName: personLast ?? wilaya.name,
          role: "PROFESSIONAL",
          phone: partner.phone,
        },
      });
      await db.partnerMember.create({
        data: { userId: owner.id, partnerId: partner.id, role: "OWNER" },
      });
    }
  }

  console.log(`Seeded ${partnerIds.length} partners.`);

  // --- Appointments ------------------------------------------------------
  console.log("Seeding appointments…");
  const bookable = await db.partner.findMany({
    where: { status: "ACTIVE", category: { supportsAppointments: true } },
    select: { id: true, slotDurationMinutes: true },
    take: 200,
  });

  const statuses = [
    "PENDING", "CONFIRMED", "CONFIRMED", "COMPLETED", "COMPLETED",
    "CANCELLED", "NO_SHOW",
  ] as const;

  for (let i = 0; i < 320; i += 1) {
    const partner = pick(bookable);
    const patient = pick(patients);
    const status = pick(statuses);
    // Past for settled statuses, future for live ones, so the demo's dashboards
    // are internally consistent rather than showing completed future visits.
    const settled = status === "COMPLETED" || status === "NO_SHOW";
    const dayOffset = settled
      ? -Math.floor(rand() * 90) - 1
      : Math.floor(rand() * 30) - (status === "CANCELLED" ? 15 : 0);
    const startAt = new Date();
    startAt.setDate(startAt.getDate() + dayOffset);
    startAt.setHours(8 + Math.floor(rand() * 8), rand() > 0.5 ? 30 : 0, 0, 0);
    const endAt = new Date(
      startAt.getTime() + partner.slotDurationMinutes * 60000,
    );

    await db.appointment.create({
      data: {
        partnerId: partner.id,
        patientId: patient.id,
        startAt,
        endAt,
        status,
        reason: pick([
          "Consultation de contrôle",
          "Première consultation",
          "Suivi de traitement",
          "Bilan annuel",
          "Douleurs persistantes",
        ]),
        cancelledAt: status === "CANCELLED" ? new Date() : null,
        cancellationReason:
          status === "CANCELLED" ? pick(["Empêchement", "Reporté"]) : null,
      },
    });
  }

  // --- Favourites --------------------------------------------------------
  const activePartners = await db.partner.findMany({
    where: { status: "ACTIVE" },
    select: { id: true },
    take: 400,
  });
  for (const patient of patients) {
    for (const partner of pickSome(activePartners, Math.floor(rand() * 5))) {
      await db.favorite.create({
        data: { userId: patient.id, partnerId: partner.id },
      });
    }
  }

  // --- Admin activity ----------------------------------------------------
  const recentPartners = await db.partner.findMany({
    select: { id: true, displayName: true },
    take: 30,
  });
  for (const partner of recentPartners) {
    await db.activityLog.create({
      data: {
        action: pick([
          "partner.verified",
          "partner.registered",
          "subscription.changed",
        ]),
        summary: `${partner.displayName} — action administrative de démonstration`,
        targetType: "partner",
        targetId: partner.id,
        actorId: admin.id,
        createdAt: new Date(Date.now() - Math.floor(rand() * 30) * 86400000),
      },
    });
  }

  const counts = {
    wilayas: await db.wilaya.count(),
    communes: await db.commune.count(),
    partners: await db.partner.count(),
    users: await db.user.count(),
    appointments: await db.appointment.count(),
  };
  console.log("Done:", counts);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await db.$disconnect();
  });
