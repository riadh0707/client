import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { AppointmentRow } from "@/components/appointment-row";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { requirePartnerContext, assertAgendaRight } from "@/lib/pro";

export const metadata: Metadata = { title: "Agenda" };
export const dynamic = "force-dynamic";

type Decision = "CONFIRMED" | "CANCELLED" | "COMPLETED" | "NO_SHOW";

const PATIENT_NOTICE: Partial<
  Record<Decision, { kind: "APPOINTMENT_CONFIRMED" | "APPOINTMENT_CANCELLED"; title: string; body: string }>
> = {
  CONFIRMED: {
    kind: "APPOINTMENT_CONFIRMED",
    title: "Rendez-vous confirmé",
    body: "Le praticien a confirmé votre rendez-vous.",
  },
  CANCELLED: {
    kind: "APPOINTMENT_CANCELLED",
    title: "Rendez-vous annulé",
    body: "Le praticien a annulé votre rendez-vous.",
  },
};

async function decide(formData: FormData) {
  "use server";

  const id = String(formData.get("id") ?? "");
  const decision = String(formData.get("decision") ?? "") as Decision;

  if (!["CONFIRMED", "CANCELLED", "COMPLETED", "NO_SHOW"].includes(decision)) {
    redirect("/pro/agenda?erreur=1");
  }

  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fagenda");

  const appointment = await db.appointment.findUnique({
    where: { id },
    select: { id: true, partnerId: true, patientId: true },
  });
  if (!appointment) redirect("/pro/agenda?erreur=1");

  // The right is checked against this appointment's partner, not against
  // whichever partner the session happens to be viewing.
  assertAgendaRight(user, appointment.partnerId);

  await db.appointment.update({
    where: { id: appointment.id },
    data: {
      status: decision,
      ...(decision === "CANCELLED"
        ? {
            cancelledAt: new Date(),
            cancellationReason: "Annulé par le praticien",
          }
        : {}),
    },
  });

  const notice = PATIENT_NOTICE[decision];
  if (notice) {
    await db.notification.create({
      data: {
        userId: appointment.patientId,
        kind: notice.kind,
        title: notice.title,
        body: notice.body,
        href: "/patient/rendez-vous",
      },
    });
  }

  revalidatePath("/pro/agenda");
  redirect("/pro/agenda");
}

function DecisionButton({
  id,
  decision,
  label,
  tone,
}: {
  id: string;
  decision: Decision;
  label: string;
  tone: "accept" | "reject" | "neutral";
}) {
  const styles = {
    accept:
      "border-azur-700 bg-azur-500 text-azur-950 hover:bg-azur-400",
    reject:
      "border-carbon-rose/60 text-carbon-rose hover:bg-carbon-rose-soft",
    neutral: "border-enamel-300 text-ink-600 hover:bg-enamel-200",
  }[tone];

  return (
    <form action={decide}>
      <input type="hidden" name="id" value={id} />
      <input type="hidden" name="decision" value={decision} />
      <button
        type="submit"
        className={`w-full border px-3 py-2 font-display text-xs font-bold tracking-[0.08em] uppercase ${styles}`}
      >
        {label}
      </button>
    </form>
  );
}

export default async function AgendaPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const query = await searchParams;
  const { partner, permissions } = await requirePartnerContext("/pro/agenda");
  if (!partner) redirect("/pro");

  const now = new Date();
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
    patient: { select: { firstName: true, lastName: true, phone: true } },
  };

  const [pending, confirmed, past] = await Promise.all([
    db.appointment.findMany({
      where: { partnerId: partner.id, status: "PENDING", startAt: { gte: now } },
      select,
      orderBy: { startAt: "asc" },
    }),
    db.appointment.findMany({
      where: {
        partnerId: partner.id,
        status: "CONFIRMED",
        startAt: { gte: now },
      },
      select,
      orderBy: { startAt: "asc" },
    }),
    db.appointment.findMany({
      where: { partnerId: partner.id, startAt: { lt: now } },
      select,
      orderBy: { startAt: "desc" },
      take: 15,
    }),
  ]);

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Agenda
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">{partner.displayName}</p>

      {query.erreur && (
        <p
          role="alert"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
        >
          Action impossible sur ce rendez-vous.
        </p>
      )}

      <section className="mt-8">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Demandes en attente ({pending.length})
        </h2>
        {pending.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Aucune demande en attente.
          </p>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {pending.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="partner"
                action={
                  permissions.canManageAgenda ? (
                    <div className="flex w-36 flex-col gap-2">
                      <DecisionButton
                        id={appointment.id}
                        decision="CONFIRMED"
                        label="Confirmer"
                        tone="accept"
                      />
                      <DecisionButton
                        id={appointment.id}
                        decision="CANCELLED"
                        label="Refuser"
                        tone="reject"
                      />
                    </div>
                  ) : null
                }
              />
            ))}
          </div>
        )}
      </section>

      <section className="mt-10">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Confirmés ({confirmed.length})
        </h2>
        {confirmed.length === 0 ? (
          <p className="mt-3 bg-enamel-50 px-5 py-8 text-center text-[15px] text-ink-600">
            Aucun rendez-vous confirmé à venir.
          </p>
        ) : (
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {confirmed.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="partner"
                action={
                  permissions.canManageAgenda ? (
                    <div className="flex w-36 flex-col gap-2">
                      <DecisionButton
                        id={appointment.id}
                        decision="CANCELLED"
                        label="Annuler"
                        tone="reject"
                      />
                    </div>
                  ) : null
                }
              />
            ))}
          </div>
        )}
      </section>

      {past.length > 0 && (
        <section className="mt-10">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Passés
          </h2>
          <div className="mt-3 flex flex-col gap-px bg-ink-900/10">
            {past.map((appointment) => (
              <AppointmentRow
                key={appointment.id}
                appointment={appointment}
                perspective="partner"
                action={
                  // Only a still-open past appointment needs an outcome; one
                  // already marked keeps its record rather than offering a
                  // silent rewrite.
                  permissions.canManageAgenda &&
                  ["PENDING", "CONFIRMED"].includes(appointment.status) ? (
                    <div className="flex w-36 flex-col gap-2">
                      <DecisionButton
                        id={appointment.id}
                        decision="COMPLETED"
                        label="Honoré"
                        tone="neutral"
                      />
                      <DecisionButton
                        id={appointment.id}
                        decision="NO_SHOW"
                        label="Absent"
                        tone="neutral"
                      />
                    </div>
                  ) : null
                }
              />
            ))}
          </div>
        </section>
      )}
    </main>
  );
}
