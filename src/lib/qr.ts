import crypto from "node:crypto";
import QRCode from "qrcode";

/** Opaque single-use ticket QR payload (Rule 6). */
export function generateQrCode(): string {
  return `sp_${crypto.randomBytes(16).toString("hex")}`;
}

export function generateTicketNumber(): string {
  const stamp = Date.now().toString(36).toUpperCase();
  const rand = crypto.randomBytes(3).toString("hex").toUpperCase();
  return `TCK-${stamp}-${rand}`;
}

export async function qrDataUrl(text: string): Promise<string> {
  return QRCode.toDataURL(text, {
    width: 240,
    margin: 1,
    color: { dark: "#1F2A33", light: "#FFFFFF" },
  });
}
