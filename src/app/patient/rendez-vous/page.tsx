import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { AppointmentRow } from "@/components/appointment-row";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = { title: "Mes rendez-vous" };
export const dynamic = "force-dynamic";

async function cancelAppointment(formData: FormData) {
  "use server";

  const id = String(formData.get("id") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpatient%2Frendez-vous");

  // Scoped to the caller's own appointment: without patientId in the filter,
  // anyone could cancel anyone's booking by posting an id.
  const appointment = await db.appointment.findFirst({
    where: { id, patientId: user.id, status: { in: ["PENDING", "CONFIRMED"] } },
    select: { id: true, partnerId: true, startAt: true },
  });
  if (!appointment) redirect("/patient/rendez-vous?erreur=annulation");

  await db.appointment.update({
    where: { id: appointment.id },
    data: {
      status: "CANCELLED",
      cancelledAt: new Date(),
      cancellationReason: "Annulé par le patient",
    },
  });

  const members = await db.partnerMember.findMany({
    where: { partnerId: appointment.partnerId },
    select: { userId: true },
  });
  if (members.length > 0) {
    await db.notification.createMany({
      data: members.map((member) => ({
        userId: member.userId,
        kind: "APPOINTMENT_CANCELLED" as const,
        title: "Rendez-vous annulé",
        body: `${user.firstName} ${user.lastName} a annulé son rendez-vous.`,
        href: "/pro/agenda",
      })),
    });
  }

  revalidatePath("/patient/rendez-vous");
  redirect("/patient/rendez-vous?annule=1");
}

export default async function PatientAppointmentsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const query = await searchParams;
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpatient%2Frendez-vous");

  const select = {
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
  };

  const now = new Date();
  const [upcoming, past] = await Promise.all([
    db.appointment.findMany({
      where: {
        patientId: user.id,
        startAt: { gte: now },
        status: { in: ["PENDING", "CONFIRMED"] },
      },
      select,
      orderBy: { startAt: "asc" },
    }),
    db.appointment.findMany({
      where: {
        patientId: user.id,
        OR: [
          { startAt: { lt: now } },
          { status: { in: ["CANCELLED", "COMPLETED", "NO_SHOW"] } },
        ],
      },
      select,
      orderBy: { startAt: "desc" },
      take: 20,
    }),
  ]);

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Mes rendez-vous
      </h1>

      {query.nouveau && (
        <p
          role="status"
          className="mt-5 border border-cross-600/40 bg-cross-100 px-4 py-3 text-[15px] text-ink-900"
        >
          Demande envoyée. Le praticien doit la confirmer&nbsp;: son statut
          restera «&nbsp;en attente&nbsp;» jusque-là.
        </p>
      )}
      {query.annule && (
        <p
          role="status"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
        >
          Rendez-vous annulé. Le praticien en a été informé.
        </p>
      )}
      {query.erreur === "annulation" && (
        <p
          role="alert"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
        >
          Ce rendez-vous ne peut plus être annulé.
        </p>
      )}

      <section className="mt-8">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          À venir
        </h2>
        {upcoming.length === 0 ? (
          <div className="mt-3 bg-enamel-50 px-5 py-10 text-center">
            <span
              aria-hidden
              className="cross-mark mx-auto block h-8 w-8 text-enamel-300"
            />
            <p className="mt-4 font-display text-lg font-bold text-ink-900">
              Aucun rendez-vous à venir
            </p>
            <p className="mx-auto mt-1.5 max-w-sm text-[15px] leading-relaxed text-ink-600">
              Cherchez un professionnel et demandez un créneau.
            </p>
            <Link
              href="/recherche"
              className="mt-5 inline-block border border-cross-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100"
            >
              Chercher un professionnel
            </Link>
          </div>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {upcoming.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="patient"
                action={
                  <form action={cancelAppointment}>
                    <input type="hidden" name="id" value={appointment.id} />
                    <button
                      type="submit"
                      className="border border-carbon-rose/60 px-3 py-2 font-display text-xs font-bold tracking-[0.08em] text-carbon-rose uppercase hover:bg-carbon-rose-soft"
                    >
                      Annuler
                    </button>
                  </form>
                }
              />
            ))}
          </div>
        )}
      </section>

      {past.length > 0 && (
        <section className="mt-10">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Historique
          </h2>
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {past.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="patient"
              />
            ))}
          </div>
        </section>
      )}
    </main>
  );
}
