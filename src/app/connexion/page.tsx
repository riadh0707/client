import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { db } from "@/lib/db";
import { getCurrentUser, startSession, verifyPassword } from "@/lib/auth";

export const metadata: Metadata = { title: "Connexion" };
export const dynamic = "force-dynamic";

/** Where each role lands after signing in. */
function homeForRole(role: string) {
  if (role === "ADMIN") return "/admin";
  if (role === "PROFESSIONAL" || role === "SECRETARY") return "/pro";
  return "/patient";
}

async function signIn(formData: FormData) {
  "use server";

  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  const password = String(formData.get("password") ?? "");
  const next = String(formData.get("next") ?? "");

  const user = await db.user.findUnique({
    where: { email },
    select: { id: true, passwordHash: true, role: true, isActive: true },
  });

  // One message for every failure mode. Saying "unknown email" would let anyone
  // enumerate which addresses hold accounts on a health platform.
  const failure = `/connexion?error=1${next ? `&next=${encodeURIComponent(next)}` : ""}`;
  if (!user || !user.isActive) redirect(failure);
  if (!verifyPassword(password, user.passwordHash)) redirect(failure);

  await startSession(user.id);
  redirect(next || homeForRole(user.role));
}

export default async function SignInPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const user = await getCurrentUser();
  const next = typeof params.next === "string" ? params.next : "";

  if (user) redirect(next || homeForRole(user.role));

  const hasError = params.error === "1";

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-5 py-12 sm:py-20">
        <h1 className="font-display text-3xl font-bold text-ink-900">
          Connexion
        </h1>
        <p className="mt-2 text-[15px] text-ink-600">
          Accédez à votre espace patient, professionnel ou administrateur.
        </p>

        <form action={signIn} className="mt-8 flex flex-col gap-5">
          <input type="hidden" name="next" value={next} />

          {hasError && (
            <p
              role="alert"
              className="border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
            >
              Adresse e-mail ou mot de passe incorrect.
            </p>
          )}

          <div>
            <label
              htmlFor="email"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Adresse e-mail
            </label>
            <input
              id="email"
              name="email"
              type="email"
              required
              autoComplete="email"
              className="w-full border border-enamel-300 bg-white px-3 py-3 text-[15px] text-ink-900"
            />
          </div>

          <div>
            <label
              htmlFor="password"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Mot de passe
            </label>
            <input
              id="password"
              name="password"
              type="password"
              required
              autoComplete="current-password"
              className="w-full border border-enamel-300 bg-white px-3 py-3 text-[15px] text-ink-900"
            />
          </div>

          <button
            type="submit"
            className="bg-cross-500 px-4 py-3.5 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400"
          >
            Se connecter
          </button>
        </form>

        {/* Demo credentials, stated plainly. Hiding them would make the build
            unreviewable; the accounts are fictional and the database is a demo
            fixture. */}
        <section className="mt-10 border-t border-enamel-300 pt-6">
          <h2 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
            Comptes de démonstration
          </h2>
          <ul className="ruled mt-2 text-sm text-ink-600">
            <li className="py-2">
              <span className="font-display font-bold text-ink-900">
                Administratrice
              </span>
              <br />
              admin@doctory.dz · doctory-demo
            </li>
            <li className="py-2">
              <span className="font-display font-bold text-ink-900">Patient</span>
              <br />
              patient1@exemple.dz · doctory-demo
            </li>
          </ul>
          <p className="mt-3 text-sm text-ink-400">
            Les comptes professionnels suivent le format
            pro-&lt;identifiant-du-partenaire&gt;@doctory.dz, visible depuis
            l&apos;administration.
          </p>
        </section>

        <p className="mt-8 text-sm text-ink-500">
          <Link href="/" className="inline-flex min-h-11 items-center underline underline-offset-4">
            Retour à l&apos;accueil
          </Link>
        </p>
      </div>
    </main>
  );
}
