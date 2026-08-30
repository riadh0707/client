import Link from "next/link";

/**
 * The header on every page but the landing, which carries its own inside the
 * green field. Kept as a dark enamel band so the plaque identity survives on
 * pages whose body is light.
 */
export function SiteHeader({
  children,
}: {
  /** Optional slot for a compact search instrument on results pages. */
  children?: React.ReactNode;
}) {
  return (
    <header className="bg-cross-700 text-enamel-50">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-4 px-5 py-4 sm:px-8 lg:flex-row lg:items-center lg:gap-8">
        <div className="flex items-center justify-between gap-4">
          <Link href="/" className="flex shrink-0 items-center gap-2.5">
            <span aria-hidden className="cross-mark h-6 w-6 text-cross-500" />
            <span className="font-display text-xl font-bold tracking-[-0.02em]">
              DOCTORY
            </span>
          </Link>
          <Link
            href="/pro"
            className="shrink-0 font-display text-xs font-bold tracking-[0.1em] text-cross-100 uppercase underline-offset-4 hover:underline lg:hidden"
          >
            Espace pro
          </Link>
        </div>

        {children && <div className="min-w-0 flex-1">{children}</div>}

        <Link
          href="/pro"
          className="hidden shrink-0 font-display text-sm font-bold tracking-[0.1em] text-cross-100 uppercase underline-offset-4 hover:underline lg:block"
        >
          Espace professionnel
        </Link>
      </div>
    </header>
  );
}
