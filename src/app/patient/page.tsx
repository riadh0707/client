import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { AppointmentRow } from "@/components/appointment-row";
import { PartnerCard } from "@/components/partner-card";
import { SearchInstrument } from "@/components/search-instrument";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { resolveOpenState, type Interval } from "@/lib/hours";

export const metadata: Metadata = { title: "Espace patient" };
export const dynamic = "force-dynamic";

export default async function PatientDashboard() {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpatient");

  const now = new Date();

  const [upcoming, favorites, wilayas, unreadNotifications] = await Promise.all([
    db.appointment.findMany({
      where: {
        patientId: user.id,
        startAt: { gte: now },
        status: { in: ["PENDING", "CONFIRMED"] },
      },
      orderBy: { startAt: "asc" },
      take: 3,
      select: {
        id: true,
        startAt: true,
        status: true,
        reason: true,
        serviceName: true,
        partner: {
          select: {
            slug: true,
            displayName: true,
            commune: { select: { name: true } },
          },
        },
      },
    }),
    db.favorite.findMany({
      where: { userId: user.id },
      orderBy: { createdAt: "desc" },
      take: 4,
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
            openingHours: {
              select: { weekday: true, opensAt: true, closesAt: true },
            },
          },
        },
      },
    }),
    db.wilaya.findMany({
      select: { code: true, name: true },
      orderBy: { code: "asc" },
    }),
    db.notification.findMany({
      where: { userId: user.id, readAt: null },
      orderBy: { createdAt: "desc" },
      take: 3,
      select: { id: true, title: true, body: true, href: true },
    }),
  ]);

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Bonjour {user.firstName}
      </h1>

      {unreadNotifications.length > 0 && (
        <section className="mt-6">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Notifications
          </h2>
          <ul className="ruled mt-2 border-y border-enamel-300">
            {unreadNotifications.map((notification) => (
              <li key={notification.id} className="py-3">
                <Link
                  href={notification.href ?? "/patient/notifications"}
                  className="group flex items-start gap-3"
                >
                  <span
                    aria-hidden
                    className="mt-1.5 h-2 w-2 shrink-0 bg-azur-500"
                  />
                  <span>
                    <span className="font-display text-[15px] font-bold text-ink-900 group-hover:text-azur-700">
                      {notification.title}
                    </span>
                    <span className="block text-sm text-ink-600">
                      {notification.body}
                    </span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="mt-8">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Chercher un professionnel
        </h2>
        <div className="mt-3 border border-enamel-300">
          <SearchInstrument wilayas={wilayas} />
        </div>
      </section>

      <section className="mt-10">
        <div className="flex items-baseline justify-between gap-4">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Prochains rendez-vous
          </h2>
          <Link
            href="/patient/rendez-vous"
            className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:underline"
          >
            Tout voir
          </Link>
        </div>

        {upcoming.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Aucun rendez-vous à venir.
          </p>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {upcoming.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="patient"
              />
            ))}
          </div>
        )}
      </section>

      <section className="mt-10">
        <div className="flex items-baseline justify-between gap-4">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Favoris
          </h2>
          <Link
            href="/patient/favoris"
            className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:underline"
          >
            Tout voir
          </Link>
        </div>

        {favorites.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Aucun favori enregistré. Ajoutez-en depuis un profil.
          </p>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
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
      </section>
    </main>
  );
}
