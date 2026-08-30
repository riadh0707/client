import Link from "next/link";
import { redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { getCurrentUser, endSession } from "@/lib/auth";
import { db } from "@/lib/db";

export const dynamic = "force-dynamic";

async function signOut() {
  "use server";
  await endSession();
  redirect("/");
}

export default async function ProLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro");
  if (user.role === "PATIENT") redirect("/patient");

  const unread = await db.notification.count({
    where: { userId: user.id, readAt: null },
  });

  const isSecretary = user.memberships[0]?.role === "SECRETARY";

  // A secretary runs the agenda and nothing else: profile and subscription are
  // the owner's. Hiding the tabs matches the permission the server enforces,
  // rather than showing links that would bounce.
  const links = [
    { href: "/pro", label: "Tableau de bord" },
    { href: "/pro/agenda", label: `Agenda${unread > 0 ? ` (${unread})` : ""}` },
    ...(isSecretary
      ? []
      : [
          { href: "/pro/profil", label: "Profil" },
          { href: "/pro/abonnement", label: "Abonnement" },
        ]),
  ];

  return (
    <div className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="border-b border-enamel-300 bg-enamel-50">
        <div className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-3 sm:px-8">
          <nav className="flex flex-wrap gap-x-5 gap-y-2">
            {links.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="font-display text-xs font-bold tracking-[0.08em] text-ink-600 uppercase underline-offset-4 hover:text-cross-700 hover:underline"
              >
                {link.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-4">
            {isSecretary && (
              <span className="border border-carbon-blue/40 bg-carbon-blue-soft px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] text-carbon-blue uppercase">
                Secrétaire
              </span>
            )}
            <form action={signOut}>
              <button
                type="submit"
                className="font-display text-xs font-bold tracking-[0.08em] text-ink-400 uppercase underline-offset-4 hover:text-carbon-rose hover:underline"
              >
                Déconnexion
              </button>
            </form>
          </div>
        </div>
      </div>

      {children}
    </div>
  );
}
