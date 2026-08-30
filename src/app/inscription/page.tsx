import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { db } from "@/lib/db";
import { getCurrentUser, hashPassword, startSession } from "@/lib/auth";

export const metadata: Metadata = { title: "Créer un compte patient" };
export const dynamic = "force-dynamic";

/**
 * Patient registration.
 *
 * Until this existed, the landing page's "Je suis patient" plaque led to a sign
 * -in wall with no way past it: every account had to be seeded. The form asks
 * for the four things an appointment request actually needs — a name the
 * practitioner will see, an address to reach the account, and a password — and
 * nothing else. No date of birth, no sex, no social security number: DOCTORY
 * puts patients in touch with practitioners, it does not hold a medical record,
 * and collecting health-adjacent identifiers it has no use for would be the
 * wrong default on this kind of platform.
 */

/** Field values are echoed back on error so nothing has to be retyped. */
type Field = "firstName" | "lastName" | "email" | "phone";

async function register(formData: FormData) {
  "use server";

  const firstName = String(formData.get("firstName") ?? "").trim();
  const lastName = String(formData.get("lastName") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  const phone = String(formData.get("phone") ?? "").trim();
  const password = String(formData.get("password") ?? "");
  const next = String(formData.get("next") ?? "");

  const back = (error: string) => {
    const params = new URLSearchParams({ error, firstName, lastName, email });
    if (phone) params.set("phone", phone);
    if (next) params.set("next", next);
    redirect(`/inscription?${params.toString()}`);
  };

  if (!firstName || !lastName) back("Indiquez votre prénom et votre nom.");
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    back("Cette adresse e-mail ne semble pas valide.");
  }
  if (password.length < 8) {
    back("Le mot de passe doit contenir au moins 8 caractères.");
  }

  const existing = await db.user.findUnique({
    where: { email },
    select: { id: true },
  });
  if (existing) {
    back(
      "Un compte existe déjà avec cette adresse. Connectez-vous, ou utilisez une autre adresse.",
    );
  }

  const user = await db.user.create({
    data: {
      email,
      passwordHash: hashPassword(password),
      firstName,
      lastName,
      phone: phone || null,
      role: "PATIENT",
    },
    select: { id: true },
  });

  await startSession(user.id);
  // Only an internal path is followed, for the reason spelled out in /connexion.
  redirect(next.startsWith("/") && !next.startsWith("//") ? next : "/patient");
}

export default async function RegisterPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const user = await getCurrentUser();
  if (user) redirect("/patient");

  const value = (field: Field) =>
    typeof params[field] === "string" ? (params[field] as string) : "";
  const error = typeof params.error === "string" ? params.error : null;
  const next = typeof params.next === "string" ? params.next : "";

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto w-full max-w-md flex-1 px-5 py-12 sm:py-16">
        <h1 className="font-display text-3xl font-bold text-ink-900">
          Créer un compte patient
        </h1>
        <p className="mt-2 text-[15px] leading-relaxed text-ink-600">
          Pour demander des rendez-vous, les suivre et enregistrer vos favoris.
          Gratuit, et sans dossier médical&nbsp;: DOCTORY vous met en relation,
          il ne conserve pas vos données de santé.
        </p>

        <form action={register} className="mt-8 flex flex-col gap-5">
          <input type="hidden" name="next" value={next} />

          {error && (
            <p
              role="alert"
              className="border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] leading-snug text-ink-900"
            >
              {error}
            </p>
          )}

          <div className="grid gap-5 sm:grid-cols-2">
            <Field
              id="firstName"
              label="Prénom"
              autoComplete="given-name"
              defaultValue={value("firstName")}
              required
            />
            <Field
              id="lastName"
              label="Nom"
              autoComplete="family-name"
              defaultValue={value("lastName")}
              required
            />
          </div>

          <Field
            id="email"
            label="Adresse e-mail"
            type="email"
            autoComplete="email"
            defaultValue={value("email")}
            required
          />

          <Field
            id="phone"
            label="Téléphone (facultatif)"
            type="tel"
            autoComplete="tel"
            defaultValue={value("phone")}
            hint="Communiqué au praticien avec votre demande de rendez-vous, pour qu'il puisse vous rappeler."
          />

          <Field
            id="password"
            label="Mot de passe"
            type="password"
            autoComplete="new-password"
            required
            minLength={8}
            hint="Au moins 8 caractères."
          />

          <button
            type="submit"
            className="min-h-11 bg-rod-500 px-4 py-3.5 font-display text-sm font-bold tracking-[0.06em] text-rod-950 uppercase hover:bg-rod-400"
          >
            Créer mon compte
          </button>
        </form>

        <p className="mt-8 border-t border-enamel-300 pt-6 text-[15px] text-ink-600">
          Vous avez déjà un compte&nbsp;?{" "}
          <Link
            href={`/connexion${next ? `?next=${encodeURIComponent(next)}` : ""}`}
            className="inline-flex min-h-11 items-center font-bold text-ink-900 underline underline-offset-4"
          >
            Se connecter
          </Link>
        </p>
        <p className="mt-2 text-[15px] text-ink-600">
          Vous êtes praticien, pharmacien ou responsable d&apos;un
          établissement&nbsp;?{" "}
          <Link
            href="/inscription/professionnel"
            className="inline-flex min-h-11 items-center font-bold text-ink-900 underline underline-offset-4"
          >
            Inscrire votre structure
          </Link>
        </p>
      </div>
    </main>
  );
}

/** One labelled field. Hints sit under the input, where they are read after it. */
function Field({
  id,
  label,
  hint,
  ...input
}: {
  id: string;
  label: string;
  hint?: string;
} & React.InputHTMLAttributes<HTMLInputElement>) {
  return (
    <div>
      <label
        htmlFor={id}
        className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
      >
        {label}
      </label>
      <input
        id={id}
        name={id}
        aria-describedby={hint ? `${id}-hint` : undefined}
        className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-3 text-[15px] text-ink-900"
        {...input}
      />
      {hint && (
        <p id={`${id}-hint`} className="mt-1.5 text-sm leading-snug text-ink-500">
          {hint}
        </p>
      )}
    </div>
  );
}
