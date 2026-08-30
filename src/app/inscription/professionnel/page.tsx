import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { db } from "@/lib/db";
import { getCurrentUser, hashPassword, startSession } from "@/lib/auth";
import { uniquePartnerSlug } from "@/lib/slug";

export const metadata: Metadata = { title: "Inscrire votre structure" };
export const dynamic = "force-dynamic";

/**
 * Professional registration.
 *
 * The revenue-bearing side of the product had no door at all: "Je suis
 * professionnel" led to a sign-in wall, so the only way onto DOCTORY was to be
 * seeded into it.
 *
 * It lives under /inscription rather than /pro on purpose: everything below
 * /pro inherits a layout whose guard sends a signed-out visitor to the sign-in
 * screen, which is exactly the wall this page exists to open. Weakening that
 * guard for one route would have been the wrong trade — it is what stops a
 * future page under /pro from forgetting to authenticate.
 *
 * Three steps, each one a URL. That is not a workaround for a dependent select:
 * it is the product's own order. PRODUCT.md puts geography before everything,
 * and a practice is a place before it is a person — so the type of partner and
 * the commune are settled first, in links the server renders, and only the last
 * step posts anything private. It also means every step works with JavaScript
 * off, and that a half-finished registration can be resumed from its URL.
 *
 * The form is shaped by the category's capability flags, never by its slug: an
 * establishment is asked for a trading name, an individual for a person's name,
 * and only a category that books appointments is asked how long a consultation
 * lasts. Adding "kinésithérapeute" is a seed row — this page already handles it.
 */

const STEPS = ["Type", "Lieu", "Coordonnées"] as const;

function stepFor(type: string | null, commune: string | null) {
  if (!type) return 0;
  if (!commune) return 1;
  return 2;
}

async function register(formData: FormData) {
  "use server";

  const read = (key: string) => String(formData.get(key) ?? "").trim();

  const categorySlug = read("type");
  const wilayaCode = Number(read("wilaya"));
  const communeCode = Number(read("commune"));
  const firstName = read("firstName");
  const lastName = read("lastName");
  const businessName = read("businessName");
  const specialtyId = read("specialtyId");
  const address = read("address");
  const phone = read("phone");
  const email = read("email").toLowerCase();
  const password = String(formData.get("password") ?? "");
  const slotDurationMinutes = Number(read("slotDurationMinutes")) || 30;

  const base = new URLSearchParams({
    type: categorySlug,
    wilaya: String(wilayaCode),
    commune: String(communeCode),
  });
  const back = (error: string) => {
    const params = new URLSearchParams(base);
    params.set("error", error);
    for (const [key, value] of [
      ["firstName", firstName],
      ["lastName", lastName],
      ["businessName", businessName],
      ["specialtyId", specialtyId],
      ["address", address],
      ["phone", phone],
      ["email", email],
    ] as const) {
      if (value) params.set(key, value);
    }
    redirect(`/inscription/professionnel?${params.toString()}`);
  };

  const category = await db.partnerCategory.findUnique({
    where: { slug: categorySlug },
  });
  const commune = await db.commune.findUnique({
    where: { code: communeCode },
    include: { wilaya: true },
  });
  if (!category || !commune || commune.wilayaCode !== wilayaCode) {
    redirect("/inscription/professionnel");
  }

  const displayName = category.isIndividual
    ? `${firstName} ${lastName}`.trim()
    : businessName;

  if (!displayName) {
    back(
      category.isIndividual
        ? "Indiquez le prénom et le nom du praticien."
        : "Indiquez le nom de l'établissement.",
    );
  }
  if (!address) back("Indiquez l'adresse de la structure.");
  if (!phone) back("Indiquez un numéro de téléphone joignable.");
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    back("Cette adresse e-mail ne semble pas valide.");
  }
  if (password.length < 8) {
    back("Le mot de passe doit contenir au moins 8 caractères.");
  }

  const taken = await db.user.findUnique({
    where: { email },
    select: { id: true },
  });
  if (taken) {
    back(
      "Un compte existe déjà avec cette adresse. Connectez-vous pour rattacher une structure.",
    );
  }

  // The specialty must belong to the chosen category, or a dentist could be
  // filed under Cardiologie by editing one hidden field.
  let specialty: string | null = null;
  if (specialtyId) {
    const found = await db.specialty.findFirst({
      where: { id: specialtyId, categoryId: category.id },
      select: { id: true },
    });
    specialty = found?.id ?? null;
  }

  // The practice sits at the commune's chef-lieu until its owner places it more
  // precisely. Inventing coordinates from a street address with no geocoder
  // would put a pin on a building nobody chose.
  const slug = await uniquePartnerSlug(displayName);

  const created = await db.$transaction(async (tx) => {
    const user = await tx.user.create({
      data: {
        email,
        passwordHash: hashPassword(password),
        firstName: firstName || displayName,
        lastName: lastName || "",
        phone,
        role: "PROFESSIONAL",
      },
      select: { id: true },
    });

    const partner = await tx.partner.create({
      data: {
        slug,
        firstName: category.isIndividual ? firstName : null,
        lastName: category.isIndividual ? lastName : null,
        businessName: category.isIndividual ? null : businessName,
        displayName,
        categoryId: category.id,
        specialtyId: specialty,
        wilayaCode: commune.wilayaCode,
        communeCode: commune.code,
        address,
        lat: commune.wilaya.lat,
        lng: commune.wilaya.lng,
        phone,
        email,
        // Registration does not publish. An administrator reviews the file
        // first: the whole point of the back-office is that anyone cannot list
        // themselves as a practitioner on a health platform.
        status: "PENDING",
        verificationStatus: "UNVERIFIED",
        slotDurationMinutes: category.supportsAppointments
          ? Math.min(Math.max(slotDurationMinutes, 10), 120)
          : 30,
      },
      select: { id: true, displayName: true },
    });

    await tx.partnerMember.create({
      data: { userId: user.id, partnerId: partner.id, role: "OWNER" },
    });

    return { userId: user.id, partner };
  });

  // The moderation queue is what the administration actually watches, and this
  // row is now in it; the notification tells the administrators why.
  const admins = await db.user.findMany({
    where: { role: "ADMIN", isActive: true },
    select: { id: true },
  });
  if (admins.length > 0) {
    await db.notification.createMany({
      data: admins.map((admin) => ({
        userId: admin.id,
        kind: "PARTNER_REGISTERED" as const,
        title: "Nouvelle inscription professionnelle",
        body: `${created.partner.displayName} (${category.name}, ${commune.name}) attend une validation.`,
        href: "/admin/partenaires?statut=PENDING",
      })),
    });
  }

  await startSession(created.userId);
  redirect("/pro?bienvenue=1");
}

