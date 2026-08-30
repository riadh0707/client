import { SiteHeader } from "@/components/site-header";

/**
 * The results skeleton.
 *
 * Shaped like the page it stands in for — sidebar, count line, ruled list of
 * cards — so the layout does not jump when the real results land. Nothing here
 * pretends to be data: the bars are blank, and the count line is a bar too
 * rather than a number that would then change.
 *
 * It carries no live region and no "Chargement…" text of its own; the
 * navigation is announced by the router, and a skeleton that also shouts is
 * noise for a screen reader.
 */
export default function SearchLoading() {
  return (
    <main className="flex flex-1 flex-col" aria-busy>
      <SiteHeader />

      <div className="mx-auto grid w-full max-w-6xl flex-1 gap-px bg-ink-900/10 lg:grid-cols-[17rem_1fr]">
        <aside className="hidden bg-enamel-50 p-5 lg:block">
          {[0, 1, 2].map((group) => (
            <div key={group} className="mb-8">
              <Bar className="h-2.5 w-24" />
              <div className="mt-4 flex flex-col gap-2.5">
                {[0, 1, 2, 3].map((row) => (
                  <Bar key={row} className="h-3.5 w-full" />
                ))}
              </div>
            </div>
          ))}
        </aside>

        <section className="bg-enamel-100">
          <div className="px-5 py-5 sm:px-6">
            <Bar className="h-7 w-40" />
          </div>
          <div className="flex flex-col gap-px bg-ink-900/10">
            {[0, 1, 2, 3, 4].map((row) => (
              <div key={row} className="bg-enamel-50 px-5 py-5 sm:px-6">
                <Bar className="h-4 w-48" />
                <Bar className="mt-2.5 h-3 w-32" />
                <Bar className="mt-4 h-3 w-full max-w-md" />
              </div>
            ))}
          </div>
        </section>
      </div>
    </main>
  );
}

/** One blank bar. Flat enamel, no shimmer: the plaque does not animate. */
function Bar({ className }: { className: string }) {
  return <span aria-hidden className={`block bg-enamel-300 ${className}`} />;
}
