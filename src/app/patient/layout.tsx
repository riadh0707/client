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

export default async function PatientLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getCurrentUser();
  // The guard lives in the layout so every page under /patient inherits it and
  // no future route can forget it.
  //
  // Authentication only, deliberately: a practitioner is also someone who books
  // appointments, so any signed-in account may hold a patient space. The reverse
  // is not true, and /pro turns PATIENT accounts away.
  if (!user) redirect("/connexion?next=%2Fpatient");

  const unread = await db.notification.count({
    where: { userId: user.id, readAt: null },
  });

  const links = [
    { href: "/patient", label: "Tableau de bord" },
    { href: "/patient/rendez-vous", label: "Mes rendez-vous" },
    { href: "/patient/favoris", label: "Favoris" },
    { href: "/patient/notifications", label: `Notifications${unread > 0 ? ` (${unread})` : ""}` },
  ];

  return (
    <div className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="border-b border-enamel-300 bg-enamel-50">
        <div className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-3 sm:px-8">
          {/* Index tabs — the Carnet donation used as the account's own
              navigation rather than as decoration. */}
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

      {children}
    </div>
  );
}