export default async function ProRegistrationPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const one = (key: string) =>
    typeof params[key] === "string" ? (params[key] as string) : null;

  const user = await getCurrentUser();
  if (user) redirect(user.role === "PATIENT" ? "/patient" : "/pro");

  const typeSlug = one("type");
  const wilayaCode = Number(one("wilaya"));
  const communeCode = Number(one("commune"));
  const error = one("error");

  const categories = await db.partnerCategory.findMany({
    orderBy: { sortOrder: "asc" },
  });
  const category = typeSlug
    ? (categories.find((c) => c.slug === typeSlug) ?? null)
    : null;

  const step = stepFor(category ? category.slug : null, one("commune"));

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto w-full max-w-2xl flex-1 px-5 py-10 sm:px-8 sm:py-14">
        <h1 className="font-display text-3xl font-bold text-ink-900 sm:text-4xl">
          Inscrire votre structure
        </h1>
        <p className="mt-2 max-w-[56ch] text-[15px] leading-relaxed text-ink-600">
          Créez votre fiche sur DOCTORY. Elle est relue par notre équipe avant
          d&apos;apparaître dans les recherches&nbsp;: sur une plateforme de
          santé, personne ne se publie tout seul.
        </p>

        <ol className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
          {STEPS.map((label, index) => (
            <li
              key={label}
              aria-current={index === step ? "step" : undefined}
              className={`flex items-center gap-2 font-display text-[11px] font-bold tracking-[0.12em] uppercase ${
                index === step
                  ? "text-cross-700"
                  : index < step
                    ? "text-ink-600"
                    : "text-ink-300"
              }`}
            >
              <span
                aria-hidden
                className={`flex h-6 w-6 items-center justify-center border tabular-nums ${
                  index === step
                    ? "border-cross-700 bg-cross-700 text-enamel-50"
                    : index < step
                      ? "border-ink-300 text-ink-600"
                      : "border-enamel-300 text-ink-300"
                }`}
              >
                {index + 1}
              </span>
              {label}
            </li>
          ))}
        </ol>

        {error && (
          <p
            role="alert"
            className="mt-6 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] leading-snug text-ink-900"
          >
            {error}
          </p>
        )}

        {step === 0 && <ChooseType categories={categories} />}
        {step === 1 && category && (
          <ChoosePlace category={category} wilayaCode={wilayaCode} />
        )}
        {step === 2 && category && (
          <Details
            category={category}
            wilayaCode={wilayaCode}
            communeCode={communeCode}
            params={params}
          />
        )}

        <p className="mt-10 border-t border-enamel-300 pt-6 text-[15px] text-ink-600">
          Vous avez déjà une fiche sur DOCTORY&nbsp;?{" "}
          <Link
            href="/connexion?next=%2Fpro"
            className="inline-flex min-h-11 items-center font-bold text-ink-900 underline underline-offset-4"
          >
            Se connecter
          </Link>
        </p>
      </div>
    </main>
  );
}

