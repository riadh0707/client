"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState, useTransition } from "react";

type Option = { value: string; label: string; count?: number };

/**
 * Search filters.
 *
 * On mobile the panel is collapsed behind a button: the brief requires filters
 * to stay usable on a phone, and a permanently expanded column would push the
 * results themselves below two screens of controls.
 *
 * Geolocation is offered here, never demanded. PRODUCT.md makes it optional, so
 * refusing it costs nothing — Wilaya/Commune remains the primary axis and the
 * refusal is reported plainly rather than silently swallowed.
 */
export function SearchFilters({
  categories,
  wilayas,
  communes,
  specialties,
}: {
  categories: Option[];
  wilayas: Option[];
  communes: Option[];
  specialties: Option[];
}) {
  const router = useRouter();
  const params = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const [open, setOpen] = useState(false);
  const [geoState, setGeoState] = useState<
    "idle" | "asking" | "granted" | "denied" | "unsupported"
  >(params.get("lat") ? "granted" : "idle");

  function update(next: Record<string, string | null>) {
    const query = new URLSearchParams(params.toString());
    for (const [key, value] of Object.entries(next)) {
      if (value === null || value === "") query.delete(key);
      else query.set(key, value);
    }
    // Any filter change invalidates the current page.
    query.delete("page");
    startTransition(() => router.push(`/recherche?${query}`));
  }

  function requestLocation() {
    if (!("geolocation" in navigator)) {
      setGeoState("unsupported");
      return;
    }
    setGeoState("asking");
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setGeoState("granted");
        update({
          lat: position.coords.latitude.toFixed(5),
          lng: position.coords.longitude.toFixed(5),
        });
      },
      () => setGeoState("denied"),
      { timeout: 10000 },
    );
  }

  const activeCount = [
    "categorie",
    "wilaya",
    "commune",
    "specialite",
    "verifie",
    "ouvert",
    "lat",
  ].filter((key) => params.get(key)).length;

  return (
    <div className="bg-enamel-50">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        aria-expanded={open}
        className="flex w-full items-center justify-between gap-3 border-b border-enamel-300 px-5 py-4 font-display text-sm font-bold tracking-[0.08em] text-ink-900 uppercase lg:hidden"
      >
        Filtres
        <span className="flex items-center gap-2 font-normal tracking-normal normal-case">
          {activeCount > 0 && (
            <span className="bg-azur-500 px-2 py-0.5 font-display text-xs font-bold text-azur-950">
              {activeCount}
            </span>
          )}
          <span aria-hidden>{open ? "▲" : "▼"}</span>
        </span>
      </button>

      <div
        className={`${open ? "block" : "hidden"} lg:block ${isPending ? "opacity-60" : ""}`}
      >
        <div className="flex flex-col gap-6 p-5">
          <Field
            id="filter-categorie"
            label="Type de partenaire"
            value={params.get("categorie") ?? ""}
            // Changing category invalidates the specialty, which is scoped to it.
            onChange={(value) => update({ categorie: value, specialite: null })}
            options={categories}
            emptyLabel="Tous les types"
          />

          {specialties.length > 0 && (
            <Field
              id="filter-specialite"
              label="Spécialité"
              value={params.get("specialite") ?? ""}
              onChange={(value) => update({ specialite: value })}
              options={specialties}
              emptyLabel="Toutes les spécialités"
            />
          )}

          <Field
            id="filter-wilaya"
            label="Wilaya"
            value={params.get("wilaya") ?? ""}
            onChange={(value) => update({ wilaya: value, commune: null })}
            options={wilayas}
            emptyLabel="Toute l'Algérie"
          />

          {communes.length > 0 && (
            <Field
              id="filter-commune"
              label="Commune"
              value={params.get("commune") ?? ""}
              onChange={(value) => update({ commune: value })}
              options={communes}
              emptyLabel="Toutes les communes"
            />
          )}

          <fieldset className="flex flex-col gap-3">
            <legend className="mb-1 font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
              Affiner
            </legend>
            <Toggle
              id="filter-ouvert"
              label="Ouvert maintenant"
              checked={params.get("ouvert") === "1"}
              onChange={(checked) => update({ ouvert: checked ? "1" : null })}
            />
            <Toggle
              id="filter-verifie"
              label="Profil vérifié uniquement"
              checked={params.get("verifie") === "1"}
              onChange={(checked) => update({ verifie: checked ? "1" : null })}
            />
          </fieldset>

          <div className="border-t border-enamel-300 pt-5">
            <span className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
              Autour de moi
            </span>
            {geoState === "granted" ? (
              <div className="mt-2.5 flex flex-col gap-2">
                <p className="text-sm text-azur-700">
                  Résultats triés par distance.
                </p>
                <button
                  type="button"
                  onClick={() => {
                    setGeoState("idle");
                    update({ lat: null, lng: null });
                  }}
                  className="inline-flex min-h-11 items-center self-start font-display text-xs font-bold tracking-[0.08em] text-ink-600 uppercase underline underline-offset-4"
                >
                  Désactiver
                </button>
              </div>
            ) : (
              <div className="mt-2.5 flex flex-col gap-2">
                <button
                  type="button"
                  onClick={requestLocation}
                  disabled={geoState === "asking"}
                  className="inline-flex min-h-11 items-center self-start border border-azur-700 px-3 py-2 font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:bg-azur-100 disabled:opacity-60"
                >
                  {geoState === "asking"
                    ? "Localisation…"
                    : "Utiliser ma position"}
                </button>
                {geoState === "denied" && (
                  <p className="text-sm text-ink-600">
                    Position refusée. Choisissez une wilaya ci-dessus&nbsp;: la
                    recherche fonctionne sans géolocalisation.
                  </p>
                )}
                {geoState === "unsupported" && (
                  <p className="text-sm text-ink-600">
                    Votre navigateur ne propose pas la géolocalisation. Utilisez
                    le filtre par wilaya.
                  </p>
                )}
              </div>
            )}
          </div>

          {activeCount > 0 && (
            <button
              type="button"
              onClick={() => {
                const query = new URLSearchParams();
                const q = params.get("q");
                if (q) query.set("q", q);
                setGeoState("idle");
                startTransition(() => router.push(`/recherche?${query}`));
              }}
              className="inline-flex min-h-11 items-center self-start font-display text-xs font-bold tracking-[0.08em] text-carbon-rose uppercase underline underline-offset-4"
            >
              Réinitialiser les filtres
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

function Field({
  id,
  label,
  value,
  onChange,
  options,
  emptyLabel,
}: {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: Option[];
  emptyLabel: string;
}) {
  return (
    <div>
      <label
        htmlFor={id}
        className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
      >
        {label}
      </label>
      <select
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
      >
        <option value="">{emptyLabel}</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
            {option.count !== undefined ? ` (${option.count})` : ""}
          </option>
        ))}
      </select>
    </div>
  );
}

function Toggle({
  id,
  label,
  checked,
  onChange,
}: {
  id: string;
  label: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
}) {
  return (
    <label
      htmlFor={id}
      className="flex min-h-11 cursor-pointer items-center gap-3 text-[15px] text-ink-900"
    >
      <input
        id={id}
        type="checkbox"
        checked={checked}
        onChange={(event) => onChange(event.target.checked)}
        className="h-5 w-5 shrink-0 accent-azur-600"
      />
      {label}
    </label>
  );
}
