/**
 * The Rod of Asclepius — DOCTORY's mark.
 *
 * It replaces the pharmacy cross this identity was first built on. The green
 * cross is the sign hanging over every Algerian pharmacy, but carried across a
 * whole platform as its emblem it reads as a Christian symbol to much of the
 * audience — the same reason the Red Crescent exists beside the Red Cross. The
 * rod is the older and the more precise sign in any case: it means medicine
 * itself rather than a dispensary, which fits a product that carries dentists,
 * laboratories and imaging centres as readily as pharmacies.
 *
 * Drawn for 16px first, then checked at every size it ships at up to the 19rem
 * hero. Three coils, not the fine spiral of the WHO emblem: at section-marker
 * size a thin spiral turns to grey mush. The staff is stroked last so it sits
 * over the serpent at every crossing — that overlap is what makes the wrap read
 * instead of looking like a wave set beside a bar.
 *
 * Colour comes from `currentColor`, as the previous mark did, so every call site
 * keeps setting it with an ordinary text colour.
 */
export function RodMark({
  className = "",
  title,
}: {
  className?: string;
  /** Only where the mark carries meaning that no adjacent text already gives. */
  title?: string;
}) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={className}
      role={title ? "img" : undefined}
      aria-hidden={title ? undefined : true}
      fill="none"
      stroke="currentColor"
      strokeWidth={2.5}
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      {title && <title>{title}</title>}
      {/* The serpent: three coils crossing the staff's line. */}
      <path d="M7.4 20.8c0-3.2 9.2-3.2 9.2-6.4s-9.2-3.2-9.2-6.4 9.2-3.2 9.2-5.6" />
      {/* The head — a short blunt stroke; a drawn eye disappears below 24px. */}
      <path d="M16.6 2.4l2.6-1.2" />
      {/* The staff, last, so it reads in front at each crossing. */}
      <path d="M12 1.9v20.2" />
    </svg>
  );
}
