import type { Metadata } from "next";
import Link from "next/link";
import { SiteHeader } from "@/components/site-header";
import { RodMark } from "@/components/rod-mark";

export const metadata: Metadata = { title: "Page introuvable" };

/**
 * The 404.
 *
 * It replaces Next.js's stock page, which was in English on a French Algerian
 * health platform and offered nothing but its own title. A patient reaches this
 * page for two ordinary reasons — a mistyped address, or a partner whose profile
 * has been suspended since the link was shared — so it says both, and puts the
 * two things they were probably trying to do within reach.
 */
export default function NotFound() {
  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-5 py-16 sm:px-8 sm:py-24">
        <RodMark className="h-12 w-12 text-enamel-300" />
        <h1 className="mt-6 font-display text-3xl font-bold text-ink-900 sm:text-4xl">
          Cette page n&apos;existe pas
        </h1>
        <p className="mt-3 max-w-[52ch] text-[15px] leading-relaxed text-ink-600">
          L&apos;adresse est peut-être incomplète. Il se peut aussi que la fiche
          que vous cherchiez ait été retirée&nbsp;: un professionnel suspendu ou
          en cours de vérification n&apos;apparaît plus publiquement.
        </p>

        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
          <Link
            href="/recherche"
            className="inline-flex min-h-11 items-center justify-center bg-rod-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-rod-950 uppercase hover:bg-rod-400"
          >
            Chercher un professionnel
          </Link>
          <Link
            href="/autour-de-moi"
            className="inline-flex min-h-11 items-center justify-center border border-rod-700 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-rod-700 uppercase hover:bg-rod-100"
          >
            Autour de moi
          </Link>
        </div>

        <p className="mt-8 text-sm text-ink-500">
          <Link
            href="/"
            className="inline-flex min-h-11 items-center underline underline-offset-4"
          >
            Retour à l&apos;accueil
          </Link>
        </p>
      </div>
    </main>
  );
}
