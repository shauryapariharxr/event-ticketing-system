/**
 * Next.js instrumentation hook (runs once per server process boot).
 * Node-only logic lives in instrumentation-node.ts, imported conditionally
 * so the Edge build never bundles pg/node-cron.
 */
export async function register() {
  if (process.env.NEXT_RUNTIME === "nodejs") {
    const { startSweeper } = await import("./sweeper");
    startSweeper();
  }
}
