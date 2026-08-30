import Link from "next/link";
import { dateTimeLong } from "@/lib/format";

/**
 * Appointment status, coded by carbon-copy colour — the Carnet donation, where
 * each flimsy in the duplicate book carries a different state.
 */
export const STATUS_STYLES: Record<
  string,
  { label: string; className: string }
> = {
  PENDING: {
    label: "En attente",
    className: "border-carbon-amber/50 bg-carbon-amber-soft text-carbon-amber",
  },
  CONFIRMED: {
    label: "Confirmé",
    className: "border-cross-600/40 bg-cross-100 text-cross-800",
  },
  COMPLETED: {
    label: "Terminé",
    className: "border-ink-300/50 bg-enamel-200 text-ink-600",
  },
  CANCELLED: {
    label: "Annulé",
    className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose",
  },
  NO_SHOW: {
    label: "Absent",
    className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose",
  },
};

export function StatusBadge({ status }: { status: string }) {
  const style = STATUS_STYLES[status] ?? STATUS_STYLES.PENDING;
  return (
    <span
      className={`shrink-0 border px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] uppercase ${style.className}`}
    >
      {style.label}
    </span>
  );
}

export function formatAppointmentDate(date: Date) {
  return dateTimeLong.format(date);
}

export function AppointmentRow({
  appointment,
  perspective,
  action,
}: {
  appointment: {
    id: string;
    startAt: Date;
    status: string;
    reason: string | null;
    serviceName: string | null;
    partner: { slug: string; displayName: string; commune: { name: string } };
    patient?: { firstName: string; lastName: string; phone: string | null };
  };
  /** Whose list this is: a patient sees the practice, a practice sees the patient. */
  perspective: "patient" | "partner";
  action?: React.ReactNode;
}) {
  return (
    <article className="bg-enamel-50 p-4 sm:p-5">
      <div className="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            {perspective === "patient" ? (
              <Link
                href={`/partenaire/${appointment.partner.slug}`}
                className="flex min-h-11 items-center font-display text-base font-bold text-ink-900 hover:text-cross-700 sm:text-lg"
              >
                {appointment.partner.displayName}
              </Link>
            ) : (
              <span className="font-display text-base font-bold text-ink-900 sm:text-lg">
                {appointment.patient
                  ? `${appointment.patient.firstName} ${appointment.patient.lastName}`
                  : "Patient"}
              </span>
            )}
            <StatusBadge status={appointment.status} />
          </div>

          <p className="mt-1 font-display text-sm tabular-nums text-cross-700">
            {formatAppointmentDate(appointment.startAt)}
          </p>

          <p className="mt-1 text-sm text-ink-500">
            {appointment.serviceName ?? "Consultation"}
            {perspective === "patient"
              ? ` · ${appointment.partner.commune.name}`
              : appointment.patient?.phone
                ? ` · ${appointment.patient.phone}`
                : ""}
          </p>

          {appointment.reason && (
            <p className="mt-2 text-sm leading-snug text-ink-600">
              {appointment.reason}
            </p>
          )}
        </div>

        {action && <div className="shrink-0">{action}</div>}
      </div>
    </article>
  );
}
