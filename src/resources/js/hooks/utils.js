export const formatTwoDecimal = (value) => {
  const num = parseFloat(value || 0);
  return isNaN(num) ? "0.00" : num.toFixed(2);
};

// ✅ Helper: Detect valid JSON string
function isJsonString(str) {
  if (typeof str !== "string") return false;
  try {
    const parsed = JSON.parse(str);
    return parsed && typeof parsed === "object";
  } catch (err) {
    return false;
  }
}

export function parseErrorMessage(errorInput) {
  if (!errorInput) return "";

  let parsed = null;

  // 🧩 Step 1: Try to detect and parse if it’s JSON or JSON string
  try {
    if (typeof errorInput === "object") {
      // Already an object (actual JSON)
      parsed = errorInput;
    } else if (typeof errorInput === "string" && isJsonString(errorInput)) {
      // It's a valid JSON string
      parsed = JSON.parse(errorInput);
    } else {
      // Plain string — return as-is
      return errorInput;
    }
  } catch (err) {
    // If parsing fails, just return the original string
    return errorInput;
  }

  // 🧩 Step 2: Handle parsed JSON object (Laravel-style validation)
  try {
    const readable = Object.values(parsed)
      .flat()
      .map((msg) => msg.replace(/id\.value\./g, "").trim());

    return readable.join("\n");
  } catch (err) {
    // Just in case JSON format isn’t what we expected
    return typeof errorInput === "string"
      ? errorInput
      : "An unexpected error occurred.";
  }
}

/* ──────────────────────────────────────────────────────────────────────────
 * UK time helpers
 * The app stores/sends times in UTC and DISPLAYS them in UK time
 * (Europe/London — auto GMT/BST). Use these for any date/time you render so
 * parsing AND the displayed zone are both correct (don't rely on the browser's
 * local zone). bootstrap.js also defaults Date#toLocale* to Europe/London as a
 * safety net for any call that slips through.
 * ────────────────────────────────────────────────────────────────────────── */
export const UK_TIMEZONE = "Europe/London";

/**
 * Parse a backend value into a Date. Critically, a bare "Y-m-d H:i:s" (or
 * "Y-m-dTH:i:s") with NO timezone is treated as UTC — that's how the API stores
 * it — instead of as the browser's local time.
 */
export const parseUTC = (value) => {
  if (value === null || value === undefined || value === "") return null;
  if (value instanceof Date) return isNaN(value) ? null : value;
  if (typeof value === "number") return new Date(value);

  const s = String(value).trim();
  // Already carries a zone (…Z or ±hh:mm) → trust it.
  if (/([zZ]|[+-]\d{2}:?\d{2})$/.test(s)) {
    const d = new Date(s.replace(" ", "T"));
    return isNaN(d) ? null : d;
  }
  // "Y-m-d H:i:s" / "Y-m-dTH:i:s" with no zone → it's UTC.
  if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(s)) {
    const d = new Date(s.replace(" ", "T") + "Z");
    return isNaN(d) ? null : d;
  }
  // Date only "Y-m-d" → midnight UTC.
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
    const d = new Date(s + "T00:00:00Z");
    return isNaN(d) ? null : d;
  }
  const d = new Date(s);
  return isNaN(d) ? null : d;
};

/** Date only, e.g. "12 Jun 2026" — UK zone. */
export const formatUKDate = (
  value,
  options = { day: "2-digit", month: "short", year: "numeric" }
) => {
  const d = parseUTC(value);
  return d ? d.toLocaleDateString("en-GB", { timeZone: UK_TIMEZONE, ...options }) : "";
};

/** Date + time, e.g. "12 Jun 2026, 14:30" — UK zone. */
export const formatUKDateTime = (
  value,
  options = {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }
) => {
  const d = parseUTC(value);
  return d ? d.toLocaleString("en-GB", { timeZone: UK_TIMEZONE, ...options }) : "";
};

/** Time only, e.g. "14:30" — UK zone. */
export const formatUKTime = (
  value,
  options = { hour: "2-digit", minute: "2-digit" }
) => {
  const d = parseUTC(value);
  return d ? d.toLocaleTimeString("en-GB", { timeZone: UK_TIMEZONE, ...options }) : "";
};
