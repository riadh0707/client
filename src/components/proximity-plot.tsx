"use client";

import { useState } from "react";
import { formatDistance } from "@/lib/hours";

/**
 * A schematic proximity plot: partners placed by true bearing and distance from
 * a centre point, north up.
 *
 * Deliberately not a tiled map. This environment cannot reach any tile provider,
 * and a Leaflet canvas with no tiles is a grey rectangle that promises a map and
 * delivers nothing. Choosing a provider (Google, Mapbox, a self-hosted OSM) is a
 * cost and contract decision for the client, not one to invent here — so this
 * shows real relative positions honestly, and directions hand off to a maps app
 * that does have the tiles.
 */

export type PlotPoint = {
  slug: string;
  name: string;
  lat: number;
  lng: number;
  distance: number;
};

export function ProximityPlot({
  centre,
  points,
  centreLabel,
}: {
  centre: { lat: number; lng: number };
  points: PlotPoint[];
  centreLabel: string;
}) {
  const [active, setActive] = useState<string | null>(null);

  if (points.length === 0) return null;

  const size = 320;
  const radius = size / 2 - 24;
  const maxDistance = Math.max(...points.map((p) => p.distance), 0.5);

  // Longitude degrees shrink with latitude; without the cosine correction an
  // east-west offset would plot far wider than it is on the ground.
  const scaleLng = Math.cos((centre.lat * Math.PI) / 180);

  const placed = points.map((point) => {
    const dx = (point.lng - centre.lng) * scaleLng;
    const dy = point.lat - centre.lat;
    const magnitude = Math.hypot(dx, dy) || 1;
    const ratio = Math.min(point.distance / maxDistance, 1);
    return {
      ...point,
      x: size / 2 + (dx / magnitude) * ratio * radius,
      // SVG y grows downward; north must point up.
      y: size / 2 - (dy / magnitude) * ratio * radius,
    };
  });

  const rings = [0.33, 0.66, 1];

  return (
    <figure className="bg-enamel-50 p-5">
      <figcaption className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
        Plan de proximité
      </figcaption>

      <svg
        viewBox={`0 0 ${size} ${size}`}
        className="mx-auto mt-3 h-auto w-full max-w-[20rem]"
        role="img"
        aria-label={`Positions relatives depuis ${centreLabel}. ${placed
          .map((p) => `${p.name}, ${formatDistance(p.distance)}`)
          .join(". ")}.`}
      >
        {rings.map((ring) => (
          <g key={ring}>
            <circle
              cx={size / 2}
              cy={size / 2}
              r={radius * ring}
              fill="none"
              stroke="#d5ded8"
              strokeWidth={1}
            />
            <text
              x={size / 2 + 4}
              y={size / 2 - radius * ring + 11}
              fontSize={9}
              fill="#a3b0a9"
              className="tabular-nums"
            >
              {formatDistance(maxDistance * ring)}
            </text>
          </g>
        ))}

        <text
          x={size / 2}
          y={12}
          textAnchor="middle"
          fontSize={10}
          fontWeight={700}
          fill="#7d8d85"
        >
          N
        </text>

        {/* The centre reads as the viewer's own position: the cross, filled. */}
        <circle cx={size / 2} cy={size / 2} r={5} fill="#0c1f17" />

        {placed.map((point) => (
          <g
            key={point.slug}
            onMouseEnter={() => setActive(point.slug)}
            onMouseLeave={() => setActive(null)}
          >
            <circle
              cx={point.x}
              cy={point.y}
              r={active === point.slug ? 7 : 5}
              fill="#00b85c"
              stroke="#fbfcfb"
              strokeWidth={2}
            />
            {/* Hit target well beyond the mark: a 5px dot is not a target.
                r=14 in a 320-unit viewBox rendered ~27px on a phone, under the
                44px floor; r=23 clears it at the widths this plot is shown at. */}
            <circle
              cx={point.x}
              cy={point.y}
              r={23}
              fill="transparent"
              tabIndex={0}
              role="button"
              aria-label={`${point.name}, ${formatDistance(point.distance)}`}
              onFocus={() => setActive(point.slug)}
              onBlur={() => setActive(null)}
            />
          </g>
        ))}
      </svg>

      <p aria-live="polite" className="mt-2 min-h-[2.5rem] text-center text-sm text-ink-600">
        {active
          ? `${placed.find((p) => p.slug === active)?.name} · ${formatDistance(
              placed.find((p) => p.slug === active)?.distance ?? 0,
            )}`
          : `Positions relatives depuis ${centreLabel}, nord en haut.`}
      </p>
    </figure>
  );
}
