import type { Metadata } from "next";
import { db } from "@/lib/db";
import { requireAdmin } from "@/lib/admin";
import { dateTimeShort } from "@/lib/format";

export const metadata: Metadata = { title: "Journal d'activité" };
export const dynamic = "force-dynamic";

export default async function ActivityLogPage() {
  await requireAdmin();

  const entries = await db.activityLog.findMany({
    orderBy: { createdAt: "desc" },
    take: 100,
    select: {
      id: true,
      action: true,
      summary: true,
      createdAt: true,
      actor: { select: { firstName: true, lastName: true } },
    },
  });

  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Journal d&apos;activité
      </h1>
      <p className="mt-1 max-w-[52ch] text-[15px] leading-relaxed text-ink-600">
        Cent dernières actions administratives. Le libellé est figé au moment de
        l&apos;action&nbsp;: renommer un partenaire plus tard ne réécrit pas
        l&apos;historique.
      </p>

      {entries.length === 0 ? (
        <p className="mt-6 bg-enamel-50 px-5 py-12 text-center text-[15px] text-ink-600">
          Aucune action enregistrée.
        </p>
      ) : (
        <ul className="ruled mt-6 border-y border-enamel-300 bg-enamel-50">
          {entries.map((entry) => (
            <li key={entry.id} className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-4 py-3">
              <div className="min-w-0">
                <span className="block text-[15px] text-ink-900">{entry.summary}</span>
                <span className="block text-xs text-ink-400">
                  {entry.action}
                  {entry.actor ? ` · ${entry.actor.firstName} ${entry.actor.lastName}` : " · système"}
                </span>
              </div>
              <span className="shrink-0 text-xs tabular-nums text-ink-400">
                {dateTimeShort.format(entry.createdAt)}
              </span>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
