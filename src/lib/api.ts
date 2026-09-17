import { NextResponse } from "next/server";
import { ZodError } from "zod";
import { getSessionUser, type Role, type SessionUser } from "@/lib/auth";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public code?: string
  ) {
    super(message);
  }
}

export function ok(data: unknown, status = 200) {
  return NextResponse.json(data as object, { status });
}

export function fail(message: string, status = 400, code?: string) {
  return NextResponse.json({ error: message, code }, { status });
}

/** Wraps a route handler: converts ApiError/ZodError to clean JSON errors. */
export function route<A extends unknown[]>(
  fn: (...args: A) => Promise<NextResponse>
): (...args: A) => Promise<NextResponse> {
  return async (...args: A) => {
    try {
      return await fn(...args);
    } catch (e) {
      if (e instanceof ApiError) return fail(e.message, e.status, e.code);
      if (e instanceof ZodError) {
        return fail(e.issues[0]?.message ?? "Invalid input", 422, "VALIDATION");
      }
      console.error("[api]", e);
      return fail("Internal server error", 500);
    }
  };
}

export async function requireUser(): Promise<SessionUser> {
  const user = await getSessionUser();
  if (!user) throw new ApiError(401, "Sign in required", "UNAUTHENTICATED");
  return user;
}

export async function requireRole(...roles: Role[]): Promise<SessionUser> {
  const user = await requireUser();
  if (!roles.includes(user.role)) {
    throw new ApiError(403, "Insufficient permissions", "FORBIDDEN");
  }
  return user;
}

export async function readJson<T>(req: Request): Promise<T> {
  try {
    return (await req.json()) as T;
  } catch {
    throw new ApiError(400, "Invalid JSON body");
  }
}
