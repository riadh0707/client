"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

type WilayaOption = { code: number; name: string };

/**
 * The search instrument: one ruled line, query then place then action.
 *
 * It reads as a single row on desktop — the plaque's engraved line — and stacks
 * on mobile, where the brief says the experience is designed on its own terms.
 * Geolocation is deliberately absent here: PRODUCT.md makes it optional and
 * secondary to Wilaya→Commune, so it belongs on the results page as an offer,
 * not as a gate on the front door.
 */
export function SearchInstrument({
  wilayas,
  autoFocus = false,
}: {
  wilayas: WilayaOption[];
  autoFocus?: boolean;
}) {
  const router = useRouter();
  const [query, setQuery] = useState("");
  const [wilaya, setWilaya] = useState("");

  function submit(event: React.FormEvent) {
    event.preventDefault();
    const params = new URLSearchParams();
    if (query.trim()) params.set("q", query.trim());
    if (wilaya) params.set("wilaya", wilaya);
    router.push(`/recherche${params.size > 0 ? `?${params}` : ""}`);
  }

  return (
    <form
      onSubmit={submit}
      className="flex w-full flex-col gap-px bg-ink-900/15 sm:flex-row"
    >
      <div className="flex-[2] bg-enamel-50">
        <label
          htmlFor="search-query"
          className="block px-4 pt-3 font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
        >
          Qui cherchez-vous&nbsp;?
        </label>
        <input
          id="search-query"
          name="q"
          type="search"
          autoFocus={autoFocus}
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Cardiologue, pharmacie, laboratoire…"
          className="w-full bg-transparent px-4 pt-1 pb-3 text-lg text-ink-900 outline-none placeholder:text-ink-300"
        />
      </div>

      <div className="flex-1 bg-enamel-50">
        <label
          htmlFor="search-wilaya"
          className="block px-4 pt-3 font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
        >
          Où&nbsp;?
        </label>
        <select
          id="search-wilaya"
          name="wilaya"
          value={wilaya}
          onChange={(event) => setWilaya(event.target.value)}
          className="w-full appearance-none bg-transparent px-4 pt-1 pb-3 text-lg text-ink-900 outline-none"
        >
          <option value="">Toute l&apos;Algérie</option>
          {wilayas.map((w) => (
            <option key={w.code} value={w.code}>
              {String(w.code).padStart(2, "0")} — {w.name}
            </option>
          ))}
        </select>
      </div>

      <button
        type="submit"
        className="bg-cross-500 px-8 py-4 font-display text-base font-bold tracking-wide text-cross-950 uppercase transition-colors hover:bg-cross-400 sm:py-0"
      >
        Rechercher
      </button>
    </form>
  );
}
