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
import { PrismaPg } from "@prisma/adapter-pg";
import { PrismaClient } from "../src/generated/prisma/client.js";

const connectionString = process.env.DATABASE_URL;
if (!connectionString) {
  throw new Error(
    "DATABASE_URL n'est pas défini. Renseignez la chaîne de connexion PostgreSQL avant de peupler la base.",
  );
}
const db = new PrismaClient({ adapter: new PrismaPg({ connectionString }) });

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

/**
 * How a practitioner of this category is addressed in Algeria.
 *
 * "Dr" is not universal: a midwife is never "Dr", and a physiotherapist or a
 * nutritionist is addressed by "M." or "Mme". Printing "Dr Amina Belkacem" over
 * a sage-femme's profile would be the kind of absurd demo data the brief rules
 * out, so the title is per-category and per-gender rather than a constant.
 */
function practitionerTitle(categorySlug: string, female: boolean) {
  if (categorySlug === "midwife") return female ? "Mme" : "M.";
  if (categorySlug === "physio" || categorySlug === "nutritionist") {
    return female ? "Mme" : "M.";
  }
  // Doctors, dentists and psychologists keep "Dr", which is customary for all
  // three here.
  return "Dr";
}

/**
 * The noun for a practitioner of a discipline: "Cardiologie" -> "Cardiologue".
 * Taken from the first search alias, which is exactly that word.
 */
