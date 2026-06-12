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
