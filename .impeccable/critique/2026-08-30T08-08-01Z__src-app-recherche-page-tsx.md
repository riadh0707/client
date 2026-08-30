---
timestamp: 2026-08-30T08-08-01Z
slug: src-app-recherche-page-tsx
---
# Critique — DOCTORY (parcours patient + admin)

Provenance: two isolated sub-agents (A design review, B detector/browser evidence).
Not degraded. Viewports 1280x900, 390x844, 360x780.

## Heuristic scores (Nielsen, 0-4)
1 Visibility of system status .......... 1
2 Match to the real world .............. 4
3 User control and freedom ............. 1
4 Consistency and standards ............ 3
5 Error prevention ..................... 1
6 Recognition over recall .............. 2
7 Flexibility and efficiency ........... 1
8 Aesthetic and minimalist design ...... 3
9 Errors: recognise/diagnose/recover ... 1
10 Help and documentation .............. n/a
Mean of scored items: 1.9 / 4

## Design specificity
Delivered, not drifted, on form. The direction contract's five commitments are
honoured; the cross module, the Wilaya-Commune spine and the split-day hours
model are product-specific and could not be lifted to another product.
Undermined on substance by the timezone defect.

## Priority issues (verified by the parent before publication)
P1 Timezone absent everywhere. Container UTC vs Africa/Algiers = 1h offset.
   Affects open/closed, next opening, bookable slots, every displayed time.
   Zero occurrences of Africa/Algiers | timeZone | TZ= in src/.
P2 Sponsored results exempt from the user's own "ouvert" filter and from
   distance sort (search.ts: filter applies to organic only, line ~132 vs
   sponsored passthrough line ~148). Contradicts PRODUCT.md's no-covert-
   manipulation constraint.
P3 Pagination phantom page: total counts sponsored, organic excludes them
   (count uses `where`; organic uses AND[where, notIn sponsoredIds]).
   14 results + PAGE_SIZE 12 => page 2 exists and is empty. Empty-state advice
   inverted (hasFilters={results.total === 0}).
P4 No focus indicator on the primary search control: outline-none at
   search-instrument.tsx:63 and :79 beats the base :focus-visible rule.
   Focus ring elsewhere measures 2.06:1 on hero, 2.43:1 on body (needs 3:1).
P5 No destructive-action confirmation anywhere (patient cancel, admin
   suspend/reject). One tap, irreversible, no undo.
P6 No registration path. Both landing role plaques terminate at a login wall.
P7 Regression introduced by the parent's own audit pass: "Autour de moi"
   unreachable on mobile from every page except the landing
   (site-header.tsx nav container gated lg:flex/xl:flex).
P8 Touch targets: 12 map pins 27.1x27.1; header links 16px tall; breadcrumbs
   17px; slot buttons 42px (miss by 2). No horizontal overflow at 390px.
P9 Booking screen: 168 identical slot buttons, 3810px tall on a phone, chosen
   slot never restated at the moment of commitment.
P10 No not-found.tsx, no error.tsx, no loading.tsx anywhere.

## Correction to a previous claim
The flat-type-hierarchy finding reported in the earlier audit pass was measured
on a Next.js 404 page (stale slug dr-mehdi-benali), not on the partner profile.
It does not reproduce on any real page at either viewport.

## Confirmed strengths
- Proximity plot: honest solve for an absent map provider, states its limits.
- Honesty engineered in code: unknown-not-closed hours, DB-backed counts,
  sponsored fetched as a separate list, pricing disclaimers.
- Contrast computed rather than eyeballed; table view behind every chart.

## False positives (agent B, accepted)
- disabled submit button contrast 3.5:1 (WCAG exempts disabled controls)
- net::ERR_ABORTED on RSC prefetch (tab close, not a failure)
- flat-type-hierarchy on the 404 page
