import crypto from "node:crypto";
import bcrypt from "bcryptjs";
import { cookies } from "next/headers";
import { q } from "@/db/pool";

export type Role = "customer" | "organizer" | "admin" | "scanner";
export const COOKIE_NAME = "sp_session";

export interface SessionUser {
  id: number;
  email: string;
  full_name: string;
  role: Role;
}

const b64url = (buf: Buffer | string) =>
  Buffer.from(buf).toString("base64url");
const secret = () =>
  crypto.createSecretKey(Buffer.from(process.env.JWT_SECRET ?? "sp_dev_fallback_secret"));

/** Minimal HS256 JWT (no extra deps; verified only in Node runtime). */
export function createToken(user: SessionUser, days = 7): string {
  const header = b64url(JSON.stringify({ alg: "HS256", typ: "JWT" }));
  const payload = b64url(
    JSON.stringify({
      sub: user.id,
      email: user.email,
      name: user.full_name,
      role: user.role,
      exp: Math.floor(Date.now() / 1000) + days * 86400,
    })
  );
  const sig = crypto
    .createHmac("sha256", secret())
    .update(`${header}.${payload}`)
    .digest("base64url");
  return `${header}.${payload}.${sig}`;
}

export function verifyToken(token: string): SessionUser | null {
  const parts = token.split(".");
  if (parts.length !== 3) return null;
  const [header, payload, sig] = parts;
  const expected = crypto
    .createHmac("sha256", secret())
    .update(`${header}.${payload}`)
    .digest("base64url");
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, "base64url").toString());
    if (!data.exp || data.exp * 1000 < Date.now()) return null;
    return {
      id: data.sub,
      email: data.email,
      full_name: data.name,
      role: data.role,
    };
  } catch {
    return null;
  }
}

export function hashPassword(pw: string): string {
  return bcrypt.hashSync(pw, 10);
}

export function comparePassword(pw: string, hash: string): boolean {
  return bcrypt.compareSync(pw, hash);
}

export async function setSessionCookie(user: SessionUser): Promise<void> {
  const store = await cookies();
  store.set(COOKIE_NAME, createToken(user), {
    httpOnly: true,
    sameSite: "lax",
    path: "/",
    maxAge: 7 * 86400,
  });
}

export async function clearSessionCookie(): Promise<void> {
  const store = await cookies();
  store.delete(COOKIE_NAME);
}

/** Fresh session from DB (role changes take effect immediately). */
export async function getSessionUser(): Promise<SessionUser | null> {
  const store = await cookies();
  const token = store.get(COOKIE_NAME)?.value;
  if (!token) return null;
  const claims = verifyToken(token);
  if (!claims) return null;
  const rows = await q<SessionUser & { role: Role }>(
    "SELECT id, email, full_name, role FROM users WHERE id = $1",
    [claims.id]
  );
  return rows[0] ?? null;
}
