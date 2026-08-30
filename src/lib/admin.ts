import "server-only";
import { redirect } from "next/navigation";
import { getCurrentUser, type SessionUser } from "@/lib/auth";
import { db } from "@/lib/db";

/**
 * Guards an administrative server action.
 *
 * Layout-level protection covers navigation, but a server action is its own
 * endpoint: anyone can post to it directly. Every admin mutation calls this
 * first, so the gate is on the write and not only on the page that renders the
 * button.
 */
export async function requireAdmin(): Promise<SessionUser> {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fadmin");
  if (user.role !== "ADMIN") redirect("/");
  return user;
}

/**
 * Records an administrative action.
 *
 * The summary is written now rather than derived at read time: a partner
 * renamed or deleted later must not rewrite what the log says happened.
 */
export async function recordActivity(params: {
  actorId: string;
  action: string;
  summary: string;
  targetType?: string;
  targetId?: string;
}) {
  await db.activityLog.create({
    data: {
      actorId: params.actorId,
      action: params.action,
      summary: params.summary,
      targetType: params.targetType ?? null,
      targetId: params.targetId ?? null,
    },
  });
}

export const PARTNER_STATUS_LABELS: Record<
  string,
  { label: string; className: string }
> = {
  PENDING: {
    label: "En attente",
    className: "border-carbon-amber/50 bg-carbon-amber-soft text-carbon-amber",
  },
  ACTIVE: {
    label: "Actif",
    className: "border-azur-600/40 bg-azur-100 text-azur-800",
  },
  SUSPENDED: {
    label: "Suspendu",
    className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose",
  },
};

export const VERIFICATION_LABELS: Record<
  string,
  { label: string; className: string }
> = {
  UNVERIFIED: {
    label: "Non vérifié",
    className: "border-ink-300/50 bg-enamel-200 text-ink-600",
  },
  PENDING: {
    label: "Vérification demandée",
    className: "border-carbon-amber/50 bg-carbon-amber-soft text-carbon-amber",
  },
  VERIFIED: {
    label: "Vérifié",
    className: "border-azur-600/40 bg-azur-100 text-azur-800",
  },
  REJECTED: {
    label: "Rejeté",
    className: "border-carbon-rose/50 bg-carbon-rose-soft text-carbon-rose",
  },
};
