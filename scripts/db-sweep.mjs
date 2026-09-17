// Manual sweep runner: npm run db:sweep
import { readFileSync } from "node:fs";
for (const line of readFileSync(".env", "utf8").split(/\r?\n/)) {
  const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
  if (m && !m[1].startsWith("#") && !process.env[m[1]]) process.env[m[1]] = m[2];
}
const { pool } = await import("../src/db/pool.ts").catch(() => ({ pool: null }));
if (!pool) {
  // Fallback: use pg directly
  const pg = (await import("pg")).default;
  const client = new pg.Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  const h = await client.query(`DELETE FROM seat_holds WHERE expires_at <= now() RETURNING id`);
  const p = await client.query(
    `UPDATE tickets SET status='cancelled'
     WHERE status='pending' AND issued_at < now() - interval '15 minutes' RETURNING id`
  );
  console.log(`Swept ${h.rowCount} holds, ${p.rowCount} stale pendings.`);
  await client.end();
  process.exit(0);
}
