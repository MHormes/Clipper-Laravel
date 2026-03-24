import { readFileSync } from "node:fs";
import path from "node:path";

/**
 * Removes surrounding single or double quotes from a string value.
 * Commonly used for env variables like APP_NAME="Clipper Laravel"
 */
function stripWrappingQuotes(value) {
  if (
    (value.startsWith("\"") && value.endsWith("\"")) ||
    (value.startsWith("'") && value.endsWith("'"))
  ) {
    return value.slice(1, -1);
  }
  return value;
}

/**
 * Manually parses the .env file in the project root and populates process.env.
 * This ensures the RAG scripts have access to GOOGLE_API_KEY and other configs
 * without requiring the full Laravel bootstrap or heavy dependencies.
 */
export function loadDotEnv(cwd = process.cwd()) {
  const envPath = path.join(cwd, ".env");
  let raw;

  try {
    raw = readFileSync(envPath, "utf8");
  } catch (err) {
    // If .env is missing, we log a warning but don't crash, 
    // as variables might be set via the system shell.
    console.warn("⚠️  No .env file found at " + envPath);
    console.error(err);
    return;
  }

  const lines = raw.split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    
    // Skip empty lines and comments
    if (!trimmed || trimmed.startsWith("#")) continue;

    const equalsIndex = trimmed.indexOf("=");
    if (equalsIndex === -1) continue;

    const key = trimmed.slice(0, equalsIndex).trim();
    
    // Basic validation for environment variable keys
    if (!/^[A-Za-z_][A-Za-z0-9_]*$/.test(key)) continue;
    
    // Do not overwrite existing process.env variables (shell precedence)
    if (Object.prototype.hasOwnProperty.call(process.env, key)) continue;

    const valuePart = trimmed.slice(equalsIndex + 1).trim();
    process.env[key] = stripWrappingQuotes(valuePart);
  }
}