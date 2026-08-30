import path from "node:path";
import { defineConfig } from "prisma/config";

// Prisma 7 moved the connection out of schema.prisma: the CLI (migrate, studio,
// db push) reads it from here. The runtime client builds its own adapter in
// src/lib/db.ts, so no adapter is needed at this layer.
// Deliberately not throwing when it is absent: `prisma generate` runs from
// postinstall and needs no database, so failing here would break `npm install`
// on a fresh clone. The commands that do need a connection — migrate, seed,
// studio — fail on their own with a clear message, and the runtime client in
// src/lib/db.ts refuses to start without it.
const DATABASE_URL = process.env.DATABASE_URL ?? "";

export default defineConfig({
  schema: path.join("prisma", "schema.prisma"),
  migrations: {
    path: path.join("prisma", "migrations"),
    seed: "npx tsx prisma/seed.ts",
  },
  // The Prisma CLI reads the connection from here.
  datasource: {
    url: DATABASE_URL,
  },
});
