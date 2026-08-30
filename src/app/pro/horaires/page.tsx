import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { getCurrentUser, partnerPermissions } from "@/lib/auth";
import { requirePartnerContext } from "@/lib/pro";
import { weekdayLabel, type Interval } from "@/lib/hours";
import { APP_TIME_ZONE, zonedParts, zonedTimeToInstant } from "@/lib/time";
import { dateOnly } from "@/lib/format";

export const metadata: Metadata = { title: "Horaires" };
export const dynamic = "force-dynamic";

/**
 * Opening hours and exceptional closures.
 *
 * Until this page existed, a practitioner was told at registration that they
 * could set their hours, and the profile page then said to write to the
 * administration instead. Hours are not decoration here: they drive "open right
 * now" on every result card and the whole bookable grid, so leaving them
 * editable only by a database seed made the professional space a viewer.
 *
 * Two intervals a day is the Algerian norm — 08:00-12:00, 14:00-17:00 — and the
 * schema allows any number, so the editor renders every interval a day already
 * has plus one empty pair. Filling the blank pair adds an interval; clearing
 * both fields of a row removes it. No JavaScript, no repeater widget, and a
 * practice with three intervals on a Tuesday cannot silently lose the third.
 *
 * The working week is Sunday to Thursday plus Saturday; Friday is the day off.
 * That is why the "copy across the week" shortcut names the days it touches
 * instead of saying "all week" and quietly opening a Friday.
 */

/** The ordinary working days, Friday excluded. */
const WORKING_DAYS = [0, 1, 2, 3, 4, 6];

const TIME = /^([01]\d|2[0-3]):[0-5]\d$/;

function toMinutes(time: string) {
  const [h, m] = time.split(":").map(Number);
  return h * 60 + m;
}

/** Reads one weekday's interval rows out of the posted form. */
function readDay(formData: FormData, weekday: number) {
  const rows: { opensAt: string; closesAt: string }[] = [];
  for (let index = 0; index < 12; index += 1) {
    const opensAt = String(formData.get(`d${weekday}-${index}-open`) ?? "").trim();
    const closesAt = String(formData.get(`d${weekday}-${index}-close`) ?? "").trim();
    // Both blank is a deleted row, and a row that never existed.
    if (!opensAt && !closesAt) continue;
    rows.push({ opensAt, closesAt });
  }
  return rows;
}

async function saveHours(formData: FormData) {
  "use server";

  const partnerId = String(formData.get("partnerId") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fhoraires");
  // Hours belong to the owner, like the rest of the profile: a secretary runs
  // the agenda within them, they do not redefine when the practice is open.
  if (!partnerPermissions(user, partnerId).canManageProfile) {
    redirect("/pro?erreur=droits");
  }

  const copyFromSunday = String(formData.get("intent") ?? "") === "copier";

  const perDay = new Map<number, { opensAt: string; closesAt: string }[]>();
  for (let weekday = 0; weekday < 7; weekday += 1) {
    perDay.set(weekday, readDay(formData, weekday));
  }

  if (copyFromSunday) {
    const source = perDay.get(0) ?? [];
    for (const weekday of WORKING_DAYS) {
      if (weekday !== 0) perDay.set(weekday, source.map((row) => ({ ...row })));
    }
  }

  const fail = (message: string) =>
    redirect(`/pro/horaires?erreur=${encodeURIComponent(message)}`);

  for (const [weekday, rows] of perDay) {
    const day = weekdayLabel(weekday).toLowerCase();
    for (const row of rows) {
      if (!TIME.test(row.opensAt) || !TIME.test(row.closesAt)) {
        fail(`${day} : renseignez une heure d'ouverture et de fermeture valides, ou laissez les deux vides.`);
      }
      if (toMinutes(row.opensAt) >= toMinutes(row.closesAt)) {
        fail(`${day} : la fermeture (${row.closesAt}) doit suivre l'ouverture (${row.opensAt}).`);
      }
    }
    // Overlapping intervals would make the same slot appear twice in the grid.
    const sorted = [...rows].sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt));
    for (let i = 1; i < sorted.length; i += 1) {
      if (toMinutes(sorted[i].opensAt) < toMinutes(sorted[i - 1].closesAt)) {
        fail(`${day} : deux plages se chevauchent (${sorted[i - 1].opensAt}–${sorted[i - 1].closesAt} et ${sorted[i].opensAt}–${sorted[i].closesAt}).`);
      }
    }
    perDay.set(weekday, sorted);
  }

  // Replaced wholesale inside a transaction: a partial write would leave a
  // practice half-open, and the grid is derived from these rows alone.
  await db.$transaction(async (tx) => {
    await tx.openingHours.deleteMany({ where: { partnerId } });
    const data = [...perDay.entries()].flatMap(([weekday, rows]) =>
      rows.map((row) => ({ partnerId, weekday, ...row })),
    );
    if (data.length > 0) await tx.openingHours.createMany({ data });
  });

  revalidatePath("/pro/horaires");
  revalidatePath("/pro");
  redirect("/pro/horaires?enregistre=1");
}

