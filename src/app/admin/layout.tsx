import Link from "next/link";
import { redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { getCurrentUser, endSession } from "@/lib/auth";

export const dynamic = "force-dynamic";

async function signOut() {
  "use server";
  await endSession();
  redirect("/");
}

const LINKS = [
  { href: "/admin", label: "Tableau de bord" },
  { href: "/admin/partenaires", label: "Partenaires" },
  { href: "/admin/utilisateurs", label: "Utilisateurs" },
  { href: "/admin/abonnements", label: "Abonnements" },
  { href: "/admin/rendez-vous", label: "Rendez-vous" },
  { href: "/admin/statistiques", label: "Statistiques" },
  { href: "/admin/activite", label: "Activité" },
];

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fadmin");
  // Administration is the one space with a hard role gate: it can suspend
  // accounts and change subscriptions, so membership in it is never implied.
  if (user.role !== "ADMIN") redirect("/");

  return (
    <div className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="border-b border-enamel-300 bg-enamel-50">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-3 sm:px-8">
          <nav className="flex flex-wrap gap-x-5 gap-y-2">
            {LINKS.map((link) => (
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
            <span className="border border-cross-600/40 bg-cross-100 px-1.5 py-0.5 font-display text-[10px] font-bold tracking-[0.08em] text-cross-800 uppercase">
              Administration
            </span>
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
