/**
 * Locale formatting, centralised.
 *
 * `fr-DZ` resolves to a 12-hour cycle in CLDR, so an unqualified formatter
 * renders "02:00 PM" in an Algerian French interface. Algeria uses the 24-hour
 * clock, so every formatter here sets hour12 explicitly rather than trusting the
 * locale. Keeping them in one module is what stops the next page from
 * reintroducing the bug.
 */

import { APP_TIME_ZONE } from "@/lib/time";

const LOCALE = "fr-DZ";

export const dateTimeLong = new Intl.DateTimeFormat(LOCALE, {
  weekday: "long",
  day: "numeric",
  month: "long",
  hour: "2-digit",
  minute: "2-digit",
  hour12: false,
  timeZone: APP_TIME_ZONE,
});

export const dateTimeShort = new Intl.DateTimeFormat(LOCALE, {
  day: "numeric",
  month: "long",
  hour: "2-digit",
  minute: "2-digit",
  hour12: false,
  timeZone: APP_TIME_ZONE,
});

export const dateOnly = new Intl.DateTimeFormat(LOCALE, {
  day: "numeric",
  month: "long",
  year: "numeric",
  timeZone: APP_TIME_ZONE,
});

export const timeOnly = new Intl.DateTimeFormat(LOCALE, {
  hour: "2-digit",
  minute: "2-digit",
  hour12: false,
  timeZone: APP_TIME_ZONE,
});

/** Algerian dinars, with the unit spelled rather than symbol-guessed. */
export function formatDzd(amount: number) {
  return `${new Intl.NumberFormat(LOCALE).format(amount)} DZD`;
}
