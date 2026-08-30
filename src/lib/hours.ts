/**
 * Opening-hours logic.
 *
 * PRODUCT.md treats hours as functional data: "open right now" and "next
 * opening" drive real patient decisions, especially for pharmacies. Algerian
 * practices commonly split the day (08:00-12:00, 14:00-17:00) and close Friday,
 * so a single open/close pair per weekday cannot express reality — hence one row
 * per interval.
 */

export type Interval = {
  weekday: number; // 0 = Sunday … 6 = Saturday
  opensAt: string; // "08:00" wall-clock
  closesAt: string; // "12:00"
};

const WEEKDAY_LABELS = [
  "Dimanche",
  "Lundi",
  "Mardi",
  "Mercredi",
  "Jeudi",
  "Vendredi",
  "Samedi",
] as const;

export function weekdayLabel(weekday: number) {
  return WEEKDAY_LABELS[weekday] ?? "";
}

function toMinutes(time: string) {
  const [hours, minutes] = time.split(":").map(Number);
  return hours * 60 + minutes;
}

function formatMinutes(total: number) {
  const hours = Math.floor(total / 60) % 24;
  const minutes = total % 60;
  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
}

export type OpenState =
  | { status: "open"; closesAt: string }
  | { status: "closed"; opensAt: string; weekday: number }
  | { status: "unknown" };

/**
 * Resolves whether a partner is open at `now`.
 *
 * Returns "unknown" rather than "closed" when no hours are recorded: an empty
 * schedule means nobody filled it in, and telling a patient a pharmacy is closed
 * on that basis would be a lie the interface cannot support.
 */
export function resolveOpenState(
  intervals: Interval[],
  now: Date = new Date(),
): OpenState {
  if (intervals.length === 0) return { status: "unknown" };

  const weekday = now.getDay();
  const minutesNow = now.getHours() * 60 + now.getMinutes();

  const todays = intervals
    .filter((interval) => interval.weekday === weekday)
    .sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt));

  for (const interval of todays) {
    const opens = toMinutes(interval.opensAt);
    const closes = toMinutes(interval.closesAt);
    if (minutesNow >= opens && minutesNow < closes) {
      return { status: "open", closesAt: formatMinutes(closes) };
    }
  }

  // Next opening: later today first, then forward through the week.
  const laterToday = todays.find(
    (interval) => toMinutes(interval.opensAt) > minutesNow,
  );
  if (laterToday) {
    return { status: "closed", opensAt: laterToday.opensAt, weekday };
  }

  for (let offset = 1; offset <= 7; offset += 1) {
    const day = (weekday + offset) % 7;
    const next = intervals
      .filter((interval) => interval.weekday === day)
      .sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt))[0];
    if (next) return { status: "closed", opensAt: next.opensAt, weekday: day };
  }

  return { status: "unknown" };
}

/** Groups intervals per weekday for display on a profile. */
export function groupByWeekday(intervals: Interval[]) {
  return Array.from({ length: 7 }, (_, weekday) => ({
    weekday,
    label: WEEKDAY_LABELS[weekday],
    intervals: intervals
      .filter((interval) => interval.weekday === weekday)
      .sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt)),
  }));
}

/**
 * Great-circle distance in kilometres. Used for "near me" ordering; at Algerian
 * intra-wilaya scale the spherical error is far below the precision of the
 * coordinates themselves.
 */
export function distanceKm(
  a: { lat: number; lng: number },
  b: { lat: number; lng: number },
) {
  const R = 6371;
  const dLat = ((b.lat - a.lat) * Math.PI) / 180;
  const dLng = ((b.lng - a.lng) * Math.PI) / 180;
  const lat1 = (a.lat * Math.PI) / 180;
  const lat2 = (b.lat * Math.PI) / 180;
  const h =
    Math.sin(dLat / 2) ** 2 +
    Math.sin(dLng / 2) ** 2 * Math.cos(lat1) * Math.cos(lat2);
  return 2 * R * Math.asin(Math.sqrt(h));
}

export function formatDistance(km: number) {
  if (km < 1) return `${Math.round(km * 1000)} m`;
  if (km < 10) return `${km.toFixed(1)} km`;
  return `${Math.round(km)} km`;
}
