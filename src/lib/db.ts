import { PrismaPg } from "@prisma/adapter-pg";
import { PrismaClient } from "@/generated/prisma";

// Next dev remounts modules on every hot reload. Without the global cache each
// reload opens another connection pool until the database refuses new ones.
const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
};

function createClient() {
  const connectionString = process.env.DATABASE_URL;
  if (!connectionString) {
    // Failing here, loudly, beats falling back to a local default: on a hosted
    // deployment a silent fallback means the app boots and then reports an empty
    // database, which reads as data loss rather than as a missing variable.
    throw new Error(
      "DATABASE_URL n'est pas défini. Renseignez la chaîne de connexion PostgreSQL.",
    );
  }

  // A small pool: serverless platforms run many short-lived instances, and each
  // one holding a wide pool exhausts the database's connection limit long before
  // the traffic justifies it. Neon's pooled endpoint (-pooler) handles the rest.
  const adapter = new PrismaPg({ connectionString, max: 5 });
  return new PrismaClient({ adapter });
}

export const db = globalForPrisma.prisma ?? createClient();

if (process.env.NODE_ENV !== "production") {
  globalForPrisma.prisma = db;
}
