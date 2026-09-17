// Applies SQL migrations from src/db/migrations in filename order, tracked in schema_migrations.
import { readFileSync, readdirSync } from "node:fs";
import { join } from "node:path";
import pg from "pg";

function loadEnv() {
  try {
    for (const line of readFileSync(".env", "utf8").split(/\r?\n/)) {
      const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
      if (m && !m[1].startsWith("#") && !process.env[m[1]]) process.env[m[1]] = m[2];
    }
  } catch {
    /* no .env — rely on real env vars */
  }
}
loadEnv();

const pool = new pg.Pool({ connectionString: process.env.DATABASE_URL });

async function main() {
  await pool.query(
    `CREATE TABLE IF NOT EXISTS schema_migrations (
       name TEXT PRIMARY KEY,
       applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
     )`
  );
  const dir = join(process.cwd(), "src", "db", "migrations");
  const files = readdirSync(dir).filter((f) => f.endsWith(".sql")).sort();
  for (const file of files) {
    const { rowCount } = await pool.query("SELECT 1 FROM schema_migrations WHERE name = $1", [file]);
    if (rowCount) {
      console.log(`= ${file} (already applied)`);
      continue;
    }
    const sql = readFileSync(join(dir, file), "utf8");
    const client = await pool.connect();
    try {
      await client.query("BEGIN");
      await client.query(sql);
      await client.query("INSERT INTO schema_migrations(name) VALUES ($1)", [file]);
      await client.query("COMMIT");
      console.log(`✓ ${file}`);
    } catch (e) {
      await client.query("ROLLBACK");
      throw new Error(`Migration ${file} failed: ${e.message}`);
    } finally {
      client.release();
    }
  }
  console.log("Migrations complete.");
}

main()
  .catch((e) => {
    console.error(e.message);
    process.exitCode = 1;
  })
  .finally(() => pool.end());
