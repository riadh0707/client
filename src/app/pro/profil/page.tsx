import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { getCurrentUser, partnerPermissions } from "@/lib/auth";
import { requirePartnerContext } from "@/lib/pro";
import { groupByWeekday, type Interval } from "@/lib/hours";

export const metadata: Metadata = { title: "Mon profil" };
export const dynamic = "force-dynamic";

async function saveProfile(formData: FormData) {
  "use server";

  const partnerId = String(formData.get("partnerId") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fprofil");

  // Profile, unlike the agenda, is the owner's alone — a secretary reaching this
  // action directly is refused here, not merely hidden from in the navigation.
  if (!partnerPermissions(user, partnerId).canManageProfile) {
    redirect("/pro?erreur=droits");
  }

  const bio = String(formData.get("bio") ?? "").trim();
  const address = String(formData.get("address") ?? "").trim();
  const phone = String(formData.get("phone") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim();
  const subSpecialty = String(formData.get("subSpecialty") ?? "").trim();
  const slotDuration = Number(formData.get("slotDurationMinutes"));

  if (!address || !phone) redirect("/pro/profil?erreur=champs");

  await db.partner.update({
    where: { id: partnerId },
    data: {
      bio: bio || null,
      address,
      phone,
      email: email || null,
      subSpecialty: subSpecialty || null,
      slotDurationMinutes:
        Number.isFinite(slotDuration) && slotDuration >= 5 && slotDuration <= 120
          ? slotDuration
          : undefined,
    },
  });

  revalidatePath("/pro/profil");
  redirect("/pro/profil?enregistre=1");
}

export default async function ProfilePage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const query = await searchParams;
  const { partner, permissions } = await requirePartnerContext("/pro/profil");
  if (!partner) redirect("/pro");
  if (!permissions.canManageProfile) redirect("/pro?erreur=droits");

  const hours = await db.openingHours.findMany({
    where: { partnerId: partner.id },
    select: { weekday: true, opensAt: true, closesAt: true },
  });
  const schedule = groupByWeekday(hours as Interval[]);

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Mon profil
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">{partner.displayName}</p>

      {query.enregistre && (
        <p role="status" className="mt-5 border border-cross-600/40 bg-cross-100 px-4 py-3 text-[15px] text-ink-900">
          Modifications enregistrées.
        </p>
      )}
      {query.erreur === "champs" && (
        <p role="alert" className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900">
          L&apos;adresse et le téléphone sont obligatoires.
        </p>
      )}

      <form action={saveProfile} className="mt-8 flex flex-col gap-5">
        <input type="hidden" name="partnerId" value={partner.id} />

        <Field label="Présentation" htmlFor="bio">
          <textarea
            id="bio"
            name="bio"
            rows={4}
            defaultValue={partner.bio ?? ""}
            className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
          />
        </Field>

        <Field label="Sous-spécialité" htmlFor="subSpecialty">
          <input
            id="subSpecialty"
            name="subSpecialty"
            defaultValue={partner.subSpecialty ?? ""}
            className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
          />
        </Field>

        <Field label="Adresse" htmlFor="address">
          <input
            id="address"
            name="address"
            required
            defaultValue={partner.address}
            className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
          />
        </Field>

        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Téléphone" htmlFor="phone">
            <input
              id="phone"
              name="phone"
              required
              defaultValue={partner.phone}
              className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
            />
          </Field>
          <Field label="E-mail" htmlFor="email">
            <input
              id="email"
              name="email"
              type="email"
              defaultValue={partner.email ?? ""}
              className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] text-ink-900"
            />
          </Field>
        </div>

        <Field
          label="Durée d'un créneau (minutes)"
          htmlFor="slotDurationMinutes"
          hint="Détermine la grille de rendez-vous proposée aux patients."
        >
          <input
            id="slotDurationMinutes"
            name="slotDurationMinutes"
            type="number"
            min={5}
            max={120}
            step={5}
            defaultValue={partner.slotDurationMinutes}
            className="w-full border border-enamel-300 bg-white px-3 py-2.5 text-[15px] tabular-nums text-ink-900"
          />
        </Field>

        <button
          type="submit"
          className="self-start bg-cross-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-cross-950 uppercase hover:bg-cross-400"
        >
          Enregistrer
        </button>
      </form>

      <section className="mt-12">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Horaires actuels
        </h2>
        <ul className="ruled mt-3 border-y border-enamel-300">
          {schedule.map((day) => (
            <li key={day.weekday} className="flex items-baseline justify-between gap-4 py-2.5">
              <span className="text-[15px] text-ink-900">{day.label}</span>
              <span className="text-right text-sm tabular-nums text-ink-500">
                {day.intervals.length === 0
                  ? "Fermé"
                  : day.intervals.map((i) => `${i.opensAt} – ${i.closesAt}`).join(" · ")}
              </span>
            </li>
          ))}
        </ul>
        <p className="mt-3 text-sm leading-relaxed text-ink-500">
          La modification des horaires n&apos;est pas encore disponible depuis
          cette page. Contactez l&apos;administration de DOCTORY pour les faire
          ajuster.
        </p>
      </section>
    </main>
  );
}

function Field({
  label,
  htmlFor,
  hint,
  children,
}: {
  label: string;
  htmlFor: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label
        htmlFor={htmlFor}
        className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
      >
        {label}
      </label>
      {children}
      {hint && <p className="mt-1.5 text-sm text-ink-500">{hint}</p>}
    </div>
  );
}
