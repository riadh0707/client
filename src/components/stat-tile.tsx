/**
 * A single figure with its label.
 *
 * Deliberately not a chart: a one-bar bar chart is an anti-pattern, and when the
 * story is one number the number is the chart.
 *
 * The figure carries proportional digits, not `tabular-nums` — equal-width digits
 * make a large standalone number read loose. Tabular figures belong in table rows
 * and axis ticks, where numbers align vertically.
 */
export function StatTile({
  label,
  value,
  hint,
  tone = "neutral",
}: {
  label: string;
  value: string | number;
  hint?: string;
  tone?: "neutral" | "attention";
}) {
  return (
    <div className="bg-enamel-50 p-5">
      <p className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
        {label}
      </p>
      <p
        className={`mt-2 font-display text-3xl font-bold ${
          tone === "attention" ? "text-carbon-amber" : "text-ink-900"
        }`}
      >
        {value}
      </p>
      {hint && <p className="mt-1 text-sm text-ink-500">{hint}</p>}
    </div>
  );
}
