import "server-only";
import { redirect } from "next/navigation";
import { getCurrentUser, partnerPermissions, type SessionUser } from "@/lib/auth";
import { db } from "@/lib/db";

/**
 * Resolves which partner the current user is acting for in the professional
 * space, and what they may do with it.
 *
 * Every /pro page goes through this rather than reading memberships directly, so
 * the secretary boundary is enforced in one place instead of being re-derived —
 * and forgotten — page by page.
 */
export async function requirePartnerContext(next: string) {
  const user = await getCurrentUser();
  if (!user) redirect(`/connexion?next=${encodeURIComponent(next)}`);

  // A patient account has no business here; send it to its own space rather
  // than to a login screen it is already past.
  if (user.role === "PATIENT") redirect("/patient");

  const membership = user.memberships[0];
  if (!membership) {
    return { user, partner: null, permissions: partnerPermissions(user, "") };
  }

  const partner = await db.partner.findUnique({
    where: { id: membership.partnerId },
    include: {
      category: true,
      specialty: true,
      wilaya: true,
      commune: true,
      subscriptions: {
        orderBy: { createdAt: "desc" },
        take: 1,
        include: { plan: true },
      },
    },
  });

  if (!partner) {
    return { user, partner: null, permissions: partnerPermissions(user, "") };
  }

  return {
    user,
    partner,
    permissions: partnerPermissions(user, partner.id),
  };
}

export type PartnerContext = Awaited<ReturnType<typeof requirePartnerContext>>;

/**
 * Guards a write against the acting user's rights on a partner. Server actions
 * call this before mutating; returning a redirect rather than throwing keeps the
 * failure mode a navigation the user can understand.
 */
export function assertAgendaRight(user: SessionUser, partnerId: string) {
  const permissions = partnerPermissions(user, partnerId);
  if (!permissions.canManageAgenda) redirect("/pro?erreur=droits");
}
