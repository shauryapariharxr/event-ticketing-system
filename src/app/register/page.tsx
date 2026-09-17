"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({ full_name: "", email: "", password: "" });
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    const res = await fetch("/api/v1/auth/register", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form),
    });
    const data = await res.json();
    setBusy(false);
    if (!res.ok) {
      setError(data.error ?? "Registration failed");
      return;
    }
    router.push("/");
    router.refresh();
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-mint-mist px-4 py-10">
      <div className="card w-full max-w-md p-8">
        <h1 className="font-display text-2xl font-bold">Create your account</h1>
        <p className="mt-1 text-sm text-ink-soft">Book seats in seconds.</p>
        {error && (
          <p className="mt-4 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">{error}</p>
        )}
        <form onSubmit={submit} className="mt-6 space-y-4">
          <div>
            <label className="label" htmlFor="name">Full name</label>
            <input id="name" required className="input" value={form.full_name}
                   onChange={(e) => setForm({ ...form, full_name: e.target.value })} />
          </div>
          <div>
            <label className="label" htmlFor="email">Email</label>
            <input id="email" type="email" required className="input" value={form.email}
                   onChange={(e) => setForm({ ...form, email: e.target.value })} />
          </div>
          <div>
            <label className="label" htmlFor="password">Password (min 8 chars)</label>
            <input id="password" type="password" required minLength={8} className="input" value={form.password}
                   onChange={(e) => setForm({ ...form, password: e.target.value })} />
          </div>
          <button className="btn-primary w-full" disabled={busy} type="submit">
            {busy ? "Creating…" : "Create account"}
          </button>
        </form>
        <p className="mt-6 text-center text-sm text-ink-soft">
          Already registered?{" "}
          <Link href="/login" className="font-medium text-ink-blue hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}