function practitionerNoun(specialtyName: string | undefined) {
  if (!specialtyName) return "Praticien";
  const first = SPECIALTY_ALIASES[specialtyName]?.split(",")[0];
  if (!first) return specialtyName;
  return first.charAt(0).toUpperCase() + first.slice(1);
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

/**
 * Search synonyms per specialty slug. Patients search for the practitioner
 * ("cardiologue"), not the discipline ("Cardiologie"); the brief's own example
 * queries are written that way. Plural forms are included because SQLite's LIKE
 * does no stemming.
 */
const SPECIALTY_ALIASES: Record<string, string> = {
  "Médecine générale": "généraliste,generaliste,médecin généraliste,omnipraticien",
  Cardiologie: "cardiologue,cardiologues,cœur,coeur",
  Pédiatrie: "pédiatre,pediatre,pédiatres,enfant,enfants",
  Gynécologie: "gynécologue,gynecologue,gynécologues,obstétrique",
  Dermatologie: "dermatologue,dermatologues,peau",
  Ophtalmologie: "ophtalmologue,ophtalmo,oculiste,yeux,vue",
  "Oto-rhino-laryngologie": "orl,oto-rhino,gorge,oreille,nez",
  "Gastro-entérologie": "gastro-entérologue,gastroentérologue,gastro,estomac",
  Endocrinologie: "endocrinologue,diabète,diabete,thyroïde",
  Neurologie: "neurologue,neurologues,nerfs",
  Pneumologie: "pneumologue,poumon,poumons,respiratoire",
  Rhumatologie: "rhumatologue,articulations,rhumatisme",
  Psychiatrie: "psychiatre,psychiatres,santé mentale",
  "Chirurgie générale": "chirurgien,chirurgiens",
  Orthopédie: "orthopédiste,orthopediste,os,fracture",
  Urologie: "urologue,urologues,reins",
  Néphrologie: "néphrologue,nephrologue,rein,reins,dialyse",
  "Dentisterie générale": "dentiste,dentistes,chirurgien-dentiste,dents",
  Orthodontie: "orthodontiste,appareil dentaire,bagues",
  Implantologie: "implant,implants,implantologue",
  Parodontologie: "parodontiste,gencives",
  "Chirurgie dentaire": "chirurgien-dentiste,extraction",
  Pédodontie: "dentiste enfant,pédodontiste",
  Biochimie: "analyses,bilan sanguin,prise de sang",
  Hématologie: "sang,numération,nfs",
  Microbiologie: "bactériologie,prélèvement",
  Sérologie: "sérologie,anticorps",
  Anatomopathologie: "biopsie,anapath",
  Toxicologie: "toxicologie,dépistage",
  Radiologie: "radiologue,radio,radiographie",
  Scanner: "scanner,tdm,tomodensitométrie",
  IRM: "irm,résonance magnétique",
  Échographie: "échographie,echographie,écho,doppler",
  Mammographie: "mammographie,mammo,sein",
  // The categories added at the client's request. Without these, a patient
  // typing "kiné" or "psy" — which is what people actually type — gets nothing,
  // because the discipline is stored as "Rééducation fonctionnelle". The first
  // alias also supplies the practitioner noun used in profile text.
  "Rééducation fonctionnelle": "kinésithérapeute,kine,kiné,kinesitherapeute,rééducation,reeducation",
  "Kinésithérapie respiratoire": "kinésithérapeute respiratoire,kiné respiratoire,bronchiolite",
  "Rééducation sportive": "kinésithérapeute du sport,kiné sport,blessure sportive",
  "Rééducation neurologique": "kinésithérapeute neurologique,kiné neurologique,avc",
  "Psychologie clinique": "psychologue,psy,psychologues,psychologie",
  Psychothérapie: "psychothérapeute,psychotherapeute,psy,thérapie,therapie",
  "Psychologie de l'enfant": "psychologue pour enfant,psy enfant,pédopsychologue",
  "Thérapie de couple": "thérapeute de couple,conseiller conjugal,couple",
  "Suivi de grossesse": "sage-femme,sage femme,grossesse,enceinte,accouchement",
  "Préparation à la naissance": "sage-femme,préparation accouchement,naissance",
  "Suivi post-natal": "sage-femme,post-natal,après accouchement,nourrisson",
  "Rééducation périnéale": "sage-femme,périnée,perinee,rééducation périnéale",
  "Nutrition clinique": "nutritionniste,diététicien,dieteticien,nutrition,régime",
  "Diabétologie nutritionnelle": "nutritionniste,diabète,diabete,alimentation diabétique",
  "Nutrition sportive": "nutritionniste du sport,diététicien sportif,nutrition sport",
  "Nutrition pédiatrique": "nutritionniste enfant,diététicien enfant,alimentation enfant",
  "Maternité": "maternité,maternite,accouchement,clinique accouchement",
  "Cardiologie interventionnelle": "cardiologue interventionnel,coronarographie,angioplastie",
  Traumatologie: "traumatologue,traumatologie,fracture,urgence",
  "Soins intensifs": "réanimation,soins intensifs,usi",
  "Hospitalisation de jour": "hôpital de jour,hospitalisation,ambulatoire",
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
  // Added at the client's request. Each one is a row here and nothing else —
  // no migration, no branch in the interface — because the capability flags
  // carry what differs. A clinic is an establishment that books appointments;
  // a midwife is an individual who books them. That is the whole difference,
  // and the flags already express it.
  {
    slug: "clinic",
    name: "Clinique",
    nameAr: "عيادة",
    isIndividual: false,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 6,
    specialties: [
      "Chirurgie générale",
      "Maternité",
      "Cardiologie interventionnelle",
      "Traumatologie",
      "Soins intensifs",
      "Hospitalisation de jour",
    ],
  },
  {
    slug: "physio",
    name: "Kinésithérapeute",
    nameAr: "أخصائي العلاج الطبيعي",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 7,
    specialties: [
      "Rééducation fonctionnelle",
      "Kinésithérapie respiratoire",
      "Rééducation sportive",
      "Rééducation neurologique",
    ],
  },
  {
    slug: "psychologist",
    name: "Psychologue",
    nameAr: "أخصائي نفساني",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 8,
    specialties: [
      "Psychologie clinique",
      "Psychothérapie",
      "Psychologie de l'enfant",
      "Thérapie de couple",
    ],
  },
  {
    slug: "midwife",
    name: "Sage-femme",
    nameAr: "قابلة",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 9,
    specialties: [
      "Suivi de grossesse",
      "Préparation à la naissance",
      "Suivi post-natal",
      "Rééducation périnéale",
    ],
  },
  {
    slug: "nutritionist",
    name: "Nutritionniste",
    nameAr: "أخصائي التغذية",
    isIndividual: true,
    supportsAppointments: true,
    supportsOpeningHours: true,
    sortOrder: 10,
    specialties: [
      "Nutrition clinique",
      "Diabétologie nutritionnelle",
      "Nutrition sportive",
      "Nutrition pédiatrique",
    ],
  },
] as const;

/**
 * What a partner actually sells, per category. Drawing services from the
 * category's whole specialty list produced nonsense — a cardiologist listing
 * "Gastro-entérologie" and "Urologie" as services — which the brief rules out.
 * Individual practitioners get consultation types plus their own discipline;
 * establishments get the acts they perform.
 */
const SERVICE_POOLS: Record<string, string[]> = {
  doctor: [
    "Consultation",
    "Consultation de suivi",
    "Certificat médical",
    "Bilan de santé",
  ],
  dentist: [
    "Consultation",
    "Détartrage",
    "Soin de carie",
    "Extraction",
    "Radiographie dentaire",
  ],
  pharmacy: [
    "Conseil pharmaceutique",
    "Délivrance sur ordonnance",
    "Prise de tension",
    "Matériel médical",
  ],
  lab: [
    "Prélèvement sanguin",
    "Bilan biologique complet",
    "Test de glycémie",
    "Sérologie",
    "Prélèvement à domicile",
  ],
  imaging: [
    "Radiographie standard",
    "Échographie abdominale",
    "Scanner",
    "IRM",
    "Mammographie",
  ],
  clinic: [
    "Consultation spécialisée",
    "Hospitalisation de jour",
    "Bloc opératoire",
    "Urgences",
    "Bilan pré-opératoire",
  ],
  physio: [
    "Séance de rééducation",
    "Bilan kinésithérapique",
    "Massage thérapeutique",
    "Rééducation post-opératoire",
  ],
  psychologist: [
    "Consultation individuelle",
    "Bilan psychologique",
    "Séance de suivi",
    "Consultation familiale",
  ],
  midwife: [
    "Consultation de suivi de grossesse",
    "Séance de préparation à la naissance",
    "Consultation post-natale",
    "Suivi du nourrisson",
  ],
  nutritionist: [
    "Bilan nutritionnel",
    "Consultation de suivi",
    "Programme alimentaire",
    "Suivi du poids",
  ],
};

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

const CLINIC_PREFIXES = [
  "Clinique El Amel", "Clinique Ibn Rochd", "Clinique El Yasmine",
  "Clinique El Feth", "Clinique Es-Salem", "Polyclinique El Nour",
];

const STREETS = [
  "Rue Didouche Mourad", "Boulevard Colonel Amirouche", "Rue Larbi Ben M'hidi",
  "Avenue de l'ALN", "Rue Abane Ramdane", "Boulevard Zighoud Youcef",
  "Rue des Frères Bouadou", "Cité 500 Logements", "Avenue Émir Abdelkader",
  "Rue Hassiba Ben Bouali",
];

/**
 * Wilayas carrying the bulk of the demo population, with the extra doctors each
 * receives on top of the guaranteed per-category coverage.
 *
 * The weights are deliberately uneven: a "top ten wilayas" chart where every row
 * shows the same number is not a ranking, and real coverage concentrates in
 * Alger and Oran before spreading.
 */
const FOCUS_WILAYAS_WEIGHTED: [number, number][] = [
  [16, 12], // Alger
  [31, 9], // Oran
  [25, 7], // Constantine
  [19, 5], // Sétif
  [9, 4], // Blida
  [23, 4], // Annaba
  [6, 3], // Béjaïa
  [15, 3], // Tizi Ouzou
  [5, 2], // Batna
  [39, 2], // El Oued
  [22, 1], // Sidi Bel Abbès
  [21, 1], // Skikda
];
const FOCUS_WILAYAS = FOCUS_WILAYAS_WEIGHTED.map(([code]) => code);

/**
 * Creation dates spread over the past 14 months, weighted toward recent months.
 *
 * Seeding everything with "now" made the growth charts meaningless: a flat line
 * at zero followed by a vertical spike on the last point. A platform that has
 * been running has a history, and the demo has to show one.
 */
function pastCreationDate() {
  const daysAgo = Math.floor(420 * rand() ** 1.7);
  return new Date(Date.now() - daysAgo * 86400000);
}

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
        data: {
          slug,
          name,
          categoryId: created.id,
          aliases: SPECIALTY_ALIASES[name] ?? null,
        },
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
  for (let i = 0; i < 90; i += 1) {
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
          createdAt: pastCreationDate(),
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

  // Distribution: the focus wilayas get every category, dealt round-robin rather
  // than drawn at random. Random draws left holes — the first build produced a
  // single pharmacy in Alger and it was not even active, so "Pharmacie à Alger",
  // one of the brief's own example searches, returned nothing. Guaranteeing three
  // of each category per focus wilaya, the first two forced active, makes every
  // example query in the brief resolve.
  const plan: { wilayaCode: number; categories: (typeof CATEGORIES)[number][] }[] =
    [];
  for (const w of geo.wilayas) {
    if (FOCUS_WILAYAS.includes(w.code)) {
      const dealt: (typeof CATEGORIES)[number][] = [];
      // Doctors carry 17 specialties, so three of them leaves most
      // specialty+wilaya pairs empty — "Cardiologue à Oran" among them. Six
      // doctors and four dentists, with specialties dealt round-robin below,
      // makes the common disciplines present in every dense wilaya.
      const roundsByCategory: Record<string, number> = {
        doctor: 6,
        dentist: 4,
        pharmacy: 3,
        lab: 3,
        imaging: 3,
      };
      const extraDoctors =
        FOCUS_WILAYAS_WEIGHTED.find(([code]) => code === w.code)?.[1] ?? 0;
      for (const category of CATEGORIES) {
        const rounds =
          (roundsByCategory[category.slug] ?? 3) +
          (category.slug === "doctor" ? extraDoctors : 0);
        for (let round = 0; round < rounds; round += 1) {
          dealt.push(category);
        }
      }
      plan.push({ wilayaCode: w.code, categories: dealt });
    } else {
      plan.push({
        wilayaCode: w.code,
        categories: [pick(CATEGORIES), pick(CATEGORIES)],
      });
    }
  }

  for (const { wilayaCode, categories: dealtCategories } of plan) {
    const isFocus = FOCUS_WILAYAS.includes(wilayaCode);
    const wilaya = wilayaByCode.get(wilayaCode);
    const communes = communesByWilaya.get(wilayaCode) ?? [];
    if (!wilaya || communes.length === 0) continue;

    // Counts how many of each category have been created in this wilaya, so the
    // first two of each can be forced active.
    const madeInWilaya = new Map<string, number>();

    for (let i = 0; i < dealtCategories.length; i += 1) {
      const category = dealtCategories[i];
      const madeSoFar = madeInWilaya.get(category.slug) ?? 0;
      madeInWilaya.set(category.slug, madeSoFar + 1);
      const forceActive = madeSoFar < 2;
      const commune = pick(communes);
      const specialties = specialtiesByCategory.get(category.slug) ?? [];
      // Round-robin in the dense wilayas guarantees specialty spread; elsewhere
      // a random draw is fine because those wilayas hold only two partners.
      const specialty =
        specialties.length === 0
          ? null
          : isFocus
            ? specialties[madeSoFar % specialties.length]
            : pick(specialties);

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
        displayName = `${practitionerTitle(category.slug, female)} ${firstName} ${lastName}`;
      } else {
        const prefix =
          category.slug === "pharmacy"
            ? pick(PHARMACY_PREFIXES)
            : category.slug === "lab"
              ? pick(LAB_PREFIXES)
              : category.slug === "clinic"
                ? pick(CLINIC_PREFIXES)
                : pick(IMAGING_PREFIXES);
        const isDuty =
          category.slug === "pharmacy" &&
          FOCUS_WILAYAS.includes(wilaya.code) &&
          madeSoFar === 0;
        businessName = isDuty
          ? `${prefix} — ${commune.name} (garde)`
          : `${prefix} — ${commune.name}`;
        displayName = businessName;
      }

      // Scatter partners within roughly 12 km of the wilaya centre so distance
      // sorting and the map have plausible spread without claiming a real address.
      const lat = wilaya.lat + (rand() - 0.5) * 0.2;
      const lng = wilaya.lng + (rand() - 0.5) * 0.2;

      const roll = rand();
      // A share of pending and suspended partners is what makes the admin
      // moderation queue worth looking at, but it must not eat the guaranteed
      // coverage the focus wilayas exist to provide.
      const status = forceActive
        ? "ACTIVE"
        : roll > 0.92
          ? "PENDING"
          : roll > 0.88
            ? "SUSPENDED"
            : "ACTIVE";
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
          // "Cardiologie installé à …" is not French. The first alias is the
          // practitioner noun ("cardiologue"); the discipline name is only a
          // fallback for specialties that have no alias.
          bio: category.isIndividual
            ? `${practitionerNoun(specialty?.name)} installé${female ? "e" : ""} à ${commune.name}. Consultations sur rendez-vous.`
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
          createdAt: pastCreationDate(),
        },
      });
      partnerIds.push(partner.id);

      // Pharmacies de garde are a real fixture of Algerian health provision and
      // the only partners open outside business hours. One per dense wilaya, so
      // "ouvert maintenant" returns something whatever the hour of the demo.
      const isDutyPharmacy =
        category.slug === "pharmacy" && isFocus && madeSoFar === 0;

      if (isDutyPharmacy) {
        for (let weekday = 0; weekday < 7; weekday += 1) {
          await db.openingHours.create({
            data: {
              partnerId: partner.id,
              weekday,
              opensAt: "00:00",
              closesAt: "23:59",
            },
          });
        }
      }

      // Opening hours: Algerian practices commonly split the day and close
      // Friday. Weekday 5 (Friday) is skipped; 6 (Saturday) is a working day.
      for (const weekday of isDutyPharmacy ? [] : [0, 1, 2, 3, 4, 6]) {
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
      // The partner's own discipline leads, then acts drawn from its category's
      // pool — never another specialty from the same category.
      const pool = SERVICE_POOLS[category.slug] ?? ["Consultation"];
      const serviceNames = [
        ...(specialty ? [specialty.name] : []),
        ...pickSome(pool, 3),
      ].filter((name, index, all) => all.indexOf(name) === index);
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

    // Booked some days before the visit, and never after it: without this every
    // appointment carries the seed's own timestamp and "rendez-vous ce mois"
    // reports the entire table.
    const bookedAt = new Date(
      startAt.getTime() - Math.floor(rand() * 21 + 1) * 86400000,
    );

    await db.appointment.create({
      data: {
        partnerId: partner.id,
        patientId: patient.id,
        startAt,
        endAt,
        status,
        createdAt: bookedAt,
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
