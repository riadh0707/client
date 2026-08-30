/**
 * Tests for the two rules the product cannot be wrong about.
 *
 * The clock: the platform shipped an hour behind Algeria, because opening
 * state, next-opening and the bookable grid all read the host's timezone. On a
 * UTC host a pharmacy open from 08:00 was reported closed at 08:51 Algiers, and
 * slots that had already passed were still offered.
 *
 * Search integrity: a paid listing must obey the visitor's own filter, and
 * pagination must not promise a page the results cannot fill. Both are pinned
 * to a known hour, because every sponsored partner is open during business
 * hours and the rule cannot be proven against the live site at an arbitrary
 * moment.
 *
 * Run: npm test
 */

import assert from "node:assert/strict";
import {
  APP_TIME_ZONE,
  addZonedDays,
  startOfZonedDay,
  zonedClock,
  zonedParts,
  zonedTimeToInstant,
} from "../src/lib/time";
import { resolveOpenState, type Interval } from "../src/lib/hours";

let failures = 0;
function check(name: string, run: () => void) {
  try {
    run();
    console.log(`  ok   ${name}`);
  } catch (error) {
    failures += 1;
    console.log(`  FAIL ${name}`);
    console.log(`       ${error instanceof Error ? error.message : String(error)}`);
  }
}

console.log(`Clock tests (${APP_TIME_ZONE}); host TZ is ${Intl.DateTimeFormat().resolvedOptions().timeZone}`);

check("reads an instant on the Algerian wall clock, not the host's", () => {
  // 07:51 UTC is 08:51 in Algiers.
  const parts = zonedParts(new Date("2026-09-07T07:51:00Z"));
  assert.equal(parts.hours, 8);
  assert.equal(parts.minutes, 51);
  assert.equal(parts.minutesOfDay, 8 * 60 + 51);
});

check("turns an Algerian wall time into the instant it denotes", () => {
  const instant = zonedTimeToInstant(2026, 9, 7, 8, 0);
  assert.equal(instant.toISOString(), "2026-09-07T07:00:00.000Z");
});

check("round-trips wall time through the instant and back", () => {
  for (const hour of [0, 6, 12, 18, 23]) {
    const instant = zonedTimeToInstant(2026, 9, 7, hour, 30);
    const back = zonedParts(instant);
    assert.equal(back.hours, hour, `hour ${hour}`);
    assert.equal(back.minutes, 30, `hour ${hour}`);
    assert.equal(back.day, 7, `hour ${hour}`);
  }
});

check("startOfZonedDay lands on Algerian midnight, not the host's", () => {
  // 00:30 Algiers on the 8th is 23:30 UTC on the 7th: a host-local midnight
  // would put this on the wrong day.
  const midnight = startOfZonedDay(new Date("2026-09-07T23:30:00Z"));
  const parts = zonedParts(midnight);
  assert.equal(parts.day, 8);
  assert.equal(parts.hours, 0);
  assert.equal(parts.minutes, 0);
});

check("addZonedDays advances the Algerian calendar day", () => {
  const start = new Date("2026-09-07T23:30:00Z"); // 8 Sept, 00:30 Algiers
  const next = addZonedDays(start, 1);
  assert.equal(zonedParts(next).day, 9);
  assert.equal(zonedParts(next).hours, 0);
});

check("a partner open 08:00-12:00 reads OPEN at 09:00 Algiers", () => {
  const at = new Date("2026-09-07T08:00:00Z"); // 09:00 Algiers
  const weekday = zonedParts(at).weekday;
  const intervals: Interval[] = [
    { weekday, opensAt: "08:00", closesAt: "12:00" },
    { weekday, opensAt: "14:00", closesAt: "17:00" },
  ];
  const state = resolveOpenState(intervals, at);
  assert.equal(state.status, "open");
  if (state.status === "open") assert.equal(state.closesAt, "12:00");
});

check("the exact case that shipped broken: 08:51 Algiers reads OPEN", () => {
  // This is the observed defect. On a UTC host the old code read 07:51 and
  // reported "Fermé · ouvre à 08:00" while the pharmacy was open.
  const at = new Date("2026-09-07T07:51:00Z");
  const weekday = zonedParts(at).weekday;
  const state = resolveOpenState(
    [{ weekday, opensAt: "08:00", closesAt: "12:00" }],
    at,
  );
  assert.equal(state.status, "open");
});

check("the midday break reads CLOSED with the correct next opening", () => {
  const at = new Date("2026-09-07T12:00:00Z"); // 13:00 Algiers
  const weekday = zonedParts(at).weekday;
  const state = resolveOpenState(
    [
      { weekday, opensAt: "08:00", closesAt: "12:00" },
      { weekday, opensAt: "14:00", closesAt: "17:00" },
    ],
    at,
  );
  assert.equal(state.status, "closed");
  if (state.status === "closed") assert.equal(state.opensAt, "14:00");
});

check("an empty schedule stays unknown, never closed", () => {
  assert.equal(resolveOpenState([], new Date()).status, "unknown");
});

check("zonedClock renders the Algerian hour", () => {
  assert.equal(zonedClock(new Date("2026-09-07T07:00:00Z")), "08:00");
});

// --- Search integrity, pinned to a known hour -----------------------------
// Every sponsored partner happens to be open during business hours, so this
// rule cannot be proven by querying the live site at an arbitrary moment. It is
// pinned here instead.

const { searchPartners } = await import("../src/lib/search");
const { zonedTimeToInstant: at } = await import("../src/lib/time");

// 13:00 Algiers on a Monday: inside the midday break that Algerian practices
// keep, so partners open 08:00-12:00 and 14:00-17:00 read closed.
const middayBreak = at(2026, 9, 7, 13, 0);

await (async () => {
  const open = await searchPartners({ categorie: "doctor", ouvert: true }, middayBreak);
  const all = await searchPartners({ categorie: "doctor" }, middayBreak);

  check("the open-now filter actually removes closed partners", () => {
    assert.ok(
      all.total > open.total,
      `expected fewer open than total, got open=${open.total} all=${all.total}`,
    );
  });

  check("no sponsored result survives the open-now filter while closed", () => {
    const closedSponsored = open.sponsored.filter(
      (row) => row.openState.status !== "open",
    );
    assert.deepEqual(
      closedSponsored.map((row) => row.displayName),
      [],
      "a paid listing bypassed the visitor's own filter",
    );
  });

  check("sponsored rows appear on page one only", () => {
    assert.ok(all.sponsored.length > 0, "expected sponsored rows on page 1");
    return (async () => {})();
  });

  const page2 = await searchPartners(
    { categorie: "doctor", page: 2 },
    middayBreak,
  );
  check("page two carries no repeated sponsored block", () => {
    assert.equal(page2.sponsored.length, 0);
  });

  check("pagination never promises a page the results cannot fill", () => {
    assert.ok(
      all.pageCount === Math.max(1, Math.ceil(all.total / 12)),
      `pageCount ${all.pageCount} disagrees with total ${all.total}`,
    );
    assert.ok(page2.items.length > 0 || all.pageCount < 2, "page 2 rendered empty");
  });
})();

console.log(failures === 0 ? "\nAll tests passed." : `\n${failures} test(s) failed.`);
process.exit(failures === 0 ? 0 : 1);
