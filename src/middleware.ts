import { NextResponse, type NextRequest } from "next/server";
import { COOKIE_NAME, verifyToken } from "@/lib/auth-edge";

/**
 * Edge middleware: gates /admin (admin|organizer) and scanner portal (scanner|admin|organizer).
 * API routes do their own deeper role checks via requireRole().
 */
export async function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl;
  const token = req.cookies.get(COOKIE_NAME)?.value;
  const session = token ? await verifyToken(token) : null;

  const isAdminArea = pathname.startsWith("/admin");
  const isScannerArea = pathname.startsWith("/scan");

  if ((isAdminArea || isScannerArea) && !session) {
    const url = req.nextUrl.clone();
    url.pathname = "/login";
    url.searchParams.set("next", pathname);
    return NextResponse.redirect(url);
  }
  if (isAdminArea && session && !["admin", "organizer"].includes(session.role)) {
    const url = req.nextUrl.clone();
    url.pathname = "/";
    url.searchParams.set("denied", "admin");
    return NextResponse.redirect(url);
  }
  if (isScannerArea && session && !["scanner", "admin", "organizer"].includes(session.role)) {
    const url = req.nextUrl.clone();
    url.pathname = "/";
    url.searchParams.set("denied", "scanner");
    return NextResponse.redirect(url);
  }
  return NextResponse.next();
}

export const config = {
  matcher: ["/admin/:path*", "/scan/:path*"],
};
