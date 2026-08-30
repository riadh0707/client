import type { Metadata } from "next";
import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { SiteHeader } from "@/components/site-header";
import { SlotPicker } from "@/components/slot-picker";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { getAvailability, isSlotBookable } from "@/lib/slots";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const partner = await db.partner.findUnique({
    where: { slug },
    select: { displayName: true },
  });
  return { title: partner ? `Rendez-vous · ${partner.displayName}` : "Rendez-vous" };
}

async function book(formData: FormData) {
  "use server";

  const slug = String(formData.get("slug") ?? "");
  const startAtRaw = String(formData.get("startAt") ?? "");
  const reason = String(formData.get("reason") ?? "").trim();
  const serviceName = String(formData.get("serviceName") ?? "").trim();

  const fail = (message: string) =>
    redirect(`/partenaire/${slug}/rendez-vous?error=${encodeURIComponent(message)}`);

  const user = await getCurrentUser();
  if (!user) {
    redirect(
      `/connexion?next=${encodeURIComponent(`/partenaire/${slug}/rendez-vous`)}`,
    );
  }

  const partner = await db.partner.findUnique({
    where: { slug },
    select: { id: true, slotDurationMinutes: true },
  });
  if (!partner) notFound();

  const startAt = new Date(startAtRaw);
  if (Number.isNaN(startAt.getTime())) fail("Créneau invalide.");

  // Re-check against fresh data: the grid the patient saw may be stale, and two
  // people can load the same page at the same moment.
  const check = await isSlotBookable(partner.id, startAt);
  if (!check.ok) fail(check.reason);

  const existing = await db.appointment.findFirst({
    where: {
      partnerId: partner.id,
      patientId: user.id,
      status: { in: ["PENDING", "CONFIRMED"] },
      startAt: { gte: new Date() },
    },
    select: { id: true },
  });
  if (existing) {
    fail("Vous avez déjà un rendez-vous à venir avec ce praticien.");
  }

  const appointment = await db.appointment.create({
    data: {
      partnerId: partner.id,
      patientId: user.id,
      startAt,
      endAt: check.ok ? check.endAt : startAt,
      status: "PENDING",
      reason: reason || null,
      serviceName: serviceName || null,
    },
  });

  // Both sides are told. The patient sees the request is pending; the practice
  // sees a new request it has to act on.
  const members = await db.partnerMember.findMany({
    where: { partnerId: partner.id },
    select: { userId: true },
  });

  await db.notification.createMany({
    data: [
      {
        userId: user.id,
        kind: "APPOINTMENT_REQUESTED",
        title: "Demande de rendez-vous envoyée",
        body: "Votre demande est en attente de confirmation par le praticien.",
        href: "/patient/rendez-vous",
      },
      ...members.map((member) => ({
        userId: member.userId,
        kind: "APPOINTMENT_REQUESTED" as const,
        title: "Nouvelle demande de rendez-vous",
        body: `${user.firstName} ${user.lastName} demande un créneau.`,
        href: "/pro/agenda",
      })),
    ],
  });

  redirect(`/patient/rendez-vous?nouveau=${appointment.id}`);
}

export default async function BookingPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const query = await searchParams;
  const user = await getCurrentUser();

  const partner = await db.partner.findUnique({
    where: { slug },
    include: {
      category: true,
      specialty: true,
      wilaya: true,
      commune: true,
      services: { orderBy: { name: "asc" }, select: { id: true, name: true } },
    },
  });

  if (!partner || partner.status !== "ACTIVE") notFound();

  // A pharmacy has no agenda; sending a patient to a booking form for one would
  // be a dead end the category model already knows about.
  if (!partner.category.supportsAppointments) {
    redirect(`/partenaire/${partner.slug}`);
  }

  const availability = await getAvailability(partner.id);
  const error = typeof query.error === "string" ? query.error : null;

  return (
    <main className="flex flex-1 flex-col">
      <SiteHeader />

      <div className="mx-auto w-full max-w-2xl flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <nav
          aria-label="Fil d'Ariane"
          className="mb-5 flex items-center gap-1 text-sm text-ink-500"
        >
          <Link
            href={`/partenaire/${partner.slug}`}
            className="inline-flex min-h-11 items-center underline underline-offset-4 hover:text-cross-700"
          >
            {partner.displayName}
          </Link>
          <span aria-hidden> / </span>
          <span>Rendez-vous</span>
        </nav>

        <h1 className="font-display text-3xl font-bold text-ink-900 sm:text-4xl">
          Prendre rendez-vous
        </h1>
        <p className="mt-2 text-[15px] text-ink-600">
          {partner.specialty?.name ?? partner.category.name} ·{" "}
          {partner.commune.name}, {partner.wilaya.name} · créneaux de{" "}
          {partner.slotDurationMinutes} minutes.
        </p>

        {error && (
          <p
            role="alert"
            className="mt-6 border border-carbon-rose/50 bg-carbon-rose-soft px-4 py-3 text-[15px] text-ink-900"
          >
            {error}
          </p>
        )}

        {!user && (
          <p className="mt-6 border border-carbon-blue/40 bg-carbon-blue-soft px-4 py-3 text-[15px] text-ink-900">
            Vous devrez vous connecter pour confirmer la demande.{" "}
            <Link
              href={`/connexion?next=${encodeURIComponent(`/partenaire/${partner.slug}/rendez-vous`)}`}
              className="inline-flex min-h-11 items-center font-bold underline underline-offset-4"
            >
              Se connecter maintenant
            </Link>
          </p>
        )}

        <form action={book} className="mt-8 flex flex-col gap-px bg-ink-900/10">
          <input type="hidden" name="slug" value={partner.slug} />
          <SlotPicker availability={availability} services={partner.services} />
        </form>

        <p className="mt-6 text-sm leading-relaxed text-ink-500">
          Votre demande est envoyée au praticien, qui la confirme ou la refuse.
          Vous serez notifié dans votre espace patient.
        </p>
      </div>
    </main>
  );
}
