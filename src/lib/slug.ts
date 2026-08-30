import { db } from "@/lib/db";

/**
 * Turns a display name into a URL fragment.
 *
 * Accents are stripped rather than percent-encoded: a profile URL is shared by
 * message and read aloud, and "dr-mehdi-benali" survives that where
 * "dr-m%C3%A9hdi" does not. Arabic names transliterated by the practitioner
 * themselves are kept as typed; what this cannot romanise it drops, which is
 * why the caller must handle an empty result.
 */
export function slugify(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
}

/**
 * A slug no existing partner holds.
 *
 * Checked against the database rather than an in-memory set, because these are
 * created one registration at a time by people who may well share a name. The
 * unique index on Partner.slug is still the authority; this only keeps the
 * common case from reaching it as an error.
 */
export async function uniquePartnerSlug(base: string) {
  const root = slugify(base) || "partenaire";
  let slug = root;
  let n = 2;
  // Fifty is far past any real collision; past that a suffix from the id keeps
  // it terminating rather than spinning.
  while (n < 50) {
    const taken = await db.partner.findUnique({
      where: { slug },
      select: { id: true },
    });
    if (!taken) return slug;
    slug = `${root}-${n}`;
    n += 1;
  }
  return `${root}-${Date.now().toString(36)}`;
}
