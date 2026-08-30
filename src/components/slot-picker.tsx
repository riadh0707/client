"use client";

import { useState } from "react";
import { useFormStatus } from "react-dom";
import type { DayAvailability } from "@/lib/slots";

/**
 * Slot picker.
 *
 * Days are horizontal columns on desktop and a vertical list on mobile, because
 * a week-grid squeezed onto a 360px screen produces tap targets too small for
 * the one-handed outdoor use PRODUCT.md describes.
 *
 * The chosen slot rides in a hidden field so the whole thing is one ordinary
 * form post: no fetch, no client-side error plumbing, and it still works while
 * JavaScript is loading.
 */
export function SlotPicker({
  availability,
  services,
}: {
  availability: DayAvailability[];
  services: { id: string; name: string }[];
}) {
  const [selected, setSelected] = useState<string | null>(null);

  const daysWithSlots = availability.filter((day) => day.slots.length > 0);
  const anyAvailable = daysWithSlots.some((day) =>
    day.slots.some((slot) => slot.available),
  );

  if (!anyAvailable) {
    return (
      <div className="bg-enamel-50 p-6 text-center">
        <p className="font-display text-lg font-bold text-ink-900">
          Aucun créneau disponible
        </p>
        <p className="mx-auto mt-2 max-w-sm text-[15px] leading-relaxed text-ink-600">
          Ce praticien n&apos;a pas de créneau libre dans les quatorze prochains
          jours. Appelez le cabinet pour connaître ses prochaines disponibilités.
        </p>
      </div>
    );
  }

  return (
    <>
      <input type="hidden" name="startAt" value={selected ?? ""} />

      <div className="flex flex-col gap-px bg-ink-900/10">
        {daysWithSlots.map((day) => {
          const free = day.slots.filter((slot) => slot.available);
          return (
            <section key={day.date} className="bg-enamel-50 p-4 sm:p-5">
              <h3 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
                {day.label}
              </h3>

              {free.length === 0 ? (
                <p className="mt-2 text-sm text-ink-400">Complet</p>
              ) : (
                <ul className="mt-3 flex flex-wrap gap-2">
                  {free.map((slot) => {
                    const isSelected = selected === slot.startAt;
                    return (
                      <li key={slot.startAt}>
                        <button
                          type="button"
                          aria-pressed={isSelected}
                          onClick={() => setSelected(slot.startAt)}
                          className={
                            isSelected
                              ? "border border-cross-700 bg-cross-500 px-3 py-2.5 font-display text-sm font-bold tabular-nums text-cross-950"
                              : "border border-enamel-300 bg-white px-3 py-2.5 font-display text-sm tabular-nums text-ink-900 hover:border-cross-600 hover:text-cross-700"
                          }
                        >
                          {slot.label}
                        </button>
                      </li>
                    );
                  })}
                </ul>
              )}
            </section>
          );
        })}
      </div>

      <div className="bg-enamel-50 p-4 sm:p-5">
        {services.length > 0 && (
          <div className="mb-4">
            <label
              htmlFor="serviceName"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Motif
            </label>
            <select
              id="serviceName"
              name="serviceName"
              className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
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

        <SubmitButton disabled={!selected} />
      </div>
    </>
  );
}

function SubmitButton({ disabled }: { disabled: boolean }) {
  const { pending } = useFormStatus();
  return (
    <button
      type="submit"
      disabled={disabled || pending}
      className="mt-4 w-full bg-cross-500 px-4 py-3.5 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400 disabled:cursor-not-allowed disabled:bg-enamel-300 disabled:text-ink-400"
    >
      {pending
        ? "Envoi en cours…"
        : disabled
          ? "Choisissez un créneau"
          : "Demander ce rendez-vous"}
    </button>
  );
}
