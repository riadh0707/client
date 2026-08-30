"use client";

import { useState } from "react";
import { useFormStatus } from "react-dom";
import type { DayAvailability } from "@/lib/slots";

/**
 * Slot picker — one day at a time.
 *
 * An earlier version printed every free slot of the next fortnight at once:
 * 164 identical buttons, 3965px of page on a 390px phone, nearly five screens
 * of scroll before the submit button came into view. Choosing an appointment is
 * a two-part decision — which day, then what time — and flattening it into one
 * undifferentiated wall made both parts harder.
 *
 * So the day is chosen first, in a strip that shows how many slots each day
 * actually holds, and only that day's times are rendered. The strip is built
 * from links in the page above this component, so picking a day works with
 * JavaScript off; only the final time selection needs the client.
 *
 * Times are grouped into morning and afternoon. A patient asks for "Tuesday
 * morning", not "the eleventh button".
 *
 * The chosen slot is restated in full immediately above the submit button.
 * Committing to a medical appointment while the only trace of the choice is a
 * highlighted rectangle somewhere further up the page is how people book the
 * wrong hour.
 */
export function SlotPicker({
  day,
  initialSlot,
  partnerName,
  durationMinutes,
  services,
}: {
  day: DayAvailability;
  /** A slot carried back through the sign-in detour, re-selected on arrival. */
  initialSlot?: string | null;
  partnerName: string;
  durationMinutes: number;
  services: { id: string; name: string }[];
}) {
  const free = day.slots.filter((slot) => slot.available);

  // Only honour the resumed slot if it is still free: someone else may have
  // taken it while the patient was signing in.
  const [selected, setSelected] = useState<string | null>(
    initialSlot && free.some((slot) => slot.startAt === initialSlot)
      ? initialSlot
      : null,
  );
  const morning = free.filter((slot) => Number(slot.label.slice(0, 2)) < 12);
  const afternoon = free.filter((slot) => Number(slot.label.slice(0, 2)) >= 12);
  const chosen = free.find((slot) => slot.startAt === selected) ?? null;

  return (
    <>
      <input type="hidden" name="startAt" value={selected ?? ""} />

      <section className="bg-enamel-50 p-4 sm:p-5">
        <h2 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
          Horaire du {day.label}
        </h2>

        {free.length === 0 ? (
          <p className="mt-3 text-[15px] leading-relaxed text-ink-600">
            Aucun créneau libre ce jour-là. Choisissez une autre date
            ci-dessus.
          </p>
        ) : (
          <div className="mt-4 flex flex-col gap-5">
            <SlotGroup
              title="Matin"
              slots={morning}
              selected={selected}
              onSelect={setSelected}
            />
            <SlotGroup
              title="Après-midi"
              slots={afternoon}
              selected={selected}
              onSelect={setSelected}
            />
          </div>
        )}
      </section>

      {free.length > 0 && (
        <section className="bg-enamel-50 p-4 sm:p-5">
          {/* The commitment panel. Everything the patient is about to send,
              in words, in one place. */}
          <div
            aria-live="polite"
            className={
              chosen
                ? "border-l-4 border-cross-500 bg-white px-4 py-3"
                : "border-l-4 border-enamel-300 bg-white px-4 py-3"
            }
          >
            <span className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
              Votre demande
            </span>
            {chosen ? (
              <p className="mt-1 text-[15px] leading-snug text-ink-900">
                <strong className="font-display">
                  {day.label} à {chosen.label}
                </strong>
                <br />
                {partnerName} · {durationMinutes} minutes
              </p>
            ) : (
              <p className="mt-1 text-[15px] leading-snug text-ink-500">
                Choisissez une heure ci-dessus.
              </p>
            )}
          </div>

          {services.length > 0 && (
            <div className="mt-5">
              <label
                htmlFor="serviceName"
                className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
              >
                Motif
              </label>
              <select
                id="serviceName"
                name="serviceName"
                className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
              >
                <option value="">Consultation</option>
                {services.map((service) => (
                  <option key={service.id} value={service.name}>
                    {service.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="mt-5">
            <label
              htmlFor="reason"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Précisions (facultatif)
            </label>
            <textarea
              id="reason"
              name="reason"
              rows={3}
              maxLength={400}
              placeholder="Décrivez brièvement votre demande."
              className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900 placeholder:text-ink-300"
            />
          </div>

          <SubmitButton disabled={!chosen} />
        </section>
      )}
    </>
  );
}

/**
 * Morning or afternoon. The group is omitted entirely when empty rather than
 * shown as a heading over nothing.
 */
function SlotGroup({
  title,
  slots,
  selected,
  onSelect,
}: {
  title: string;
  slots: { startAt: string; label: string }[];
  selected: string | null;
  onSelect: (startAt: string) => void;
}) {
  if (slots.length === 0) return null;

  return (
    <div>
      <h3 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-400 uppercase">
        {title}
      </h3>
      <ul className="mt-2 grid grid-cols-[repeat(auto-fill,minmax(4.75rem,1fr))] gap-2">
        {slots.map((slot) => {
          const isSelected = selected === slot.startAt;
          return (
            <li key={slot.startAt}>
              <button
                type="button"
                aria-pressed={isSelected}
                onClick={() => onSelect(slot.startAt)}
                className={
                  isSelected
                    ? "flex min-h-11 w-full items-center justify-center border-2 border-cross-700 bg-cross-500 px-2 py-2.5 font-display text-sm font-bold tabular-nums text-cross-950"
                    : "flex min-h-11 w-full items-center justify-center border border-enamel-300 bg-white px-2 py-2.5 font-display text-sm tabular-nums text-ink-900 hover:border-cross-600 hover:text-cross-700"
                }
              >
                {slot.label}
              </button>
            </li>
          );
        })}
      </ul>
    </div>
  );
}

function SubmitButton({ disabled }: { disabled: boolean }) {
  const { pending } = useFormStatus();
  return (
    <button
      type="submit"
      disabled={disabled || pending}
      className="mt-5 min-h-11 w-full bg-cross-500 px-4 py-3.5 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400 disabled:cursor-not-allowed disabled:bg-enamel-300 disabled:text-ink-400"
    >
      {pending
        ? "Envoi en cours…"
        : disabled
          ? "Choisissez une heure"
          : "Demander ce rendez-vous"}
    </button>
  );
}
