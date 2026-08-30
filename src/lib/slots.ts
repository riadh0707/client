import { db } from "@/lib/db";
import type { Interval } from "@/lib/hours";
import {
  addZonedDays,
  startOfZonedDay,
  zonedParts,
  zonedTimeToInstant,
} from "@/lib/time";

/**
 * Availability computation.
 *
 * Slots are derived, never stored: a partner declares opening intervals, a slot
 * duration, and time off, and the bookable grid falls out of those three. Storing
 * generated slots would mean regenerating them every time a practitioner edits
 * their hours, and drifting whenever that job failed.
 */

export type Slot = {
  /** ISO string, the source of truth passed back on booking. */
  startAt: string;
  /** "09:30", for display. */
  label: string;
  available: boolean;
};

export type DayAvailability = {
  /** "2026-08-31" */
  date: string;
  label: string;
  slots: Slot[];
};

const DAY_LABELS = [
  "dimanche",
  "lundi",
  "mardi",
  "mercredi",
  "jeudi",
  "vendredi",
  "samedi",
];
const MONTH_LABELS = [
  "janvier", "février", "mars", "avril", "mai", "juin",
  "juillet", "août", "septembre", "octobre", "novembre", "décembre",
];

function toMinutes(time: string) {
  const [hours, minutes] = time.split(":").map(Number);
  return hours * 60 + minutes;
}

function isoDate(date: Date) {
  const p = zonedParts(date);
  return `${p.year}-${String(p.month).padStart(2, "0")}-${String(p.day).padStart(2, "0")}`;
}

function dayLabel(date: Date) {
  const p = zonedParts(date);
  return `${DAY_LABELS[p.weekday]} ${p.day} ${MONTH_LABELS[p.month - 1]}`;
}

/**
 * Builds the bookable grid for `days` days starting today.
 *
 * A slot is unavailable when it overlaps a booked appointment, falls inside a
 * time-off range, or is already in the past. Past slots are rendered as taken
 * rather than hidden so today's column keeps the same shape as every other one.
 */
export async function getAvailability(
  partnerId: string,
  days = 14,
  now: Date = new Date(),
): Promise<DayAvailability[]> {
  const partner = await db.partner.findUnique({
    where: { id: partnerId },
    select: {
      slotDurationMinutes: true,
      openingHours: { select: { weekday: true, opensAt: true, closesAt: true } },
    },
  });
  if (!partner) return [];

  // Day boundaries on the Algerian calendar. Host-local midnight would shift the
  // whole grid by the offset and drop or duplicate a day at the edges.
  const rangeStart = startOfZonedDay(now);
  const rangeEnd = addZonedDays(now, days);

  const [appointments, timeOff] = await Promise.all([
    db.appointment.findMany({
      where: {
        partnerId,
        // Cancelled appointments free their slot again; no-shows do not, since
        // the practitioner's time was still consumed.
        status: { in: ["PENDING", "CONFIRMED", "COMPLETED", "NO_SHOW"] },
        startAt: { gte: rangeStart, lt: rangeEnd },
      },
      select: { startAt: true, endAt: true },
    }),
    db.timeOff.findMany({
      where: { partnerId, endAt: { gte: rangeStart }, startAt: { lt: rangeEnd } },
      select: { startAt: true, endAt: true },
    }),
  ]);

  const busy = [...appointments, ...timeOff];
  const intervals = partner.openingHours as Interval[];
  const step = partner.slotDurationMinutes;

  const result: DayAvailability[] = [];

  for (let offset = 0; offset < days; offset += 1) {
    const date = addZonedDays(rangeStart, offset);
    const dayParts = zonedParts(date);

    const todays = intervals
      .filter((interval) => interval.weekday === dayParts.weekday)
      .sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt));

    const slots: Slot[] = [];

    for (const interval of todays) {
      const opens = toMinutes(interval.opensAt);
      const closes = toMinutes(interval.closesAt);

      for (let minute = opens; minute + step <= closes; minute += step) {
        // "08:00" means eight o'clock in Algiers; the instant that denotes is
        // what gets stored and compared.
        const startAt = zonedTimeToInstant(
          dayParts.year,
          dayParts.month,
          dayParts.day,
          Math.floor(minute / 60),
          minute % 60,
        );
        const endAt = new Date(startAt.getTime() + step * 60000);

        const overlapped = busy.some(
          (range) => startAt < range.endAt && endAt > range.startAt,
        );

        slots.push({
          startAt: startAt.toISOString(),
          label: `${String(Math.floor(minute / 60)).padStart(2, "0")}:${String(minute % 60).padStart(2, "0")}`,
          available: !overlapped && startAt > now,
        });
      }
    }

    result.push({ date: isoDate(date), label: dayLabel(date), slots });
  }

  return result;
}

/**
 * Re-checks one slot at booking time.
 *
 * The grid the patient saw may be seconds old, so the same rules are applied
 * again here against fresh data. Without this, two patients loading the page
 * together could both book the same slot.
 */
export async function isSlotBookable(partnerId: string, startAt: Date) {
  const partner = await db.partner.findUnique({
    where: { id: partnerId },
    select: {
      status: true,
      slotDurationMinutes: true,
      category: { select: { supportsAppointments: true } },
      openingHours: { select: { weekday: true, opensAt: true, closesAt: true } },
    },
  });

  if (!partner) return { ok: false as const, reason: "Partenaire introuvable." };
  if (partner.status !== "ACTIVE")
    return { ok: false as const, reason: "Ce partenaire n'accepte pas de rendez-vous." };
  if (!partner.category.supportsAppointments)
    return { ok: false as const, reason: "Ce partenaire ne prend pas de rendez-vous." };
  if (startAt <= new Date())
    return { ok: false as const, reason: "Ce créneau est déjà passé." };

  const endAt = new Date(startAt.getTime() + partner.slotDurationMinutes * 60000);
  const { weekday, minutesOfDay: minutes } = zonedParts(startAt);

  const withinHours = (partner.openingHours as Interval[]).some(
    (interval) =>
      interval.weekday === weekday &&
      minutes >= toMinutes(interval.opensAt) &&
      minutes + partner.slotDurationMinutes <= toMinutes(interval.closesAt),
  );
  if (!withinHours)
    return { ok: false as const, reason: "Ce créneau est hors des horaires d'ouverture." };

  const [clash, off] = await Promise.all([
    db.appointment.findFirst({
      where: {
        partnerId,
        status: { in: ["PENDING", "CONFIRMED", "COMPLETED", "NO_SHOW"] },
        startAt: { lt: endAt },
        endAt: { gt: startAt },
      },
      select: { id: true },
    }),
    db.timeOff.findFirst({
      where: { partnerId, startAt: { lt: endAt }, endAt: { gt: startAt } },
      select: { id: true },
    }),
  ]);

  if (clash) return { ok: false as const, reason: "Ce créneau vient d'être réservé." };
  if (off) return { ok: false as const, reason: "Le praticien est absent à cette date." };

  return { ok: true as const, endAt };
}