async function addClosure(formData: FormData) {
  "use server";

  const partnerId = String(formData.get("partnerId") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fhoraires");
  if (!partnerPermissions(user, partnerId).canManageProfile) {
    redirect("/pro?erreur=droits");
  }

  const from = String(formData.get("from") ?? "");
  const to = String(formData.get("to") ?? "");
  const reason = String(formData.get("reason") ?? "").trim();

  const fail = (message: string) =>
    redirect(`/pro/horaires?erreur=${encodeURIComponent(message)}#fermetures`);

  const parse = (value: string) => {
    const [y, m, d] = value.split("-").map(Number);
    return [y, m, d];
  };
  if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to)) {
    fail("Indiquez une date de début et une date de fin.");
  }

  const [fy, fm, fd] = parse(from);
  const [ty, tm, td] = parse(to);
  // Algerian wall-clock, both ends: the closure covers whole local days, so a
  // host-local midnight would clip an hour off each edge.
  const startAt = zonedTimeToInstant(fy, fm, fd, 0, 0);
  const endAt = zonedTimeToInstant(ty, tm, td, 23, 59);
  if (endAt <= startAt) fail("La fin de la fermeture doit suivre son début.");

  await db.timeOff.create({
    data: { partnerId, startAt, endAt, reason: reason || null },
  });

  revalidatePath("/pro/horaires");
  redirect("/pro/horaires?ferme=1#fermetures");
}

