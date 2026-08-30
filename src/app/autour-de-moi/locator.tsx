"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState, useTransition } from "react";

/**
 * Location chooser for the nearby surface.
 *
 * Geolocation is offered, never required — PRODUCT.md makes it optional and the
 * brief is explicit that refusing it must not cost the feature. Refusal falls
 * back to Wilaya then Commune, which is the product's primary axis anyway.
 */
export function Locator({
  wilayas,
  communes,
}: {
  wilayas: { code: number; name: string }[];
  communes: { code: number; name: string }[];
}) {
  const router = useRouter();
  const params = useSearchParams();
  const [pending, startTransition] = useTransition();
  const [state, setState] = useState<"idle" | "asking" | "denied" | "unsupported">("idle");

  function push(next: Record<string, string | null>) {
    const query = new URLSearchParams(params.toString());
    for (const [key, value] of Object.entries(next)) {
      if (value === null || value === "") query.delete(key);
      else query.set(key, value);
    }
    startTransition(() => router.push(`/autour-de-moi?${query}`));
  }

  function locate() {
    if (!("geolocation" in navigator)) {
      setState("unsupported");
      return;
    }
    setState("asking");
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setState("idle");
        push({
          lat: position.coords.latitude.toFixed(5),
          lng: position.coords.longitude.toFixed(5),
          wilaya: null,
          commune: null,
        });
      },
      () => setState("denied"),
      { timeout: 10000 },
    );
  }

  const usingPosition = Boolean(params.get("lat"));

  return (
    <div className={`bg-enamel-50 p-5 ${pending ? "opacity-60" : ""}`}>
      <div className="flex flex-wrap items-end gap-4">
        <div>
          <span className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
            Ma position
          </span>
          {usingPosition ? (
            <button
              type="button"
              onClick={() => push({ lat: null, lng: null })}
              className="border border-enamel-300 px-3 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-ink-600 uppercase hover:border-carbon-rose hover:text-carbon-rose"
            >
              Ne plus utiliser ma position
            </button>
          ) : (
            <button
              type="button"
              onClick={locate}
              disabled={state === "asking"}
              className="border border-cross-700 px-3 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100 disabled:opacity-60"
            >
              {state === "asking" ? "Localisation…" : "Me localiser"}
            </button>
          )}
        </div>

        <div className="min-w-[12rem] flex-1">
          <label htmlFor="loc-wilaya" className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
            Wilaya
          </label>
          <select
            id="loc-wilaya"
            value={params.get("wilaya") ?? ""}
            onChange={(e) => push({ wilaya: e.target.value, commune: null, lat: null, lng: null })}
            className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
          >
            <option value="">Choisir une wilaya</option>
            {wilayas.map((w) => (
              <option key={w.code} value={w.code}>
                {String(w.code).padStart(2, "0")} · {w.name}
              </option>
            ))}
          </select>
        </div>

        {communes.length > 0 && (
          <div className="min-w-[12rem] flex-1">
            <label htmlFor="loc-commune" className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
              Commune
            </label>
            <select
              id="loc-commune"
              value={params.get("commune") ?? ""}
              onChange={(e) => push({ commune: e.target.value })}
              className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
            >
              <option value="">Toute la wilaya</option>
              {communes.map((c) => (
                <option key={c.code} value={c.code}>{c.name}</option>
              ))}
            </select>
          </div>
        )}
      </div>

      {state === "denied" && (
        <p className="mt-3 text-[15px] text-ink-600">
          Position refusée — aucun problème. Choisissez votre wilaya ci-dessus&nbsp;:
          la recherche par proximité fonctionne sans géolocalisation.
        </p>
      )}
      {state === "unsupported" && (
        <p className="mt-3 text-[15px] text-ink-600">
          Votre navigateur ne propose pas la géolocalisation. Utilisez le choix
          de wilaya.
        </p>
      )}
    </div>
  );
}
