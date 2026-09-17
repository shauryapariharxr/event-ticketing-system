import Link from "next/link";
import { getSessionUser } from "@/lib/auth";

export function Header({ session }: { session: { full_name: string; role: string } | null }) {
  return (
    <header className="sticky top-0 z-40 border-b border-line bg-white/90 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
        <Link href="/" className="flex items-center gap-2">
          <span className="grid h-8 w-8 place-items-center rounded-lg bg-ink-blue text-white font-display font-bold">
            S
          </span>
          <span className="font-display text-lg font-semibold">StagePass</span>
        </Link>
        <nav className="flex items-center gap-3 text-sm">
          <Link href="/" className="rounded-lg px-3 py-2 hover:bg-mint-mist">
            Events
          </Link>
          {session && (
            <Link href="/tickets" className="rounded-lg px-3 py-2 hover:bg-mint-mist">
              My Tickets
            </Link>
          )}
          {session?.role === "admin" && (
            <Link href="/admin" className="rounded-lg px-3 py-2 font-medium text-ink-blue hover:bg-mint-mist">
              Admin
            </Link>
          )}
          {session ? (
            <form action="/logout" method="get">
              <button className="btn-ghost" type="submit">
                Sign out ({session.full_name.split(" ")[0]})
              </button>
            </form>
          ) : (
            <Link href="/login" className="btn-primary">
              Sign in
            </Link>
          )}
        </nav>
      </div>
    </header>
  );
}

const STATUS_STYLES: Record<string, string> = {
  scheduled: "bg-mint-mist text-ink-blue",
  cancelled: "bg-danger/10 text-danger",
  completed: "bg-line text-ink-soft",
  active: "bg-success/10 text-success",
  used: "bg-line text-ink-soft",
  pending: "bg-warning/10 text-warning",
};

export function StatusChip({ status }: { status: string }) {
  const cls = STATUS_STYLES[status] ?? "bg-mint-mist text-ink-soft";
  return <span className={`chip ${cls}`}>{status}</span>;
}

export function EmptyState({ title, subtitle, action }: { title: string; subtitle?: string; action?: React.ReactNode }) {
  return (
    <div className="card flex flex-col items-center gap-2 px-8 py-14 text-center">
      <div className="grid h-12 w-12 place-items-center rounded-full bg-mint-mist text-xl">🎟️</div>
      <h3 className="font-display text-lg font-semibold">{title}</h3>
      {subtitle && <p className="max-w-sm text-sm text-ink-soft">{subtitle}</p>}
      {action}
    </div>
  );
}

export function Footer() {
  return (
    <footer className="mt-16 border-t border-line bg-white">
      <div className="mx-auto max-w-6xl px-4 py-8 text-sm text-ink-soft">
        <div className="flex flex-col items-start justify-between gap-2 sm:flex-row sm:items-center">
          <span>
            <strong className="font-display text-ink">StagePass</strong> — Event Ticketing &amp; Seating · DBMS PBL
            (CSL_311)
          </span>
          <span>Local PostgreSQL · Next.js · 10 business rules enforced</span>
        </div>
      </div>
    </footer>
  );
}
