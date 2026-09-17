import { z } from "zod";
import { q } from "@/db/pool";
import { ok, route, readJson, ApiError } from "@/lib/api";
import { comparePassword, setSessionCookie, type Role } from "@/lib/auth";

export const runtime = "nodejs";

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

export const POST = route(async (req: Request) => {
  const input = loginSchema.parse(await readJson<unknown>(req));
  const rows = await q<{ id: number; email: string; full_name: string; role: Role; password_hash: string }>(
    `SELECT id, email, full_name, role, password_hash FROM users WHERE email=$1`,
    [input.email.toLowerCase()]
  );
  const user = rows[0];
  if (!user || !comparePassword(input.password, user.password_hash)) {
    throw new ApiError(401, "Invalid email or password");
  }
  await setSessionCookie({ id: user.id, email: user.email, full_name: user.full_name, role: user.role });
  return ok({ user: { id: user.id, email: user.email, full_name: user.full_name, role: user.role } });
});
