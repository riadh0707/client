import Link from "next/link";

/**
 * The header on every page but the landing, which carries its own inside the
 * green field. Kept as a dark enamel band so the plaque identity survives on
 * pages whose body is light.
 *
 * Both destinations stay reachable at every width. An earlier version gated the
 * navigation behind `lg:flex`/`xl:flex` to stop it crowding the search
 * instrument, which made "Autour de moi" — the entry point for the urgent
 * lookup PRODUCT.md calls a first-class job — unreachable on a phone from every
 * page except the landing. Fixing a contrast problem had broken the fast path.
 *
 * Links carry `py-2.5 min-h-11` rather than sitting at their 16px line box:
 * a 44px target is the floor for the one-handed outdoor use the brief names.
 */

const NAV_LINK =
  "flex min-h-11 shrink-0 items-center px-1 py-2.5 font-display text-xs font-bold tracking-[0.1em] text-cross-100 uppercase underline-offset-4 hover:underline sm:text-sm";

export function SiteHeader({
  children,
}: {
  /** Optional slot for a compact search instrument on results pages. */
  children?: React.ReactNode;
}) {
  return (
    <header className="bg-cross-700 text-enamel-50">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-3 px-5 py-3 sm:px-8 lg:flex-row lg:items-center lg:gap-8">
        <Link
          href="/"
          className="flex min-h-11 shrink-0 items-center gap-2.5 py-1"
        >
          <span aria-hidden className="cross-mark h-6 w-6 text-cross-500" />
          <span className="font-display text-xl font-bold tracking-[-0.02em]">
            DOCTORY
          </span>
        </Link>

        {/* The navigation takes its own row on a phone. Sharing the wordmark's
            row pushed the header past 390px and made the page scroll sideways. */}
        <nav
          aria-label="Navigation principale"
          className="flex flex-wrap gap-x-5 gap-y-1 lg:hidden"
        >
          <Link href="/autour-de-moi" className={NAV_LINK}>
            Autour de moi
          </Link>
          <Link href="/pro" className={NAV_LINK}>
            Espace pro
          </Link>
        </nav>

        {/* The instrument gets a floor width: a second nav link once squeezed it
            until the query and wilaya fields both truncated their placeholders. */}
        {children && (
          <div className="min-w-0 flex-1 lg:min-w-[28rem]">{children}</div>
        )}

        <nav
          aria-label="Navigation principale"
          className="hidden shrink-0 gap-6 lg:flex"
        >
          <Link href="/autour-de-moi" className={NAV_LINK}>
            Autour de moi
          </Link>
          <Link href="/pro" className={NAV_LINK}>
            Espace professionnel
          </Link>
        </nav>
      </div>
    </header>
  );
}
