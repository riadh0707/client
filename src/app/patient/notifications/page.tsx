import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { dateTimeShort } from "@/lib/format";

export const metadata: Metadata = { title: "Notifications" };
export const dynamic = "force-dynamic";

export default async function NotificationsPage() {
  const user = await getCurrentUser();
  if (!user) redirect("/connexion?next=%2Fpatient%2Fnotifications");

  const notifications = await db.notification.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: "desc" },
    take: 50,
  });

  // Opening the centre is the read receipt. Marking them read on render keeps
  // the badge honest without a second interaction the user has to discover.
  const unreadIds = notifications.filter((n) => !n.readAt).map((n) => n.id);
  if (unreadIds.length > 0) {
    await db.notification.updateMany({
      where: { id: { in: unreadIds } },
      data: { readAt: new Date() },
    });
  }

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-5 py-8 sm:px-8 sm:py-10">
      <h1 className="font-display text-2xl font-bold text-ink-900 sm:text-3xl">
        Notifications
      </h1>

      {notifications.length === 0 ? (
        <p className="mt-6 bg-enamel-50 px-5 py-12 text-center text-[15px] text-ink-600">
          Aucune notification pour le moment.
        </p>
      ) : (
        <ul className="mt-6 flex flex-col gap-px bg-ink-900/10">
          {notifications.map((notification) => (
            <li key={notification.id} className="bg-enamel-50 p-4 sm:p-5">
              <div className="flex items-start gap-3">
                <span
                  aria-hidden
                  className={`mt-1.5 h-2 w-2 shrink-0 ${
                    unreadIds.includes(notification.id)
                      ? "bg-rod-500"
                      : "bg-enamel-300"
                  }`}
                />
                <div className="min-w-0">
                  <p className="font-display text-[15px] font-bold text-ink-900">
                    {notification.href ? (
                      <Link
                        href={notification.href}
                        className="hover:text-rod-700"
                      >
                        {notification.title}
                      </Link>
                    ) : (
                      notification.title
                    )}
                  </p>
                  <p className="mt-0.5 text-sm leading-snug text-ink-600">
                    {notification.body}
                  </p>
                  <p className="mt-1.5 text-xs tabular-nums text-ink-400">
                    {dateTimeShort.format(notification.createdAt)}
                  </p>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
