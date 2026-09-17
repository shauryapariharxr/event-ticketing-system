import type { Pool as PgPool, QueryResultRow } from "pg";
import pg from "pg";

const globalForPool = globalThis as unknown as { __stagepassPool?: PgPool };

export const pool: PgPool =
  globalForPool.__stagepassPool ??
  new pg.Pool({
    connectionString: process.env.DATABASE_URL,
    max: 10,
  });

if (!globalForPool.__stagepassPool) globalForPool.__stagepassPool = pool;

/** Parameterized query helper. */
export async function q<T extends QueryResultRow = QueryResultRow>(
  text: string,
  params: unknown[] = []
): Promise<T[]> {
  const res = await pool.query(text, params);
  return res.rows as T[];
}

/** Run a set of operations inside a single transaction. */
export async function withTransaction<T>(
  fn: (tx: TxClient) => Promise<T>
): Promise<T> {
  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    const result = await fn({
      query: async <U extends QueryResultRow = QueryResultRow>(
        text: string,
        params: unknown[] = []
      ) => (await client.query(text, params)).rows as U[],
    });
    await client.query("COMMIT");
    return result;
  } catch (e) {
    await client.query("ROLLBACK");
    throw e;
  } finally {
    client.release();
  }
}

export interface TxClient {
  query<U extends QueryResultRow = QueryResultRow>(
    text: string,
    params?: unknown[]
  ): Promise<U[]>;
}
