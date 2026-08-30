import type { Metadata } from "next";
import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { SlotPicker } from "@/components/slot-picker";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { getAvailability, isSlotBookable } from "@/lib/slots";
import { zonedParts } from "@/lib/time";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const partner = await db.partner.findUnique({
    where: { slug },
    select: { displayName: true },
  });
  return { title: partner ? `Rendez-vous · ${partner.displayName}` : "Rendez-vous" };
}

async function book(formData: FormData) {
  "use server";

  const slug = String(formData.get("slug") ?? "");
  const startAtRaw = String(formData.get("startAt") ?? "");
  const reason = String(formData.get("reason") ?? "").trim();
  const serviceName = String(formData.get("serviceName") ?? "").trim();

  // The refusal comes back on the day and the slot the patient was looking at.
  // Dropping them on whatever day happens to be free first makes the message
  // read as being about a slot they never chose.
  const fail = (message: string) => {
    const params = new URLSearchParams({ error: message });
    const chosen = new Date(startAtRaw);
    if (!Number.isNaN(chosen.getTime())) params.set("creneau", chosen.toISOString());
    redirect(`/partenaire/${slug}/rendez-vous?${params.toString()}`);
  };

  const user = await getCurrentUser();
  if (!user) {
    // The chosen slot rides through the sign-in detour. Sending the patient back
    // to a bare booking page would make them find the same day and the same hour
    // a second time, and the notice above the form promises otherwise.
    const chosen = new Date(startAtRaw);
    const resume = Number.isNaN(chosen.getTime())
      ? `/partenaire/${slug}/rendez-vous`
      : `/partenaire/${slug}/rendez-vous?creneau=${encodeURIComponent(chosen.toISOString())}`;
    redirect(`/connexion?next=${encodeURIComponent(resume)}`);
  }

  const partner = await db.partner.findUnique({
    where: { slug },
    select: { id: true, slotDurationMinutes: true },
  });
  if (!partner) notFound();

  const startAt = new Date(startAtRaw);
  if (Number.isNaN(startAt.getTime())) fail("Créneau invalide.");

  // Re-check against fresh data: the grid the patient saw may be stale, and two
  // people can load the same page at the same moment.
  const check = await isSlotBookable(partner.id, startAt);
  if (!check.ok) fail(check.reason);

  const existing = await db.appointment.findFirst({
    where: {
      partnerId: partner.id,
      patientId: user.id,
      status: { in: ["PENDING", "CONFIRMED"] },
      startAt: { gte: new Date() },
    },
    select: { id: true },
  });
  if (existing) {
    fail("Vous avez déjà un rendez-vous à venir avec ce praticien.");
  }

  const appointment = await db.appointment.create({
    data: {
      partnerId: partner.id,
      patientId: user.id,
      startAt,
      endAt: check.ok ? check.endAt : startAt,
      status: "PENDING",
      reason: reason || null,
      serviceName: serviceName || null,
    },
  });

  // Both sides are told. The patient sees the request is pending; the practice
  // sees a new request it has to act on.
  const members = await db.partnerMember.findMany({
    where: { partnerId: partner.id },
    select: { userId: true },
  });

  await db.notification.createMany({
    data: [
      {
        userId: user.id,
        kind: "APPOINTMENT_REQUESTED",
        title: "Demande de rendez-vous envoyée",
        body: "Votre demande est en attente de confirmation par le praticien.",
        href: "/patient/rendez-vous",
      },
      ...members.map((member) => ({
        userId: member.userId,
        kind: "APPOINTMENT_REQUESTED" as const,
        title: "Nouvelle demande de rendez-vous",
        body: `${user.firstName} ${user.lastName} demande un créneau.`,
        href: "/pro/agenda",
      })),
    ],
  });

  redirect(`/patient/rendez-vous?nouveau=${appointment.id}`);
}

