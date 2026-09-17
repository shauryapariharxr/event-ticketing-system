import { z } from "zod";
import { q } from "@/db/pool";
import { ok, route, readJson, requireUser, ApiError } from "@/lib/api";
import { audit } from "@/lib/audit";

export const runtime = "nodejs";

const transferSchema = z.object({ to_email: z.string().email("Valid email required") });

export const POST = route(async (req: Request, ctx: { params: Promise<{ id: string }> }) => {
  const user = await requireUser();
  const { id } = await ctx.params;
  const input = transferSchema.parse(await readJson<unknown>(req));
  const [ticket] = await q<{ id: number; status: string }>(
    `SELECT id, status FROM tickets WHERE id=$1 AND user_id=$2`,
    [Number(id), user.id]
  );  // id from path param is a string; Number() normalizes bigint-string ids
  if (!ticket) throw new ApiError(404, "Ticket not found among your tickets");
  if (ticket.status !== "active") throw new ApiError(409, "Only active tickets can be transferred");

  const [recipient] = await q<{ id: number }>(
    `SELECT id FROM users WHERE email=$1`,
    [input.to_email.toLowerCase()]
  );
  if (!recipient) throw new ApiError(404, "No user found with that email");
  if (recipient.id === user.id) throw new ApiError(400, "Cannot transfer to yourself");

  await q(`UPDATE tickets SET user_id=$2 WHERE id=$1`, [Number(id), recipient.id]);
  await audit(user, "ticket.transferred", "ticket", Number(id), { to: input.to_email });
  return ok({ transferred: true });
});
