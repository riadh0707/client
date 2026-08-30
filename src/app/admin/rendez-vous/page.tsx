import type { Metadata } from "next";
import Link from "next/link";
import { Prisma } from "@/generated/prisma";
import { db } from "@/lib/db";
import { requireAdmin } from "@/lib/admin";
import { StatusBadge, formatAppointmentDate } from "@/components/appointment-row";

export const metadata: Metadata = { title: "Rendez-vous" };
export const dynamic = "force-dynamic";

const PAGE_SIZE = 25;

export default async function AdminAppointmentsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  await requireAdmin();
  const raw = await searchParams;
  const one = (k: string) => {
    const v = raw[k];
    return Array.isArray(v) ? v[0] : v;
  };

  const q = one("q") ?? "";
  const statut = one("statut") ?? "";
  const page = Math.max(1, Number(one("page")) || 1);

  const where: Prisma.AppointmentWhereInput = {};
  if (statut) where.status = statut as Prisma.AppointmentWhereInput["status"];
  if (q) {
    where.OR = [
      { partner: { displayName: { contains: q } } },
      { patient: { firstName: { contains: q } } },
      { patient: { lastName: { contains: q } } },
    ];
  }

  const [appointments, total] = await Promise.all([
    db.appointment.findMany({
      where,
      orderBy: { startAt: "desc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true,
        startAt: true,
        status: true,
        serviceName: true,
        partner: {
          select: {
            slug: true,
            displayName: true,
            wilaya: { select: { name: true } },
          },
        },
        patient: { select: { firstName: true, lastName: true } },
      },
    }),
    db.appointment.count({ where }),
  ]);

  const pageCount = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const query = new URLSearchParams();
  for (const [k, v] of Object.entries(raw)) {
    if (v === undefined) continue;
    query.set(k, Array.isArray(v) ? v[0] : v);
  }

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Rendez-vous
      </h1>
      <p className="mt-1 text-[15px] text-ink-600">
        {total} rendez-vous correspondant aux filtres.
      </p>

      <form
        method="get"
        className="mt-6 flex flex-wrap items-end gap-3 border border-enamel-300 bg-enamel-50 p-4"
      >
        <div className="min-w-[14rem] flex-1">
          <label htmlFor="q" className="mb-1 block font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
            Recherche
          </label>
          <input
            id="q"
            name="q"
            defaultValue={q}
            placeholder="Patient ou partenaire"
            className="w-full border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900"
          />
        </div>
        <div>
          <label htmlFor="statut" className="mb-1 block font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
            Statut
          </label>
          <select
            id="statut"
            name="statut"
            defaultValue={statut}
            className="border border-enamel-300 bg-white px-2.5 py-2 text-sm text-ink-900"
          >
            <option value="">Tous</option>
            <option value="PENDING">En attente</option>
            <option value="CONFIRMED">Confirmé</option>
            <option value="COMPLETED">Terminé</option>
            <option value="CANCELLED">Annulé</option>
            <option value="NO_SHOW">Absent</option>
          </select>
        </div>
        <button
          type="submit"
          className="bg-cross-500 px-4 py-2.5 font-display text-xs font-bold tracking-[0.08em] text-cross-950 uppercase hover:bg-cross-400"
        >
          Filtrer
        </button>
        <Link
          href="/admin/rendez-vous"
          className="font-display text-xs font-bold tracking-[0.08em] text-ink-500 uppercase underline underline-offset-4"
        >
          Réinitialiser
        </Link>
      </form>

      {appointments.length === 0 ? (
        <p className="mt-6 bg-enamel-50 px-5 py-12 text-center text-[15px] text-ink-600">
          Aucun rendez-vous ne correspond à ces filtres.
        </p>
      ) : (
        <div className="mt-6 overflow-x-auto border border-enamel-300">
          <table className="w-full min-w-[46rem] border-collapse bg-enamel-50 text-left">
            <thead>
              <tr className="border-b border-enamel-300">
                {["Patient", "Partenaire", "Wilaya", "Date", "Motif", "Statut"].map((h) => (
                  <th key={h} className="px-3 py-2.5 font-display text-[11px] font-bold tracking-[0.12em] text-ink-500 uppercase">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="ruled">
              {appointments.map((a) => (
                <tr key={a.id} className="align-top">
                  <td className="px-3 py-3 text-sm text-ink-900">
                    {a.patient.firstName} {a.patient.lastName}
                  </td>
                  <td className="px-3 py-3 text-sm">
                    <Link href={`/partenaire/${a.partner.slug}`} className="text-ink-900 hover:text-cross-700">
                      {a.partner.displayName}
                    </Link>
                  </td>
                  <td className="px-3 py-3 text-sm text-ink-600">{a.partner.wilaya.name}</td>
                  <td className="px-3 py-3 text-sm tabular-nums text-ink-600">
                    {formatAppointmentDate(a.startAt)}
                  </td>
                  <td className="px-3 py-3 text-sm text-ink-600">{a.serviceName ?? "Consultation"}</td>
                  <td className="px-3 py-3"><StatusBadge status={a.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {pageCount > 1 && (
        <nav aria-label="Pagination" className="mt-5 flex items-center justify-between gap-4">
          {page > 1 ? <PageLink query={query} page={page - 1} label="← Précédent" /> : <span />}
          <span className="font-display text-sm tabular-nums text-ink-500">
            Page {page} sur {pageCount}
          </span>
          {page < pageCount ? <PageLink query={query} page={page + 1} label="Suivant →" /> : <span />}
        </nav>
      )}
    </main>
  );
}

function PageLink({ query, page, label }: { query: URLSearchParams; page: number; label: string }) {
  const next = new URLSearchParams(query);
  next.set("page", String(page));
  return (
    <Link
      href={`/admin/rendez-vous?${next}`}
      className="font-display text-xs font-bold tracking-[0.08em] text-cross-700 uppercase hover:underline"
    >
      {label}
    </Link>
  );
}
