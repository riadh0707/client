import type { Metadata } from "next";
import Link from "next/link";
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

/**
 * Services, edited with the same idiom as the opening hours: every service the
 * partner already has, plus one blank row to add another. Clearing a name
 * deletes that service. No JavaScript, no repeater widget.
 *
 * A blank price is stored as null, never as zero. The public profile renders a
 * null price as "Tarif non communiqué" precisely because not publishing a price
 * is common here — writing 0 would turn a silence into a claim that the
 * consultation is free.
 */
async function saveServices(formData: FormData) {
  "use server";

  const partnerId = String(formData.get("partnerId") ?? "");
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpro%2Fprofil");
  if (!partnerPermissions(user, partnerId).canManageProfile) {
    redirect("/pro?erreur=droits");
  }

  const rows: { name: string; priceDzd: number | null; durationMinutes: number | null }[] = [];
  for (let index = 0; index < 30; index += 1) {
    const name = String(formData.get(`s${index}-name`) ?? "").trim();
    if (!name) continue; // cleared name = deleted row, and the blank spare
    const priceRaw = String(formData.get(`s${index}-price`) ?? "").trim();
    const durationRaw = String(formData.get(`s${index}-duration`) ?? "").trim();
    const price = Number(priceRaw);
    const duration = Number(durationRaw);

    if (priceRaw && (!Number.isFinite(price) || price < 0)) {
      redirect(`/pro/profil?erreur=${encodeURIComponent(`« ${name} » : le tarif doit être un montant en dinars, ou vide.`)}#prestations`);
    }
    if (durationRaw && (!Number.isFinite(duration) || duration <= 0)) {
      redirect(`/pro/profil?erreur=${encodeURIComponent(`« ${name} » : la durée doit être un nombre de minutes, ou vide.`)}#prestations`);
    }

    rows.push({
      name,
      priceDzd: priceRaw ? Math.round(price) : null,
      durationMinutes: durationRaw ? Math.round(duration) : null,
    });
  }

  await db.$transaction(async (tx) => {
    await tx.service.deleteMany({ where: { partnerId } });
    if (rows.length > 0) {
      await tx.service.createMany({
        data: rows.map((row) => ({ ...row, partnerId })),
      });
    }
  });

  revalidatePath("/pro/profil");
  redirect("/pro/profil?prestations=1#prestations");
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

  const [hours, services] = await Promise.all([
    db.openingHours.findMany({
      where: { partnerId: partner.id },
      select: { weekday: true, opensAt: true, closesAt: true },
    }),
    db.service.findMany({
      where: { partnerId: partner.id },
      orderBy: { name: "asc" },
      select: { id: true, name: true, priceDzd: true, durationMinutes: true },
    }),
  ]);
  const schedule = groupByWeekday(hours as Interval[]);

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Mon profil
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">{partner.displayName}</p>

      {query.enregistre && (
        <p role="status" className="mt-5 border border-azur-600/40 bg-azur-100 px-4 py-3 text-[15px] text-ink-900">
          Modifications enregistrées.
        </p>
      )}
      {query.erreur === "champs" && (
        <p role="alert" className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900">
          L&apos;adresse et le téléphone sont obligatoires.
        </p>
      )}
      {typeof query.erreur === "string" && query.erreur !== "champs" && (
        <p role="alert" className="mt-5 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] leading-snug text-ink-900">
          {query.erreur}
        </p>
      )}
      {query.prestations && (
        <p role="status" className="mt-5 border border-azur-600/40 bg-azur-100 px-4 py-3 text-[15px] text-ink-900">
          Prestations enregistrées. Elles apparaissent sur votre fiche et parmi
          les motifs proposés aux patients.
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
          className="self-start bg-azur-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-azur-950 uppercase hover:bg-azur-400"
        >
          Enregistrer
        </button>
      </form>

      <section id="prestations" className="mt-12">
        <h2 className="font-display text-[11px] font-bold tracking-[0.16em] text-ink-500 uppercase">
          Prestations
        </h2>
        <p className="mt-1.5 text-[15px] leading-relaxed text-ink-600">
          Affichées sur votre fiche, et proposées aux patients comme motif de
          rendez-vous. Laissez un tarif vide s&apos;il n&apos;est pas
          communiqué&nbsp;: la fiche indiquera «&nbsp;tarif non
          communiqué&nbsp;», jamais «&nbsp;gratuit&nbsp;».
        </p>

        <form action={saveServices} className="mt-4">
          <input type="hidden" name="partnerId" value={partner.id} />
          <div className="flex flex-col gap-px bg-ink-900/10">
            {Array.from({ length: services.length + 1 }, (_, index) => {
              const service = services[index];
              return (
                <div
                  key={service?.id ?? `nouveau-${index}`}
                  className="grid gap-3 bg-enamel-50 p-4 sm:grid-cols-[1fr_9rem_8rem]"
                >
                  <div>
                    <label
                      htmlFor={`s${index}-name`}
                      className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
                    >
                      {service ? "Prestation" : "Ajouter une prestation"}
                    </label>
                    <input
                      id={`s${index}-name`}
                      name={`s${index}-name`}
                      defaultValue={service?.name ?? ""}
                      maxLength={80}
                      placeholder={service ? undefined : "Consultation de contrôle"}
                      className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] text-ink-900 placeholder:text-ink-300"
                    />
                  </div>
                  <div>
                    <label
                      htmlFor={`s${index}-price`}
                      className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
                    >
                      Tarif (DZD)
                    </label>
                    <input
                      id={`s${index}-price`}
                      name={`s${index}-price`}
                      type="number"
                      min={0}
                      step={100}
                      defaultValue={service?.priceDzd ?? ""}
                      className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
                    />
                  </div>
                  <div>
                    <label
                      htmlFor={`s${index}-duration`}
                      className="mb-1.5 block font-display text-[11px] font-bold tracking-[0.14em] text-ink-500 uppercase"
                    >
                      Durée (min)
                    </label>
                    <input
                      id={`s${index}-duration`}
                      name={`s${index}-duration`}
                      type="number"
                      min={5}
                      step={5}
                      defaultValue={service?.durationMinutes ?? ""}
                      className="min-h-11 w-full border border-enamel-300 bg-white px-3 py-2 text-[15px] tabular-nums text-ink-900"
                    />
                  </div>
                </div>
              );
            })}
          </div>
          <p className="mt-3 text-sm leading-relaxed text-ink-500">
            Effacez le nom d&apos;une prestation pour la retirer.
          </p>
          <button
            type="submit"
            className="mt-4 min-h-11 bg-azur-500 px-5 py-3 font-display text-sm font-bold tracking-[0.06em] text-azur-950 uppercase hover:bg-azur-400"
          >
            Enregistrer les prestations
          </button>
        </form>
      </section>

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
        <Link
          href="/pro/horaires"
          className="mt-4 inline-flex min-h-11 items-center border border-azur-700 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-azur-700 uppercase hover:bg-azur-100"
        >
          Modifier mes horaires
        </Link>
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
