import Link from "next/link";
import { db } from "@/lib/db";
import { SearchInstrument } from "@/components/search-instrument";

// Counts come from the database, never from a hardcoded marketing number:
// PRODUCT.md forbids implying more coverage than exists.
async function getLandingData() {
  const [wilayas, categories, partnerCount, wilayasCovered] = await Promise.all([
    db.wilaya.findMany({
      select: { code: true, name: true },
      orderBy: { code: "asc" },
    }),
    db.partnerCategory.findMany({
      orderBy: { sortOrder: "asc" },
      select: {
        id: true,
        slug: true,
        name: true,
        supportsAppointments: true,
        _count: { select: { partners: { where: { status: "ACTIVE" } } } },
      },
    }),
    db.partner.count({ where: { status: "ACTIVE" } }),
    db.partner
      .findMany({
        where: { status: "ACTIVE" },
        distinct: ["wilayaCode"],
        select: { wilayaCode: true },
      })
      .then((rows) => rows.length),
  ]);

  return { wilayas, categories, partnerCount, wilayasCovered };
}

export default async function HomePage() {
  const { wilayas, categories, partnerCount, wilayasCovered } =
    await getLandingData();

  return (
    <main className="flex flex-1 flex-col">
      {/* ---- The field. Committed green owning the whole first viewport. ---- */}
      <section className="relative overflow-hidden bg-cross-700 text-enamel-50">
        {/* The cross as structural module: the lit sign seen from across the
            street. It bleeds off the right edge only — bleeding two edges at
            once severs the arms and the shape stops reading as a cross — and it
            sits below the header so it never muddies the navigation. */}
        <div
          aria-hidden
          className="cross-mark pointer-events-none absolute top-28 right-10 hidden h-[19rem] w-[19rem] text-cross-600 opacity-55 lg:block xl:right-16 xl:h-[22rem] xl:w-[22rem]"
        />

        <div className="relative mx-auto w-full max-w-6xl px-5 pt-8 pb-12 sm:px-8 sm:pt-10 sm:pb-16">
          <header className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <span className="flex items-center gap-2.5">
              <span
                aria-hidden
                className="cross-mark h-6 w-6 text-cross-500 sm:h-7 sm:w-7"
              />
              <span className="font-display text-xl font-bold tracking-[-0.02em] sm:text-2xl">
                DOCTORY
              </span>
            </span>
            <Link
              href="/autour-de-moi"
              className="ml-auto inline-flex min-h-11 shrink-0 items-center py-2.5 font-display text-xs font-bold tracking-[0.1em] text-cross-100 uppercase underline-offset-4 hover:underline sm:text-sm"
            >
              Autour de moi
            </Link>
            <Link
              href="/pro"
              className="inline-flex min-h-11 shrink-0 items-center py-2.5 font-display text-xs font-bold tracking-[0.1em] text-cross-100 uppercase underline-offset-4 hover:underline sm:text-sm"
            >
              {/* The full label wraps to two lines on a 360px screen and crowds
                  the wordmark, so the narrow viewport gets the short form. */}
              <span className="sm:hidden">Espace pro</span>
              <span className="hidden sm:inline">Espace professionnel</span>
            </Link>
          </header>

          <div className="mt-14 max-w-3xl sm:mt-20">
            <h1 className="font-display text-[2.6rem] leading-[0.95] font-bold tracking-[-0.03em] text-balance sm:text-6xl lg:text-7xl">
              Le soin,
              <br />
              près de chez vous.
            </h1>
            <p className="mt-6 max-w-[52ch] text-lg leading-relaxed text-cross-100 sm:text-xl">
              Médecins, dentistes, pharmacies, laboratoires et centres
              d&apos;imagerie — cherchés par wilaya, par commune, ou autour de
              vous.
            </p>
          </div>

          {/* The instrument sits on the fold line, half in the field. */}
          <div className="mt-10 sm:mt-14">
            <SearchInstrument wilayas={wilayas} />
          </div>

          <dl className="mt-8 flex flex-wrap gap-x-10 gap-y-3 font-display text-sm">
            <div className="flex items-baseline gap-2">
              <dt className="sr-only">Professionnels et établissements</dt>
              <dd className="text-2xl font-bold tabular-nums">{partnerCount}</dd>
              <span className="text-cross-100">
                professionnels et établissements
              </span>
            </div>
            <div className="flex items-baseline gap-2">
              <dt className="sr-only">Wilayas couvertes</dt>
              <dd className="text-2xl font-bold tabular-nums">
                {wilayasCovered}
              </dd>
              <span className="text-cross-100">wilayas sur 58</span>
            </div>
          </dl>
        </div>
      </section>

      {/* ---- The two plaques. The role choice the brief puts first. ---- */}
      <section className="mx-auto w-full max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Entrer sur DOCTORY
        </h2>
        <div className="mt-5 grid gap-px bg-ink-900/15 sm:grid-cols-2">
          <RolePlaque
            href="/inscription"
            eyebrow="Je suis patient"
            title="Trouver et prendre rendez-vous"
            lines={[
              "Chercher par spécialité, wilaya ou commune",
              "Voir les professionnels autour de vous",
              "Prendre et suivre vos rendez-vous",
              "Enregistrer vos favoris",
            ]}
            action="Créer un compte patient"
            secondary={{ href: "/connexion?next=%2Fpatient", label: "J'ai déjà un compte" }}
          />
          <RolePlaque
            href="/inscription/professionnel"
            eyebrow="Je suis professionnel"
            title="Gérer ma présence et mon agenda"
            lines={[
              "Créer et vérifier votre profil",
              "Définir horaires et disponibilités",
              "Recevoir et gérer vos rendez-vous",
              "Suivre votre abonnement",
            ]}
            action="Inscrire votre structure"
            secondary={{ href: "/connexion?next=%2Fpro", label: "J'ai déjà une fiche" }}
          />
        </div>
      </section>

      {/* ---- Categories, with counts that come from the database. ---- */}
      <section className="border-t border-enamel-300 bg-enamel-50">
        <div className="mx-auto w-full max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
          <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
            Par type de partenaire
          </h2>
          <ul className="ruled mt-5 border-y border-enamel-300">
            {categories.map((category) => (
              <li key={category.id}>
                <Link
                  href={`/recherche?categorie=${category.slug}`}
                  className="group flex items-center gap-4 py-4 transition-colors hover:bg-cross-50 sm:gap-6 sm:py-5"
                >
                  <span
                    aria-hidden
                    className="cross-mark h-4 w-4 shrink-0 text-cross-500 sm:h-5 sm:w-5"
                  />
                  <span className="font-display text-lg font-bold text-ink-900 group-hover:text-cross-700 sm:text-xl">
                    {category.name}
                  </span>
                  {!category.supportsAppointments && (
                    <span className="border border-carbon-blue/40 bg-carbon-blue-soft px-1.5 py-0.5 font-display text-[11px] font-bold tracking-[0.08em] text-carbon-blue uppercase">
                      Sans rendez-vous
                    </span>
                  )}
                  <span className="ml-auto shrink-0 font-display text-sm tabular-nums text-ink-500">
                    {category._count.partners}
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <footer className="mt-auto border-t border-enamel-300 bg-cross-950 text-cross-100">
        <div className="mx-auto w-full max-w-6xl px-5 py-10 sm:px-8">
          <span className="flex items-center gap-2.5 text-enamel-50">
            <span aria-hidden className="cross-mark h-5 w-5 text-cross-500" />
            <span className="font-display text-lg font-bold">DOCTORY</span>
          </span>
          <p className="mt-4 max-w-[52ch] text-sm leading-relaxed">
            Plateforme de mise en relation entre patients et professionnels de
            santé en Algérie. Les profils affichés dans cette version sont des
            données de démonstration&nbsp;: ils ne désignent aucun praticien
            réel.
          </p>
        </div>
      </footer>
    </main>
  );
}

function RolePlaque({
  href,
  eyebrow,
  title,
  lines,
  action,
  secondary,
}: {
  href: string;
  eyebrow: string;
  title: string;
  lines: string[];
  action: string;
  /** The quieter way in, for someone who already has an account. */
  secondary: { href: string; label: string };
}) {
  return (
    // The plaque is two destinations, so it is no longer one link: registering
    // and signing in are different acts, and burying the second inside the first
    // is how both landing plaques used to dead-end at a sign-in wall.
    <div className="group flex flex-col bg-enamel-50 p-6 transition-colors hover:bg-cross-50 sm:p-8">
      <Link href={href} className="flex flex-1 flex-col">
        <span className="font-display text-[11px] font-bold tracking-[0.14em] text-cross-700 uppercase">
          {eyebrow}
        </span>
        <h3 className="mt-2 font-display text-2xl font-bold text-ink-900 sm:text-3xl">
          {title}
        </h3>
        <ul className="mt-5 flex flex-col gap-2.5 text-ink-600">
          {lines.map((line) => (
            <li key={line} className="flex gap-3 text-[15px] leading-snug">
              <span
                aria-hidden
                className="mt-[0.42em] h-1.5 w-1.5 shrink-0 bg-cross-500"
              />
              {line}
            </li>
          ))}
        </ul>
        <span className="mt-7 inline-flex min-h-11 items-center gap-2 font-display text-sm font-bold text-cross-700">
          {action}
          <span aria-hidden>&rarr;</span>
        </span>
      </Link>
      <Link
        href={secondary.href}
        className="inline-flex min-h-11 items-center font-display text-sm text-ink-500 underline underline-offset-4 hover:text-cross-700"
      >
        {secondary.label}
      </Link>
    </div>
  );
}
