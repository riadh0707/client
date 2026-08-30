import path from "node:path";
import { defineConfig } from "prisma/config";

// Prisma 7 moved the connection out of schema.prisma: the CLI (migrate, studio,
// db push) reads it from here. The runtime client builds its own adapter in
// src/lib/db.ts, so no adapter is needed at this layer.
const DATABASE_URL = process.env.DATABASE_URL ?? "file:./prisma/dev.db";

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
