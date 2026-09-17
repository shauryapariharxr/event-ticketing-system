import type { SessionUser } from "@/lib/auth";
import { q } from "@/db/pool";

/** Append-only audit writer (Rule 10). Call after successful business mutations. */
export async function audit(
  actor: SessionUser | null,
  action: string,
  entityType: string,
  entityId: number | null,
  newData?: unknown,
  oldData?: unknown
): Promise<void> {
  await q(
    `INSERT INTO audit_logs(actor_id, action, entity_type, entity_id, new_data, old_data)
     VALUES ($1, $2, $3, $4, $5::jsonb, $6::jsonb)`,
    [
      actor?.id ?? null,
      action,
      entityType,
      entityId,
      newData === undefined ? null : JSON.stringify(newData),
      oldData === undefined ? null : JSON.stringify(oldData),
    ]
  );
}
