import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { PartnerCard } from "@/components/partner-card";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { resolveOpenState, type Interval } from "@/lib/hours";

export const metadata: Metadata = { title: "Favoris" };
export const dynamic = "force-dynamic";

export default async function FavouritesPage() {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpatient%2Ffavoris");

  const now = new Date();
  const favorites = await db.favorite.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: "desc" },
    select: {
      partner: {
        select: {
          id: true,
          slug: true,
          displayName: true,
          address: true,
          phone: true,
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
      },
    },
  });

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Favoris
      </h1>
      <p className="mt-2 text-[15px] text-ink-600">
        {favorites.length} professionnel{favorites.length === 1 ? "" : "s"} et
        établissement{favorites.length === 1 ? "" : "s"} enregistré
        {favorites.length === 1 ? "" : "s"}.
      </p>

      {favorites.length === 0 ? (
        <div className="mt-6 bg-enamel-50 px-5 py-12 text-center">
          <span aria-hidden className="cross-mark mx-auto block h-8 w-8 text-enamel-300" />
          <p className="mt-4 font-display text-lg font-bold text-ink-900">
            Aucun favori
          </p>
          <p className="mx-auto mt-1.5 max-w-sm text-[15px] leading-relaxed text-ink-600">
            Enregistrez un professionnel depuis son profil pour le retrouver ici.
          </p>
          <Link
            href="/recherche"
            className="mt-5 inline-block border border-cross-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100"
          >
            Chercher un professionnel
          </Link>
        </div>
      ) : (
        <div className="mt-6 flex flex-col gap-px bg-ink-900/10">
          {favorites.map(({ partner }) => (
            <PartnerCard
              key={partner.id}
              partner={{
                ...partner,
                openState: partner.category.supportsOpeningHours
                  ? resolveOpenState(partner.openingHours as Interval[], now)
                  : { status: "unknown" },
                distance: null,
              }}
            />
          ))}
        </div>
      )}
    </main>
  );
}
