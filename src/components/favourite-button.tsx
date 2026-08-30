import Link from "next/link";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";

async function toggleFavourite(formData: FormData) {
  "use server";

  const partnerId = String(formData.get("partnerId") ?? "");
  const slug = String(formData.get("slug") ?? "");

  const user = await getCurrentUser();
  if (!user) redirect(`/connexion?next=${encodeURIComponent(`/partenaire/${slug}`)}`);

  const existing = await db.favorite.findUnique({
    where: { userId_partnerId: { userId: user.id, partnerId } },
    select: { id: true },
  });

  if (existing) {
    await db.favorite.delete({ where: { id: existing.id } });
  } else {
    // The partner must exist and be listed: a favourite pointing at a suspended
    // or missing profile would render as a broken row in the patient's list.
    const partner = await db.partner.findFirst({
      where: { id: partnerId, status: "ACTIVE" },
      select: { id: true },
    });
    if (!partner) redirect(`/partenaire/${slug}`);
    await db.favorite.create({ data: { userId: user.id, partnerId } });
  }

  revalidatePath(`/partenaire/${slug}`);
  revalidatePath("/patient/favoris");
  redirect(`/partenaire/${slug}`);
}

/**
 * Adds or removes a partner from the signed-in patient's favourites.
 *
 * Signed out, it is a link to sign in rather than a button that fails — the
 * action is still visible, so the feature is discoverable before the account
 * exists.
 */
export async function FavouriteButton({
  partnerId,
  slug,
}: {
  partnerId: string;
  slug: string;
}) {
  const user = await getCurrentUser();

  if (!user) {
    return (
      <Link
        href={`/connexion?next=${encodeURIComponent(`/partenaire/${slug}`)}`}
        className="mt-3 flex min-h-11 items-center justify-center border border-enamel-300 px-3 py-2 text-center font-display text-xs font-bold tracking-[0.08em] text-ink-600 uppercase hover:border-azur-600 hover:text-azur-700"
      >
        Enregistrer dans mes favoris
      </Link>
    );
  }

  const isFavourite = Boolean(
    await db.favorite.findUnique({
      where: { userId_partnerId: { userId: user.id, partnerId } },
      select: { id: true },
    }),
  );

  return (
    <form action={toggleFavourite} className="mt-3">
      <input type="hidden" name="partnerId" value={partnerId} />
      <input type="hidden" name="slug" value={slug} />
      <button
        type="submit"
        aria-pressed={isFavourite}
        className={`min-h-11 w-full border px-3 py-2 font-display text-xs font-bold tracking-[0.08em] uppercase ${
          isFavourite
            ? "border-azur-700 bg-azur-100 text-azur-800 hover:bg-azur-200"
            : "border-enamel-300 text-ink-600 hover:border-azur-600 hover:text-azur-700"
        }`}
      >
        {isFavourite ? "★ Dans mes favoris" : "Enregistrer dans mes favoris"}
      </button>
    </form>
  );
}