export default async function BookingPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const query = await searchParams;
  const user = await getCurrentUser();

  const partner = await db.partner.findUnique({
    where: { slug },
    include: {
      category: true,
      specialty: true,
      wilaya: true,
      commune: true,
      services: { orderBy: { name: "asc" }, select: { id: true, name: true } },
    },
  });

  if (!partner || partner.status !== "ACTIVE") notFound();

  // A pharmacy has no agenda; sending a patient to a booking form for one would
  // be a dead end the category model already knows about.
  if (!partner.category.supportsAppointments) {
    redirect(`/partenaire/${partner.slug}`);
  }

  const availability = await getAvailability(partner.id);
  const error = typeof query.error === "string" ? query.error : null;

  // The day is chosen in the page, not in the client component, so the strip
  // below works with JavaScript off. `?jour=` names the day; without it, the
  // first day that actually has a free slot is opened — landing a patient on an
  // empty Friday because it happens to be today is a wasted screen.
  const freeCount = (day: (typeof availability)[number]) =>
    day.slots.filter((slot) => slot.available).length;

  // `creneau` comes back from the sign-in detour and names both the day to open
  // and the time to pre-select; `jour` is the ordinary day-strip navigation.
  const resumedSlot = typeof query.creneau === "string" ? query.creneau : null;
  const resumedDate = (() => {
    if (!resumedSlot) return null;
    const date = new Date(resumedSlot);
    if (Number.isNaN(date.getTime())) return null;
    const p = zonedParts(date);
    return `${p.year}-${String(p.month).padStart(2, "0")}-${String(p.day).padStart(2, "0")}`;
  })();

  const requestedDate =
    resumedDate ?? (typeof query.jour === "string" ? query.jour : null);
  const selectedDay =
    availability.find((day) => day.date === requestedDate) ??
    availability.find((day) => freeCount(day) > 0) ??
    null;

  const totalFree = availability.reduce((sum, day) => sum + freeCount(day), 0);

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto w-full max-w-2xl flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <nav
          aria-label="Fil d'Ariane"
          className="mb-5 flex items-center gap-1 text-sm text-ink-500"
        >
          <Link
            href={`/partenaire/${partner.slug}`}
            className="inline-flex min-h-11 items-center underline underline-offset-4 hover:text-azur-700"
          >
            {partner.displayName}
          </Link>
          <span aria-hidden> / </span>
          <span>Rendez-vous</span>
        </nav>

        <h1 className="font-display text-3xl font-bold text-ink-900 sm:text-4xl">
          Prendre rendez-vous
        </h1>
        <p className="mt-2 text-[15px] text-ink-600">
          {partner.specialty?.name ?? partner.category.name} ·{" "}
          {partner.commune.name}, {partner.wilaya.name} · créneaux de{" "}
          {partner.slotDurationMinutes} minutes.
        </p>

        {error && (
          <p
            role="alert"
            className="mt-6 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
          >
            {error}
          </p>
        )}

        {!user && (
          <div className="mt-6 border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3">
            <p className="text-[15px] leading-relaxed text-ink-900">
              Vous devrez vous connecter pour confirmer la demande. Choisissez
              votre créneau d&apos;abord&nbsp;: il sera conservé.
            </p>
            <Link
              href={`/connexion?next=${encodeURIComponent(`/partenaire/${partner.slug}/rendez-vous`)}`}
              className="mt-1 inline-flex min-h-11 items-center font-bold text-ink-900 underline underline-offset-4"
            >
              Se connecter maintenant
            </Link>
          </div>
        )}

        {totalFree === 0 || !selectedDay ? (
          <div className="mt-8 bg-enamel-50 p-6 text-center">
            <p className="font-display text-lg font-bold text-ink-900">
              Aucun créneau disponible
            </p>
            <p className="mx-auto mt-2 max-w-sm text-[15px] leading-relaxed text-ink-600">
              Ce praticien n&apos;a pas de créneau libre dans les quatorze
              prochains jours. Appelez le cabinet pour connaître ses prochaines
              disponibilités.
            </p>
            <a
              href={`tel:${partner.phone.replace(/\s/g, "")}`}
              className="mt-5 inline-flex min-h-11 items-center border border-azur-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:bg-azur-100"
            >
              Appeler le {partner.phone}
            </a>
          </div>
        ) : (
          <>
            {/* The day strip. Each day carries its own free-slot count, so a
                patient can see where the availability is before opening a
                single one. */}
            <nav aria-label="Choisir un jour" className="mt-8">
              <h2 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
                Choisir un jour
              </h2>
              {/* The number under each date is otherwise a bare figure the
                  patient has to guess at. Said once, here, rather than
                  repeated on fourteen cards that have no room for it. */}
              <p className="mt-1 text-sm text-ink-500">
                Le chiffre indique les créneaux encore libres.
              </p>
              <ul className="mt-3 flex gap-2 overflow-x-auto pb-2 sm:flex-wrap sm:overflow-x-visible">
                {availability.map((day) => {
                  const free = freeCount(day);
                  const isSelected = day.date === selectedDay.date;
                  const shared =
                    "flex min-h-11 w-[4.25rem] shrink-0 flex-col items-center justify-center gap-0.5 border px-1 py-2 text-center";

                  if (free === 0) {
                    return (
                      <li key={day.date}>
                        <span
                          className={`${shared} border-enamel-300 bg-enamel-50 text-ink-300`}
                          aria-label={`${day.label} : complet`}
                        >
                          <span className="font-display text-[11px] tracking-[0.06em] uppercase">
                            {day.weekdayShort}
                          </span>
                          <span className="font-display text-lg font-bold tabular-nums">
                            {day.dayNumber}
                          </span>
                          <span className="text-[10px] leading-none">—</span>
                        </span>
                      </li>
                    );
                  }

                  return (
                    <li key={day.date}>
                      <Link
                        href={`/partenaire/${partner.slug}/rendez-vous?jour=${day.date}`}
                        scroll={false}
                        aria-current={isSelected ? "date" : undefined}
                        aria-label={`${day.label} : ${free} créneau${free > 1 ? "x" : ""} libre${free > 1 ? "s" : ""}`}
                        className={
                          isSelected
                            ? `${shared} border-azur-700 bg-azur-700 text-enamel-50`
                            : `${shared} border-enamel-300 bg-white text-ink-900 hover:border-azur-600`
                        }
                      >
                        <span className="font-display text-[11px] tracking-[0.06em] uppercase">
                          {day.weekdayShort}
                        </span>
                        <span className="font-display text-lg font-bold tabular-nums">
                          {day.dayNumber}
                        </span>
                        <span
                          className={
                            isSelected
                              ? "text-[10px] leading-none tabular-nums text-azur-100"
                              : "text-[10px] leading-none tabular-nums text-ink-500"
                          }
                        >
                          {free}
                        </span>
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </nav>

            <form
              action={book}
              className="mt-3 flex flex-col gap-px bg-ink-900/10"
            >
              <input type="hidden" name="slug" value={partner.slug} />
              <SlotPicker
                key={selectedDay.date}
                day={selectedDay}
                initialSlot={resumedSlot}
                partnerName={partner.displayName}
                durationMinutes={partner.slotDurationMinutes}
                services={partner.services}
              />
            </form>
          </>
        )}

        <p className="mt-6 text-sm leading-relaxed text-ink-500">
          Votre demande est envoyée au praticien, qui la confirme ou la refuse.
          Vous serez notifié dans votre espace patient.
        </p>
      </div>
    </main>
  );
}
