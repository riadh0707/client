import "server-only";
import { cookies } from "next/headers";
import { createHmac, scryptSync, timingSafeEqual, randomBytes } from "node:crypto";
import { db } from "@/lib/db";

/**
 * Session handling.
 *
 * A signed cookie carrying the user id, not a JWT: this needs to identify a
 * session and nothing more, and a hand-rolled HMAC over one field is easier to
 * audit than a token format with an algorithm field. Sessions are stateless, so
 * revocation is by expiry — acceptable while the platform has no "sign out
 * everywhere" requirement, and the note is here for when it does.
 */

const COOKIE = "doctory_session";
const MAX_AGE_SECONDS = 60 * 60 * 24 * 7;

function secret() {
  const value = process.env.SESSION_SECRET;
  if (value) return value;
  if (process.env.NODE_ENV === "production") {
    // Falling back to a hardcoded key in production would make every session
    // forgeable by anyone reading this repository.
    throw new Error("SESSION_SECRET must be set in production.");
  }
  return "doctory-development-secret-not-for-production";
}

function sign(payload: string) {
  return createHmac("sha256", secret()).update(payload).digest("base64url");
}

function serialise(userId: string) {
  const expires = Date.now() + MAX_AGE_SECONDS * 1000;
  const payload = `${userId}.${expires}`;
  return `${payload}.${sign(payload)}`;
}

function parse(token: string) {
  const parts = token.split(".");
  if (parts.length !== 3) return null;
  const [userId, expires, signature] = parts;

  const expected = sign(`${userId}.${expires}`);
  const a = Buffer.from(signature);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !timingSafeEqual(a, b)) return null;
  if (Number(expires) < Date.now()) return null;

  return userId;
}

/** Verifies a password against the `salt:hash` format the seed writes. */
export function verifyPassword(plain: string, stored: string) {
  const [salt, hash] = stored.split(":");
  if (!salt || !hash) return false;
  const candidate = scryptSync(plain, salt, 64);
  const expected = Buffer.from(hash, "hex");
  if (candidate.length !== expected.length) return false;
  return timingSafeEqual(candidate, expected);
}

export function hashPassword(plain: string) {
  const salt = randomBytes(16).toString("hex");
  return `${salt}:${scryptSync(plain, salt, 64).toString("hex")}`;
}

export async function startSession(userId: string) {
  const store = await cookies();
  store.set(COOKIE, serialise(userId), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: MAX_AGE_SECONDS,
  });
}

export async function endSession() {
  const store = await cookies();
  store.delete(COOKIE);
}

export type SessionUser = {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  role: "PATIENT" | "PROFESSIONAL" | "SECRETARY" | "ADMIN";
  /** Partners this user may act for, with the right they hold on each. */
  memberships: { partnerId: string; partnerSlug: string; partnerName: string; role: "OWNER" | "SECRETARY" }[];
};

export async function getCurrentUser(): Promise<SessionUser | null> {
  const store = await cookies();
  const token = store.get(COOKIE)?.value;
  if (!token) return null;

  const userId = parse(token);
  if (!userId) return null;

  const user = await db.user.findUnique({
    where: { id: userId },
    select: {
      id: true,
      email: true,
      firstName: true,
      lastName: true,
      role: true,
      isActive: true,
      memberships: {
        select: {
          role: true,
          partner: { select: { id: true, slug: true, displayName: true } },
        },
      },
    },
  });

  // A deactivated account keeps its cookie until expiry, so the check belongs
  // here rather than only at sign-in.
  if (!user || !user.isActive) return null;

  return {
    id: user.id,
    email: user.email,
    firstName: user.firstName,
    lastName: user.lastName,
    role: user.role,
    memberships: user.memberships.map((membership) => ({
      partnerId: membership.partner.id,
      partnerSlug: membership.partner.slug,
      partnerName: membership.partner.displayName,
      role: membership.role,
    })),
  };
}

/**
 * What a user may do with a partner. Secretaries run the agenda and nothing
 * else — the brief is explicit that they must not hold the professional's or the
 * administrator's rights, so the distinction lives here rather than being
 * re-derived at each call site.
 */
export function partnerPermissions(user: SessionUser | null, partnerId: string) {
  if (!user) return { canView: false, canManageAgenda: false, canManageProfile: false };
  if (user.role === "ADMIN")
    return { canView: true, canManageAgenda: true, canManageProfile: true };

  const membership = user.memberships.find((m) => m.partnerId === partnerId);
  if (!membership)
    return { canView: false, canManageAgenda: false, canManageProfile: false };

  return {
    canView: true,
    canManageAgenda: true,
    // Profile, subscription and billing stay with the owner.
    canManageProfile: membership.role === "OWNER",
  };
}
