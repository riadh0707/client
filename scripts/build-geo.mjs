/**
 * Builds DOCTORY's canonical Algerian geography from the vendored leblad dataset.
 *
 * The source (dzcode-io/leblad, MIT) predates the 2019 administrative reform: it
 * carries 48 wilayas and marks the ten future ones as dairas named
 * "<NAME> (wilaya déléguée)". This script promotes those ten to full wilayas under
 * their official 2019 codes (49-58), moving their communes with them, so the app
 * ships today's 58-wilaya structure rather than the historic 48.
 *
 * Output: data/generated/geography.json — the single source the Prisma seed reads.
 */

import { readFileSync, writeFileSync, mkdirSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");

/**
 * Official 2019 promotions, keyed by the source daira code. `name` is the modern
 * French spelling used by the Algerian administration, which differs from the
 * source's transliteration in several cases (AIN SALAH -> In Salah).
 */
const PROMOTED = {
  1090: { code: 49, name: "Timimoun", nameAr: "تيميمون" },
  1250: { code: 50, name: "Bordj Badji Mokhtar", nameAr: "برج باجي مختار" },
  7050: { code: 51, name: "Ouled Djellal", nameAr: "أولاد جلال" },
  8070: { code: 52, name: "Béni Abbès", nameAr: "بني عباس" },
  1108: { code: 53, name: "In Salah", nameAr: "عين صالح" },
  1104: { code: 54, name: "In Guezzam", nameAr: "عين قزام" },
  3013: { code: 55, name: "Touggourt", nameAr: "تقرت" },
  3302: { code: 56, name: "Djanet", nameAr: "جانت" },
  3927: { code: 57, name: "El M'Ghair", nameAr: "المغير" },
  4702: { code: 58, name: "El Meniaa", nameAr: "المنيعة" },
};

/**
 * Four of the promoted wilayas carry no communes on their marker entry: their
 * territory still sits under the parent's ordinary dairas. Mapping those daira
 * codes across reproduces the official 2019 composition exactly — Timimoun 10
 * communes, Bordj Badji Mokhtar 2, Ouled Djellal 6, Béni Abbès 10 — and stops
 * them shipping as selectable but empty.
 *
 * The other six promoted wilayas keep only the communes their marker entry
 * carries; the source does not record which further dairas moved with them, and
 * inventing that mapping would be guesswork rather than reference data.
 */
const DAIRA_REASSIGNMENT = {
  109: 49, // Timimoun
  103: 49, // Charouine
  123: 49, // Aougrout
  116: 49, // Tinerkouk
  125: 50, // Bordj Badji Mokhtar
  705: 51, // Ouled Djellal
  708: 51, // Sidi Khaled
  807: 52, // Béni Abbès
  811: 52, // Igli
  812: 52, // Tabelbala
  814: 52, // El Ouata
  818: 52, // Kerzaz
  803: 52, // Ouled Khodeir
};

/**
 * The source stores names in caps ("OULED AHMED TIMMI"). Title-case them for
 * display while preserving the particles Algerian toponyms actually use, so we
 * get "Aïn El Hammam" and "Bordj Bou Arréridj", not "Aïn el hammam".
 */
function toTitleCase(input) {
  return input
    .toLowerCase()
    .split(/(\s|-|')/)
    .map((part) => {
      if (/^(\s|-|')$/.test(part)) return part;
      return part.charAt(0).toUpperCase() + part.slice(1);
    })
    .join("");
}

function cleanDairaName(name) {
  return toTitleCase(name.replace(/\s*\(wilaya déléguée\)\s*/i, "").trim());
}

const source = JSON.parse(
  readFileSync(resolve(ROOT, "data/source/leblad-WilayaList.json"), "utf8"),
);
const { coordinates } = JSON.parse(
  readFileSync(resolve(ROOT, "data/wilaya-coordinates.json"), "utf8"),
);

const wilayas = [];
const communes = [];
const warnings = [];

// Six of the ten promoted places appear twice in the source under one code: once
// as an ordinary daira and once carrying the "(wilaya déléguée)" marker. Both
// entries describe the same territory, so the wilaya is created once and the
// communes of either entry are routed to it.
const created = new Set();

for (const w of source) {
  wilayas.push({
    code: w.mattricule,
    name: w.name,
    nameAr: w.name_ar ?? null,
    nameBer: w.name_ber ?? null,
    phoneCodes: w.phoneCodes ?? [],
    postalCodes: w.postalCodes ?? [],
  });

  for (const daira of w.dairats ?? []) {
    const promotion = PROMOTED[daira.code];

    if (promotion && !created.has(promotion.code)) {
      created.add(promotion.code);
      wilayas.push({
        code: promotion.code,
        name: promotion.name,
        nameAr: promotion.nameAr,
        nameBer: null,
        // A promoted wilaya inherits its parent's dialling code; the source has
        // no separate entry for the new codes.
        phoneCodes: w.phoneCodes ?? [],
        postalCodes: [],
        promotedFrom: w.mattricule,
      });
    }

    const owningWilaya =
      promotion?.code ?? DAIRA_REASSIGNMENT[daira.code] ?? w.mattricule;
    const baladyiats = daira.baladyiats ?? [];

    if (baladyiats.length === 0) {
      warnings.push(
        `No communes in source for daira "${daira.name}" (wilaya ${w.name})`,
      );
      continue;
    }

    for (const commune of baladyiats) {
      communes.push({
        code: commune.code,
        wilayaCode: owningWilaya,
        daira: cleanDairaName(daira.name),
        name: toTitleCase(commune.name),
        nameAr: commune.name_ar ?? null,
      });
    }
  }
}

wilayas.sort((a, b) => a.code - b.code);
communes.sort((a, b) => a.code - b.code);

// Attach coordinates and fail loudly on a gap: a wilaya with no centre cannot be
// mapped or used for proximity, and silently shipping null would surface as a
// broken map rather than a build error.
const missingCoordinates = [];
for (const wilaya of wilayas) {
  const point = coordinates[String(wilaya.code)];
  if (!point) {
    missingCoordinates.push(`${wilaya.code} ${wilaya.name}`);
    continue;
  }
  wilaya.lat = point.lat;
  wilaya.lng = point.lng;
}

if (missingCoordinates.length > 0) {
  throw new Error(`Missing coordinates for: ${missingCoordinates.join(", ")}`);
}

const expectedWilayas = 58;
if (wilayas.length !== expectedWilayas) {
  throw new Error(
    `Expected ${expectedWilayas} wilayas after promotion, built ${wilayas.length}`,
  );
}

const output = {
  generatedFrom: "data/source/leblad-WilayaList.json (dzcode-io/leblad, MIT)",
  note: "Ten wilayas promoted to codes 49-58 per the 2019 administrative reform.",
  wilayas,
  communes,
};

mkdirSync(resolve(ROOT, "data/generated"), { recursive: true });
writeFileSync(
  resolve(ROOT, "data/generated/geography.json"),
  `${JSON.stringify(output, null, 2)}\n`,
);

console.log(`Wrote ${wilayas.length} wilayas and ${communes.length} communes.`);
if (warnings.length > 0) {
  console.log(`\n${warnings.length} source gaps (communes absent upstream):`);
  for (const warning of warnings) console.log(`  - ${warning}`);
}
