export type Role = "customer" | "organizer" | "admin" | "scanner";
export const COOKIE_NAME = "sp_session";

export interface EdgeSession {
  id: number;
  email: string;
  full_name: string;
  role: Role;
}

const enc = new TextEncoder();

function b64urlToBytes(s: string): Uint8Array<ArrayBuffer> {
  const b64 = s.replace(/-/g, "+").replace(/_/g, "/");
  const pad = b64.length % 4 === 0 ? "" : "=".repeat(4 - (b64.length % 4));
  const bin = atob(b64 + pad);
  const bytes = new Uint8Array(new ArrayBuffer(bin.length));
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return bytes;
}

async function hmacKey(): Promise<CryptoKey> {
  const raw = enc.encode(process.env.JWT_SECRET ?? "sp_dev_fallback_secret");
  return crypto.subtle.importKey("raw", raw, { name: "HMAC", hash: "SHA-256" }, false, ["verify"]);
}

/** HS256 verify via Web Crypto — works in the Edge runtime (middleware). */
export async function verifyToken(token: string): Promise<EdgeSession | null> {
  const parts = token.split(".");
  if (parts.length !== 3) return null;
  const [header, payload, sig] = parts;
  try {
    const valid = await crypto.subtle.verify(
      "HMAC",
      await hmacKey(),
      b64urlToBytes(sig),
      enc.encode(`${header}.${payload}`)
    );
    if (!valid) return null;
    const data = JSON.parse(new TextDecoder().decode(b64urlToBytes(payload))) as {
      sub: number;
      email: string;
      name: string;
      role: Role;
      exp: number;
    };
    if (!data.exp || data.exp * 1000 < Date.now()) return null;
    return { id: data.sub, email: data.email, full_name: data.name, role: data.role };
  } catch {
    return null;
  }
}