async function removeClosure(formData: FormData) {
  "use server";

  const id = String(formData.get("id") ?? "");
  const partnerId = String(formData.get("partnerId") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fhoraires");
  if (!partnerPermissions(user, partnerId).canManageProfile) {
    redirect("/pro?erreur=droits");
  }

  // Scoped to the caller's own partner: without partnerId in the filter, any
  // owner could delete another practice's closure by posting an id.
  await db.timeOff.deleteMany({ where: { id, partnerId } });

  revalidatePath("/pro/horaires");
  redirect("/pro/horaires?rouvert=1#fermetures");
}

export default async function HoursPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const query = await searchParams;
  const { partner, permissions } = await requirePartnerContext("/pro/horaires");
  if (!partner) redirect("/pro");
  if (!permissions.canManageProfile) redirect("/pro?erreur=droits");

  const now = new Date();
  const [hours, closures, upcoming] = await Promise.all([
    db.openingHours.findMany({
      where: { partnerId: partner.id },
      select: { weekday: true, opensAt: true, closesAt: true },
    }),
    db.timeOff.findMany({
      where: { partnerId: partner.id, endAt: { gte: now } },
      orderBy: { startAt: "asc" },
      select: { id: true, startAt: true, endAt: true, reason: true },
    }),
    db.appointment.findMany({
      where: {
        partnerId: partner.id,
        startAt: { gte: now },
        status: { in: ["PENDING", "CONFIRMED"] },
      },
      select: { id: true, startAt: true },
    }),
  ]);

  const byDay = (weekday: number) =>
    (hours as Interval[])
      .filter((interval) => interval.weekday === weekday)
      .sort((a, b) => toMinutes(a.opensAt) - toMinutes(b.opensAt));

  // Appointments already booked outside the hours as they now stand. Editing
  // hours does not cancel anything, so saying nothing would let a practitioner
  // believe a slot they just closed had emptied itself.
  const orphaned = upcoming.filter((appointment) => {
    const parts = zonedParts(appointment.startAt);
    const minutes = parts.minutesOfDay;
    return !byDay(parts.weekday).some(
      (interval) =>
        minutes >= toMinutes(interval.opensAt) &&
        minutes < toMinutes(interval.closesAt),
    );
  });

  const error = typeof query.erreur === "string" ? query.erreur : null;
  const today = zonedParts(now);
  const todayIso = `${today.year}-${String(today.month).padStart(2, "0")}-${String(today.day).padStart(2, "0")}`;

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Horaires
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">
        {partner.displayName} · heures locales ({APP_TIME_ZONE.split("/")[1]})
      </p>

      {error && (
        <p
          role="alert"
          className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] leading-snug text-ink-900"
        >
          {error}
        </p>
      )}
      {query.enregistre && (
        <p
          role="status"
          className="mt-5 border border-cross-600/40 bg-cross-100 px-4 py-3 text-[15px] text-ink-900"
        >
          Horaires enregistrés. La grille de rendez-vous proposée aux patients
          est mise à jour.
        </p>
      )}
      {query.ferme && (
        <p
          role="status"
          className="mt-5 border border-cross-600/40 bg-cross-100 px-4 py-3 text-[15px] text-ink-900"
        >
          Fermeture enregistrée. Aucun créneau n&apos;est proposé sur cette
          période.
        </p>
      )}
      {query.rouvert && (
        <p
          role="status"
          className="mt-5 border border-cross-600/40 bg-cross-100 px-4 py-3 text-[15px] text-ink-900"
        >
          Fermeture supprimée. Les créneaux de cette période redeviennent
          disponibles.
        </p>
      )}

      {orphaned.length > 0 && (
        <p className="mt-5 border border-carbon-amber/50 bg-carbon-amber-soft px-4 py-3 text-[15px] leading-relaxed text-ink-900">
          <strong className="font-display">
            {orphaned.length} rendez-vous à venir
          </strong>{" "}
          {orphaned.length === 1 ? "tombe" : "tombent"} en dehors de ces
          horaires. Modifier vos horaires n&apos;annule aucun rendez-vous déjà
          pris&nbsp;:{" "}
          <Link
            href="/pro/agenda"
            className="font-bold underline underline-offset-4"
          >
            vérifiez votre agenda
          </Link>
          .
        </p>
      )}

      {/* ---- Weekly hours ---- */}
      <form action={saveHours} className="mt-8">
        <input type="hidden" name="partnerId" value={partner.id} />

        <div className="flex flex-col gap-px bg-ink-900/10">
          {Array.from({ length: 7 }, (_, weekday) => {
            const intervals = byDay(weekday);
            // Every existing interval, plus a blank pair to add one. Two rows
            // minimum, so the usual morning/afternoon split needs no round trip.
            const rowCount = Math.max(intervals.length + 1, 2);

            return (
              <fieldset key={weekday} className="bg-enamel-50 p-4 sm:p-5">
                <legend className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
                  {weekdayLabel(weekday)}
                  {weekday === 5 && (
                    <span className="ml-2 font-normal tracking-normal text-ink-400 normal-case">
                      jour de repos habituel
                    </span>
                  )}
                </legend>

                <div className="mt-3 flex flex-col gap-2.5">
                  {Array.from({ length: rowCount }, (_, index) => {
                    const interval = intervals[index];
                    return (
                      <div
                        key={index}
                        className="flex flex-wrap items-center gap-2"
                      >
                        <label
                          htmlFor={`d${weekday}-${index}-open`}
                          className="sr-only"
                        >
                          {weekdayLabel(weekday)}, plage {index + 1}, ouverture
                        </label>
                        <input
                          id={`d${weekday}-${index}-open`}
                          name={`d${weekday}-${index}-open`}
                          type="time"
                          defaultValue={interval?.opensAt ?? ""}
                          className="min-h-11 border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
                        />
                        <span aria-hidden className="text-ink-400">
                          &ndash;
                        </span>
                        <label
                          htmlFor={`d${weekday}-${index}-close`}
                          className="sr-only"
                        >
                          {weekdayLabel(weekday)}, plage {index + 1}, fermeture
                        </label>
                        <input
                          id={`d${weekday}-${index}-close`}
                          name={`d${weekday}-${index}-close`}
                          type="time"
                          defaultValue={interval?.closesAt ?? ""}
                          className="min-h-11 border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
                        />
                        {!interval && index >= intervals.length && (
                          <span className="text-sm text-ink-400">
                            {intervals.length === 0 && index === 0
                              ? "fermé"
                              : "ajouter une plage"}
                          </span>
                        )}
                      </div>
                    );
                  })}
                </div>
              </fieldset>
            );
          })}
        </div>

        <p className="mt-3 text-sm leading-relaxed text-ink-500">
          Laissez une plage vide pour la supprimer. Un jour sans aucune plage est
          fermé, et n&apos;affiche pas «&nbsp;fermé&nbsp;» au hasard&nbsp;: un
          profil sans aucun horaire indique simplement que ses horaires ne sont
          pas renseignés.
        </p>

        <div className="mt-5 flex flex-col gap-3 sm:flex-row">
          <button
            type="submit"
            className="min-h-11 bg-cross-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400"
          >
            Enregistrer les horaires
          </button>
          {/* Names the days it touches. "Toute la semaine" would quietly open a
              Friday for a practice that closes on Fridays. */}
          <button
            type="submit"
            name="intent"
            value="copier"
            className="min-h-11 border border-cross-700 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-cross-700 uppercase hover:bg-cross-100"
          >
            Recopier dimanche sur lun.–jeu. et sam.
          </button>
        </div>
      </form>

      {/* ---- Exceptional closures ---- */}
      <section id="fermetures" className="mt-12">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Fermetures exceptionnelles
        </h2>
        <p className="mt-1.5 text-[15px] leading-relaxed text-ink-600">
          Congés, formation, journée fermée. Aucun créneau n&apos;est proposé aux
          patients sur ces périodes.
        </p>

        {closures.length > 0 && (
          <ul className="ruled mt-4 border-y border-enamel-300">
            {closures.map((closure) => (
              <li
                key={closure.id}
                className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3"
              >
                <span className="min-w-0">
                  <span className="block font-display font-bold tabular-nums text-ink-900">
                    {dateOnly.format(closure.startAt)} &ndash;{" "}
                    {dateOnly.format(closure.endAt)}
                  </span>
                  {closure.reason && (
                    <span className="block text-sm text-ink-500">
                      {closure.reason}
                    </span>
                  )}
                </span>
                <form action={removeClosure}>
                  <input type="hidden" name="id" value={closure.id} />
                  <input
                    type="hidden"
                    name="partnerId"
                    value={partner.id}
                  />
                  <button
                    type="submit"
                    className="inline-flex min-h-11 items-center border border-carbon-rose/60 px-3 py-2 font-display text-xs font-bold tracking-[0.08em] text-carbon-rose uppercase hover:bg-carbon-rose-soft"
                  >
                    Supprimer
                  </button>
                </form>
              </li>
            ))}
          </ul>
        )}

        <form
          action={addClosure}
          className="mt-5 flex flex-col gap-4 bg-enamel-50 p-4 sm:p-5"
        >
          <input type="hidden" name="partnerId" value={partner.id} />
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label
                htmlFor="from"
                className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
              >
                Du
              </label>
              <input
                id="from"
                name="from"
                type="date"
                min={todayIso}
                required
                className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
              />
            </div>
            <div>
              <label
                htmlFor="to"
                className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
              >
                Au (inclus)
              </label>
              <input
                id="to"
                name="to"
                type="date"
                min={todayIso}
                required
                className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
              />
            </div>
          </div>
          <div>
            <label
              htmlFor="reason"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Motif (facultatif)
            </label>
            <input
              id="reason"
              name="reason"
              maxLength={120}
              placeholder="Congés annuels"
              className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] text-ink-900 placeholder:text-ink-300"
            />
            <p className="mt-1.5 text-sm text-ink-500">
              Pour votre usage. Le motif n&apos;est pas affiché aux patients.
            </p>
          </div>
          <button
            type="submit"
            className="min-h-11 self-start border border-cross-700 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-cross-700 uppercase hover:bg-cross-100"
          >
            Ajouter une fermeture
          </button>
        </form>
      </section>
    </main>
  );
}
