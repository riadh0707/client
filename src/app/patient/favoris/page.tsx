import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { PartnerCard } from "@/components/partner-card";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { resolveOpenState, type Interval } from "@/lib/hours";
import { RodMark } from "@/components/rod-mark";

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
          status: true,
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

  // A partner favourited while listed can be suspended afterwards. Rendering the
  // card anyway gave a patient a result that led to a 404; dropping the row
  // silently would leave them wondering where it went. So the listed ones are
  // cards, and the rest are named as no longer listed.
  const listed = favorites.filter(({ partner }) => partner.status === "ACTIVE");
  const unlisted = favorites.filter(({ partner }) => partner.status !== "ACTIVE");

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Favoris
      </h1>
      <p className="mt-2 text-[15px] text-ink-600">
        {listed.length} professionnel{listed.length === 1 ? "" : "s"} et
        établissement{listed.length === 1 ? "" : "s"} enregistré
        {listed.length === 1 ? "" : "s"}.
      </p>

      {favorites.length === 0 ? (
        <div className="mt-6 bg-enamel-50 px-5 py-12 text-center">
          <RodMark className="mx-auto block h-12 w-12 text-enamel-300" />
          <p className="mt-4 font-display text-lg font-bold text-ink-900">
            Aucun favori
          </p>
          <p className="mx-auto mt-1.5 max-w-sm text-[15px] leading-relaxed text-ink-600">
            Enregistrez un professionnel depuis son profil pour le retrouver ici.
          </p>
          <Link
            href="/recherche"
            className="mt-5 inline-flex min-h-11 items-center border border-rod-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-rod-700 uppercase hover:bg-rod-100"
          >
            Chercher un professionnel
          </Link>
        </div>
      ) : (
        <div className="mt-6 flex flex-col gap-px bg-ink-900/10">
          {listed.map(({ partner }) => (
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

      {unlisted.length > 0 && (
        <section className="mt-8">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Plus référencés
          </h2>
          <ul className="ruled mt-2 border-y border-enamel-300 text-[15px] text-ink-600">
            {unlisted.map(({ partner }) => (
              <li key={partner.id} className="py-3">
                <span className="font-display font-bold text-ink-900">
                  {partner.displayName}
                </span>{" "}
                — {partner.commune.name}, {partner.wilaya.name}
                <span className="block text-sm text-ink-500">
                  Cette fiche n&apos;est plus publiée sur DOCTORY. Son numéro
                  reste valable&nbsp;: {partner.phone}
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}
    </main>
  );
}
