/**
 * Algerian wall-clock time.
 *
 * Every "ouvert / fermé", every "ouvre à 08:00", every bookable slot and every
 * displayed appointment hour is a statement about the clock on the wall in
 * Algeria. Before this module those were computed with `Date` getters, which
 * read the host's timezone: deployed on a UTC host the whole platform ran an
 * hour behind the country it serves, telling patients a pharmacy that opened at
 * 08:00 was closed at 08:51 Algiers, and offering slots that had already passed.
 *
 * Algeria has kept UTC+1 year-round since 1981 with no daylight saving, so a
 * hardcoded +1 would work today. The offset is derived from `Intl` anyway: a
 * constant would be an undocumented bet on a government not changing its mind,
 * and deriving it costs nothing.
 */

export const APP_TIME_ZONE = "Africa/Algiers";

const PARTS = new Intl.DateTimeFormat("en-US", {
  timeZone: APP_TIME_ZONE,
  hour12: false,
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
  weekday: "short",
});

const WEEKDAY_INDEX: Record<string, number> = {
  Sun: 0,
  Mon: 1,
  Tue: 2,
  Wed: 3,
  Thu: 4,
  Fri: 5,
  Sat: 6,
};

export type ZonedParts = {
  year: number;
  /** 1-12, not the zero-based month a Date carries. */
  month: number;
  day: number;
  /** 0 = Sunday … 6 = Saturday, matching OpeningHours.weekday. */
  weekday: number;
  hours: number;
  minutes: number;
  /** Minutes since midnight, the unit the opening-hours comparisons use. */
  minutesOfDay: number;
};

/** Reads an instant as it appears on a clock in Algiers. */
export function zonedParts(date: Date = new Date()): ZonedParts {
  const parts: Record<string, string> = {};
  for (const part of PARTS.formatToParts(date)) {
    if (part.type !== "literal") parts[part.type] = part.value;
  }

  // "24" appears at midnight in some ICU versions of the h23 cycle.
  const hours = Number(parts.hour) % 24;
  const minutes = Number(parts.minute);

  return {
    year: Number(parts.year),
    month: Number(parts.month),
    day: Number(parts.day),
    weekday: WEEKDAY_INDEX[parts.weekday] ?? 0,
    hours,
    minutes,
    minutesOfDay: hours * 60 + minutes,
  };
}

/**
 * The zone's offset from UTC at a given instant, in milliseconds.
 *
 * Formats the instant in the zone, reads those wall-clock fields back as if
 * they were UTC, and takes the difference. That is the standard trick for
 * getting an IANA offset without a date library.
 */
function offsetMs(date: Date): number {
  const p = zonedParts(date);
  const asIfUtc = Date.UTC(
    p.year,
    p.month - 1,
    p.day,
    p.hours,
    p.minutes,
    date.getUTCSeconds(),
    date.getUTCMilliseconds(),
  );
  return asIfUtc - date.getTime();
}

/**
 * Turns an Algerian wall-clock time into the instant it denotes.
 *
 * Applied twice: the first pass uses the offset in force at the naive guess,
 * which is the wrong offset for a wall time that falls on the far side of a
 * transition. The second pass re-reads the offset at the corrected instant.
 * Algeria has no transitions today, so the second pass is a no-op here — it is
 * what keeps the function correct if that ever stops being true.
 */
export function zonedTimeToInstant(
  year: number,
  month: number,
  day: number,
  hours = 0,
  minutes = 0,
): Date {
  const naive = Date.UTC(year, month - 1, day, hours, minutes, 0, 0);
  let instant = new Date(naive - offsetMs(new Date(naive)));
  instant = new Date(naive - offsetMs(instant));
  return instant;
}

/** Midnight in Algiers on the day the instant falls on. */
export function startOfZonedDay(date: Date = new Date()): Date {
  const p = zonedParts(date);
  return zonedTimeToInstant(p.year, p.month, p.day, 0, 0);
}

/** Midnight in Algiers `days` days after the day the instant falls on. */
export function addZonedDays(date: Date, days: number): Date {
  const p = zonedParts(date);
  return zonedTimeToInstant(p.year, p.month, p.day + days, 0, 0);
}

/** "08:00" for an instant, on the Algerian clock. */
export function zonedClock(date: Date): string {
  const p = zonedParts(date);
  return `${String(p.hours).padStart(2, "0")}:${String(p.minutes).padStart(2, "0")}`;
}