/** Step 1. Every category the database holds, described by its own flags. */
function ChooseType({
  categories,
}: {
  categories: {
    id: string;
    slug: string;
    name: string;
    isIndividual: boolean;
    supportsAppointments: boolean;
  }[];
}) {
  return (
    <section className="mt-8">
      <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
        Quel type de structure inscrivez-vous&nbsp;?
      </h2>
      <ul className="ruled mt-3 border-y border-enamel-300">
        {categories.map((category) => (
          <li key={category.id}>
            <Link
              href={`/inscription/professionnel?type=${category.slug}`}
              className="group flex min-h-11 items-center gap-4 py-4 sm:gap-6"
            >
              <span
                aria-hidden
                className="cross-mark h-4 w-4 shrink-0 text-cross-500 sm:h-5 sm:w-5"
              />
              <span className="min-w-0">
                <span className="block font-display text-lg font-bold text-ink-900 group-hover:text-cross-700">
                  {category.name}
                </span>
                <span className="block text-sm text-ink-500">
                  {category.isIndividual
                    ? "Praticien inscrit à son nom"
                    : "Établissement inscrit sous sa raison sociale"}
                  {category.supportsAppointments
                    ? " · agenda et rendez-vous"
                    : " · sans rendez-vous"}
                </span>
              </span>
              <span
                aria-hidden
                className="ml-auto shrink-0 text-ink-300 group-hover:text-cross-700"
              >
                &rarr;
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  );
}

/** Step 2. Wilaya, then commune — the same order the search uses. */
async function ChoosePlace({
  category,
  wilayaCode,
}: {
  category: { slug: string; name: string };
  wilayaCode: number;
}) {
  const wilayas = await db.wilaya.findMany({
    select: { code: true, name: true },
    orderBy: { code: "asc" },
  });
  const wilaya = Number.isFinite(wilayaCode)
    ? wilayas.find((w) => w.code === wilayaCode)
    : undefined;
  const communes = wilaya
    ? await db.commune.findMany({
        where: { wilayaCode: wilaya.code },
        select: { code: true, name: true, daira: true },
        orderBy: { name: "asc" },
      })
    : [];

  return (
    <section className="mt-8">
      <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
        Où exercez-vous&nbsp;?
      </h2>
      <p className="mt-2 text-[15px] leading-relaxed text-ink-600">
        {category.name} · la commune détermine où les patients vous trouvent.
      </p>

      {/* A GET form: choosing the wilaya reloads this same step with its
          communes, and nothing private has been typed yet. */}
      <form method="get" action="/inscription/professionnel" className="mt-5">
        <input type="hidden" name="type" value={category.slug} />
        <label
          htmlFor="wilaya"
          className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
        >
          Wilaya
        </label>
        <div className="flex flex-col gap-3 sm:flex-row">
          <select
            id="wilaya"
            name="wilaya"
            defaultValue={wilaya ? String(wilaya.code) : ""}
            className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900 sm:flex-1"
          >
            <option value="">Choisir une wilaya</option>
            {wilayas.map((w) => (
              <option key={w.code} value={w.code}>
                {String(w.code).padStart(2, "0")} · {w.name}
              </option>
            ))}
          </select>
          <button
            type="submit"
            className="min-h-11 shrink-0 border border-cross-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:bg-cross-100"
          >
            {wilaya ? "Changer" : "Continuer"}
          </button>
        </div>
      </form>

      {wilaya && (
        <div className="mt-8">
          <h3 className="font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase">
            Commune · {wilaya.name}
          </h3>
          <ul className="ruled mt-2 max-h-96 overflow-y-auto border-y border-enamel-300">
            {communes.map((commune) => (
              <li key={commune.code}>
                <Link
                  href={`/inscription/professionnel?type=${category.slug}&wilaya=${wilaya.code}&commune=${commune.code}`}
                  className="flex min-h-11 items-center gap-3 py-2.5"
                >
                  <span className="font-display font-bold text-ink-900">
                    {commune.name}
                  </span>
                  <span className="ml-auto shrink-0 text-sm text-ink-400">
                    {commune.daira}
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      )}
    </section>
  );
}

/** Step 3. The only step that posts anything private. */
async function Details({
  category,
  wilayaCode,
  communeCode,
  params,
}: {
  category: {
    id: string;
    slug: string;
    name: string;
    isIndividual: boolean;
    supportsAppointments: boolean;
    supportsOpeningHours: boolean;
  };
  wilayaCode: number;
  communeCode: number;
  params: Record<string, string | string[] | undefined>;
}) {
  const commune = await db.commune.findUnique({
    where: { code: communeCode },
    include: { wilaya: { select: { code: true, name: true } } },
  });
  if (!commune || commune.wilayaCode !== wilayaCode) {
    redirect(`/inscription/professionnel?type=${category.slug}`);
  }

  const specialties = await db.specialty.findMany({
    where: { categoryId: category.id },
    orderBy: { name: "asc" },
    select: { id: true, name: true },
  });

  const value = (key: string) =>
    typeof params[key] === "string" ? (params[key] as string) : "";

  return (
    <section className="mt-8">
      <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-l-4 border-cross-500 bg-enamel-50 px-4 py-3">
        <p className="text-[15px] text-ink-900">
          <strong className="font-display">{category.name}</strong> à{" "}
          <strong className="font-display">{commune.name}</strong>,{" "}
          {commune.wilaya.name}
        </p>
        <Link
          href={`/inscription/professionnel?type=${category.slug}&wilaya=${commune.wilayaCode}`}
          className="inline-flex min-h-11 items-center font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase underline underline-offset-4"
        >
          Changer
        </Link>
      </div>

      <form action={register} className="mt-6 flex flex-col gap-5">
        <input type="hidden" name="type" value={category.slug} />
        <input type="hidden" name="wilaya" value={commune.wilayaCode} />
        <input type="hidden" name="commune" value={commune.code} />

        {category.isIndividual ? (
          <div className="grid gap-5 sm:grid-cols-2">
            <Field
              id="firstName"
              label="Prénom du praticien"
              autoComplete="given-name"
              defaultValue={value("firstName")}
              required
            />
            <Field
              id="lastName"
              label="Nom du praticien"
              autoComplete="family-name"
              defaultValue={value("lastName")}
              required
            />
          </div>
        ) : (
          <Field
            id="businessName"
            label={`Nom de l'établissement`}
            autoComplete="organization"
            defaultValue={value("businessName")}
            required
            hint="Le nom sous lequel les patients vous connaissent, tel qu'il figure sur votre enseigne."
          />
        )}

        {specialties.length > 0 && (
          <div>
            <label
              htmlFor="specialtyId"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Spécialité
            </label>
            <select
              id="specialtyId"
              name="specialtyId"
              defaultValue={value("specialtyId")}
              className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
            >
              <option value="">Sans spécialité déclarée</option>
              {specialties.map((specialty) => (
                <option key={specialty.id} value={specialty.id}>
                  {specialty.name}
                </option>
              ))}
            </select>
          </div>
        )}

        <Field
          id="address"
          label="Adresse"
          defaultValue={value("address")}
          required
          hint={`Rue et numéro, à ${commune.name}. Vous pourrez placer votre position exacte depuis votre espace.`}
        />

        <Field
          id="phone"
          label="Téléphone du cabinet"
          type="tel"
          defaultValue={value("phone")}
          required
          hint="Affiché publiquement sur votre fiche."
        />

        {category.supportsAppointments && (
          <div>
            <label
              htmlFor="slotDurationMinutes"
              className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
            >
              Durée d&apos;une consultation
            </label>
            <select
              id="slotDurationMinutes"
              name="slotDurationMinutes"
              defaultValue="30"
              className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
            >
              {[15, 20, 30, 45, 60].map((minutes) => (
                <option key={minutes} value={minutes}>
                  {minutes} minutes
                </option>
              ))}
            </select>
            <p className="mt-1.5 text-sm leading-snug text-ink-500">
              Détermine le pas de vos créneaux. Modifiable ensuite.
            </p>
          </div>
        )}

        <div className="border-t border-enamel-300 pt-5">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Votre compte
          </h2>
          <p className="mt-1.5 text-sm leading-snug text-ink-500">
            Ces identifiants vous serviront à gérer la fiche et l&apos;agenda.
          </p>
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
          id="password"
          label="Mot de passe"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
          hint="Au moins 8 caractères."
        />

        {/* Said before the button, not after: what happens next, and what does
            not happen. Nothing here charges anyone. */}
        <div className="border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3">
          <p className="text-[15px] leading-relaxed text-ink-900">
            Après envoi, votre fiche part en attente de validation. Elle
            n&apos;apparaît pas encore dans les recherches. Vous accédez
            immédiatement à votre espace pour renseigner
            {category.supportsOpeningHours
              ? " vos horaires et vos prestations"
              : " vos prestations"}
            .
          </p>
          <p className="mt-2 text-sm leading-relaxed text-ink-600">
            L&apos;inscription est gratuite et aucun paiement n&apos;est demandé
            ici. Les abonnements se souscrivent depuis votre espace, une fois la
            fiche validée.
          </p>
        </div>

        <button
          type="submit"
          className="min-h-11 bg-cross-500 px-4 py-3.5 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400"
        >
          Envoyer ma demande d&apos;inscription
        </button>
      </form>
    </section>
  );
}

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
