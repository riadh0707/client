"use client";

import Link from "next/link";
import { useEffect } from "react";
import { RodMark } from "@/components/rod-mark";

/**
 * The uncaught-error boundary.
 *
 * Deliberately does not show the error message: on a health platform the
 * failing query can carry a patient's name or a practitioner's identifier, and
 * a stack trace on screen tells the person in front of it nothing they can act
 * on. What it does give is the digest — the one string that lets an operator
 * find this exact failure in the logs — and a way back.
 *
 * `reset` re-renders the segment, so a transient database failure recovers
 * without a full reload. It carries no site header: the header is a server
 * component, and this boundary must render even when the layout below it is
 * what failed.
 */
export default function ErrorBoundary({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <main className="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-5 py-16 sm:px-8 sm:py-24">
      <RodMark className="h-12 w-12 text-carbon-rose/40" />
      <h1 className="mt-6 font-display text-3xl font-bold text-ink-900 sm:text-4xl">
        Une erreur est survenue
      </h1>
      <p className="mt-3 max-w-[52ch] text-[15px] leading-relaxed text-ink-600">
        Cette page n&apos;a pas pu être affichée. Vos rendez-vous et vos données
        ne sont pas affectés. Réessayez&nbsp;: si le problème persiste, revenez
        dans quelques minutes.
      </p>

      <div className="mt-8 flex flex-col gap-3 sm:flex-row">
        <button
          type="button"
          onClick={reset}
          className="inline-flex min-h-11 items-center justify-center bg-rod-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-rod-950 uppercase hover:bg-rod-400"
        >
          Réessayer
        </button>
        <Link
          href="/"
          className="inline-flex min-h-11 items-center justify-center border border-rod-700 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-rod-700 uppercase hover:bg-rod-100"
        >
          Retour à l&apos;accueil
        </Link>
      </div>

      {error.digest && (
        <p className="mt-8 border-t border-enamel-300 pt-4 text-sm text-ink-400">
          Référence de l&apos;incident&nbsp;:{" "}
          <span className="font-display tabular-nums text-ink-600">
            {error.digest}
          </span>
          <br />
          Communiquez-la si vous signalez le problème.
        </p>
      )}
    </main>
  );
}
