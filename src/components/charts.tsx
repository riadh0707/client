"use client";

import { useId, useState } from "react";

/**
 * Charts for the administration.
 *
 * Built as inline SVG rather than pulling a charting library: these are three
 * simple forms, and a dependency would add weight without adding correctness.
 *
 * Colour follows the dataviz method. Every chart here carries ONE measure, so it
 * uses ONE hue — #0a7a45, the design system's cross-700, validated against this
 * surface (lightness band, chroma floor, contrast ≥ 3:1). Category identity comes
 * from the axis labels, never from colour: giving five partner types five hues
 * would burn the colour channel on information the labels already carry, and no
 * five-hue set in this palette clears the colourblind separation threshold.
 *
 * Every chart ships a table view. A tooltip enhances; it never gates a value.
 */

const SERIES = "#0a7a45";
const GRID = "#d5ded8";
const AXIS_TEXT = "#7d8d85";

export type Point = { label: string; value: number };

function niceMax(value: number) {
  if (value <= 0) return 1;
  const magnitude = 10 ** Math.floor(Math.log10(value));
  return Math.ceil(value / magnitude) * magnitude;
}

function TableView({
  caption,
  rows,
  unit,
}: {
  caption: string;
  rows: Point[];
  unit: string;
}) {
  return (
    <details className="mt-3">
      <summary className="cursor-pointer font-display text-[11px] font-bold tracking-[0.1em] text-ink-500 uppercase hover:text-cross-700">
        Voir les données
      </summary>
      <div className="mt-2 max-h-64 overflow-y-auto border border-enamel-300">
        <table className="w-full border-collapse text-left text-sm">
          <caption className="sr-only">{caption}</caption>
          <thead>
            <tr className="border-b border-enamel-300">
              <th className="px-3 py-2 font-display text-[11px] font-bold tracking-[0.1em] text-ink-500 uppercase">
                Libellé
              </th>
              <th className="px-3 py-2 text-right font-display text-[11px] font-bold tracking-[0.1em] text-ink-500 uppercase">
                {unit}
              </th>
            </tr>
          </thead>
          <tbody className="ruled">
            {rows.map((row) => (
              <tr key={row.label}>
                <td className="px-3 py-1.5 text-ink-900">{row.label}</td>
                {/* tabular-nums belongs here, where numbers align vertically —
                    not on the hero figures, where equal-width digits read loose. */}
                <td className="px-3 py-1.5 text-right tabular-nums text-ink-900">
                  {row.value}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </details>
  );
}

/** Change over time, one series. */
export function LineChart({
  title,
  points,
  unit = "Nombre",
}: {
  title: string;
  points: Point[];
  unit?: string;
}) {
  const [active, setActive] = useState<number | null>(null);
  const clipId = useId();

  const width = 640;
  const height = 200;
  const padding = { top: 12, right: 12, bottom: 28, left: 40 };
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;

  const max = niceMax(Math.max(...points.map((p) => p.value), 1));
  const stepX = points.length > 1 ? plotWidth / (points.length - 1) : 0;

  const x = (index: number) => padding.left + index * stepX;
  const y = (value: number) => padding.top + plotHeight * (1 - value / max);

  const path = points
    .map((point, index) => `${index === 0 ? "M" : "L"} ${x(index)} ${y(point.value)}`)
    .join(" ");

  const ticks = [0, 0.5, 1].map((fraction) => Math.round(max * fraction));

  return (
    <figure className="min-w-0 bg-enamel-50 p-5">
      <figcaption className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
        {title}
      </figcaption>

      <div className="mt-3 overflow-x-auto">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="h-auto w-full min-w-[32rem]"
          role="img"
          aria-label={`${title}. ${points.map((p) => `${p.label} : ${p.value}`).join(", ")}.`}
        >
          <defs>
            <clipPath id={clipId}>
              <rect
                x={padding.left}
                y={padding.top}
                width={plotWidth}
                height={plotHeight}
              />
            </clipPath>
          </defs>

          {/* Solid hairlines, one shade off the surface. Never dashed: dashing
              reads as "projection" when it is only a grid. */}
          {ticks.map((tick) => (
            <g key={tick}>
              <line
                x1={padding.left}
                x2={width - padding.right}
                y1={y(tick)}
                y2={y(tick)}
                stroke={GRID}
                strokeWidth={1}
              />
              <text
                x={padding.left - 8}
                y={y(tick) + 4}
                textAnchor="end"
                fontSize={10}
                fill={AXIS_TEXT}
                className="tabular-nums"
              >
                {tick}
              </text>
            </g>
          ))}

          <path
            d={path}
            fill="none"
            stroke={SERIES}
            strokeWidth={2}
            strokeLinejoin="round"
            strokeLinecap="round"
            clipPath={`url(#${clipId})`}
          />

          {/* The endpoint is direct-labelled; the rest live on the axis and in
              the tooltip. A number on every point goes unread. */}
          {points.length > 0 &&
            (() => {
              const last = points[points.length - 1];
              const pointY = y(last.value);
              // When the final value tops the axis, a label placed above it is
              // clipped by the viewBox — so it flips below the point instead.
              const above = pointY - 10 >= padding.top + 10;
              return (
                <>
                  <circle
                    cx={x(points.length - 1)}
                    cy={pointY}
                    r={4}
                    fill={SERIES}
                    stroke="#fbfcfb"
                    strokeWidth={2}
                  />
                  <text
                    x={x(points.length - 1) - 6}
                    y={above ? pointY - 10 : pointY + 16}
                    textAnchor="end"
                    fontSize={11}
                    fontWeight={700}
                    fill="#0c1f17"
                  >
                    {last.value}
                  </text>
                </>
              );
            })()}

          {points.map((point, index) => (
            <g key={point.label}>
              {active === index && (
                <>
                  <line
                    x1={x(index)}
                    x2={x(index)}
                    y1={padding.top}
                    y2={padding.top + plotHeight}
                    stroke={GRID}
                    strokeWidth={1}
                  />
                  <circle
                    cx={x(index)}
                    cy={y(point.value)}
                    r={5}
                    fill={SERIES}
                    stroke="#fbfcfb"
                    strokeWidth={2}
                  />
                </>
              )}
              {/* Hit area spans the whole column: a 5px marker you must land on
                  dead-centre is not a target. */}
              <rect
                x={x(index) - stepX / 2}
                y={padding.top}
                width={Math.max(stepX, 24)}
                height={plotHeight}
                fill="transparent"
                onMouseEnter={() => setActive(index)}
                onMouseLeave={() => setActive(null)}
                onFocus={() => setActive(index)}
                onBlur={() => setActive(null)}
                tabIndex={0}
                role="button"
                aria-label={`${point.label} : ${point.value}`}
              />
            </g>
          ))}

          {points.map((point, index) =>
            index % Math.ceil(points.length / 6) === 0 ? (
              <text
                key={`tick-${point.label}`}
                x={x(index)}
                y={height - 8}
                textAnchor="middle"
                fontSize={10}
                fill={AXIS_TEXT}
              >
                {point.label}
              </text>
            ) : null,
          )}
        </svg>
      </div>

      <p
        aria-live="polite"
        className="mt-1 h-5 text-sm text-ink-600"
      >
        {active !== null
          ? `${points[active].label} : ${points[active].value}`
          : ""}
      </p>

      <TableView caption={title} rows={points} unit={unit} />
    </figure>
  );
}

/** Magnitude across named categories, one series, horizontal bars. */
export function BarChart({
  title,
  points,
  unit = "Nombre",
}: {
  title: string;
  points: Point[];
  unit?: string;
}) {
  const [active, setActive] = useState<string | null>(null);
  const max = Math.max(...points.map((p) => p.value), 1);

  return (
    <figure className="min-w-0 bg-enamel-50 p-5">
      <figcaption className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
        {title}
      </figcaption>

      <ul className="mt-4 flex flex-col gap-2.5">
        {points.map((point) => {
          const percent = (point.value / max) * 100;
          return (
            <li
              key={point.label}
              className="grid grid-cols-[minmax(6rem,10rem)_1fr_auto] items-center gap-3"
              onMouseEnter={() => setActive(point.label)}
              onMouseLeave={() => setActive(null)}
            >
              <span className="truncate text-sm text-ink-900" title={point.label}>
                {point.label}
              </span>
              {/* The track is the surface; the bar is a thin mark with rounded
                  data-ends, anchored to the baseline at the left. */}
              <span className="h-3 w-full bg-enamel-200">
                <span
                  className="block h-3 rounded-r-[4px] transition-opacity"
                  style={{
                    width: `${Math.max(percent, 1)}%`,
                    backgroundColor: SERIES,
                    opacity: active && active !== point.label ? 0.55 : 1,
                  }}
                />
              </span>
              <span className="text-right text-sm tabular-nums text-ink-600">
                {point.value}
              </span>
            </li>
          );
        })}
      </ul>

      <TableView caption={title} rows={points} unit={unit} />
    </figure>
  );
}
