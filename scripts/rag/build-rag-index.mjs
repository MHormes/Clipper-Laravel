#!/usr/bin/env node

import { createHash } from "node:crypto";
import { appendFile, mkdir, readdir, readFile, stat, writeFile } from "node:fs/promises";
import path from "node:path";
import { loadDotEnv } from "./load-dotenv.mjs";

const ROOT = process.cwd();
// Use Laravel's storage folder for RAG data
const DEFAULT_OUTPUT_DIR = path.join(ROOT, "storage", "gemini_rag");
const DEFAULT_INCLUDE_DIRS = ["app", "config", "database", "resources", "routes", "tests"];
const DEFAULT_INCLUDE_FILES = [
  "composer.json",
  "package.json",
  "artisan",
  "phpunit.xml",
  "vite.config.js",
  "tailwind.config.js",
  "README.md"
];
const DEFAULT_EXTENSIONS = [
  ".php",
  ".vue",
  ".js",
  ".ts",
  ".json",
  ".md",
  ".css",
  ".sql",
];
const DEFAULT_IGNORE_DIRS = [
  ".git",
  ".idea",
  ".vscode",
  "node_modules",
  "vendor",
  "storage",
  "public/build",
  "bootstrap/cache",
];

function parseCsv(value, fallback) {
  if (!value) return fallback;
  const parts = value.split(",").map((p) => p.trim()).filter(Boolean);
  return parts.length > 0 ? parts : fallback;
}

function parseIntWithDefault(value, fallback) {
  const parsed = Number.parseInt(value ?? "", 10);
  return Number.isNaN(parsed) ? fallback : parsed;
}

function normalizeModelName(model) {
  return model.startsWith("models/") ? model : `models/${model}`;
}

function getRuntimeConfig() {
  const outputDir = process.env.RAG_OUTPUT_DIR
    ? path.resolve(ROOT, process.env.RAG_OUTPUT_DIR)
    : DEFAULT_OUTPUT_DIR;

  return {
    outputDir,
    jsonlOutputFile: path.join(outputDir, "index.jsonl"),
    vectorsOutputFile: path.join(outputDir, "vectors.jsonl"),
    manifestFile: path.join(outputDir, "manifest.json"),
    includeDirs: parseCsv(process.env.RAG_INCLUDE_DIRS, DEFAULT_INCLUDE_DIRS),
    includeFiles: parseCsv(process.env.RAG_INCLUDE_FILES, DEFAULT_INCLUDE_FILES),
    allowedExtensions: new Set(parseCsv(process.env.RAG_ALLOWED_EXTENSIONS, DEFAULT_EXTENSIONS)),
    ignoreDirs: new Set(parseCsv(process.env.RAG_IGNORE_DIRS, DEFAULT_IGNORE_DIRS)),
    chunkSize: parseIntWithDefault(process.env.RAG_CHUNK_SIZE, 1400),
    chunkOverlap: parseIntWithDefault(process.env.RAG_CHUNK_OVERLAP, 200),
    embedModel: normalizeModelName(process.env.RAG_EMBED_MODEL ?? "gemini-embedding-001"),
    embedDimension: parseIntWithDefault(process.env.RAG_EMBED_DIMENSION, 3072), // Fixed for 001
    batchSize: parseIntWithDefault(process.env.RAG_EMBED_BATCH_SIZE, 5),
    maxRetries: parseIntWithDefault(process.env.RAG_EMBED_MAX_RETRIES, 8),
    initialBackoffMs: parseIntWithDefault(process.env.RAG_EMBED_INITIAL_BACKOFF_MS, 5000),
    backoffMultiplier: Number.parseFloat(process.env.RAG_EMBED_BACKOFF_MULTIPLIER ?? "1.7"),
    requestDelayMs: parseIntWithDefault(process.env.RAG_EMBED_REQUEST_DELAY_MS, 2000),
    resume: (process.env.RAG_RESUME ?? "true").toLowerCase() !== "false",
    apiBaseUrl: process.env.RAG_GEMINI_API_BASE_URL ?? "https://generativelanguage.googleapis.com/v1beta",
  };
}

function sleep(ms) { return new Promise((res) => setTimeout(res, ms)); }
function normalizeText(input) { return input.replace(/\r\n/g, "\n"); }
function sha1(value) { return createHash("sha1").update(value).digest("hex"); }
function estimateTokens(value) { return Math.ceil(value.length / 4); }
function languageForFile(filePath) { return path.extname(filePath).slice(1) || "php"; }

function charIndexToLine(content, charIndex) {
  let line = 1;
  for (let i = 0; i < charIndex && i < content.length; i++) {
    if (content.charCodeAt(i) === 10) line++;
  }
  return line;
}

function chunkText(content, chunkSize, chunkOverlap) {
  if (content.length <= chunkSize) return [{ start: 0, end: content.length, text: content }];
  const chunks = [];
  let start = 0;
  while (start < content.length) {
    let end = Math.min(start + chunkSize, content.length);
    if (end < content.length) {
      const newlineCut = content.lastIndexOf("\n", end);
      if (newlineCut > start + Math.floor(chunkSize * 0.5)) end = newlineCut + 1;
    }
    chunks.push({ start, end, text: content.slice(start, end) });
    if (end >= content.length) break;
    start = Math.max(0, end - chunkOverlap);
  }
  return chunks;
}

async function walkFiles(startPath, allowedExtensions, ignoreDirs, out = []) {
  const entries = await readdir(startPath, { withFileTypes: true });
  for (const entry of entries) {
    if (ignoreDirs.has(entry.name)) continue;
    const fullPath = path.join(startPath, entry.name);
    if (entry.isDirectory()) { await walkFiles(fullPath, allowedExtensions, ignoreDirs, out); continue; }
    if (allowedExtensions.has(path.extname(entry.name).toLowerCase())) out.push(fullPath);
  }
  return out;
}

async function collectFiles(config) {
  const files = [];
  for (const dir of config.includeDirs) {
    const fullPath = path.join(ROOT, dir);
    try { if ((await stat(fullPath)).isDirectory()) files.push(...(await walkFiles(fullPath, config.allowedExtensions, config.ignoreDirs))); } catch {}
  }
  for (const file of config.includeFiles) {
    const fullPath = path.join(ROOT, file);
    try { if ((await stat(fullPath)).isFile()) files.push(fullPath); } catch {}
  }
  return [...new Set(files)].sort();
}

async function embedBatch(texts, config, apiKey) {
  const endpoint = `${config.apiBaseUrl}/${config.embedModel}:batchEmbedContents`;
  const body = {
    requests: texts.map((text) => ({
      model: config.embedModel,
      taskType: "RETRIEVAL_DOCUMENT",
      content: { parts: [{ text }] },
    })),
  };
  const response = await fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json", "x-goog-api-key": apiKey },
    body: JSON.stringify(body),
  });
  if (!response.ok) throw new Error(`Gemini Error (${response.status}): ${await response.text()}`);
  const json = await response.json();
  return json.embeddings.map((e) => e.values);
}

async function readExistingVectors(filePath) {
  try {
    const raw = await readFile(filePath, "utf8");
    const vectors = [];
    const seenIds = new Set();
    raw.split(/\r?\n/).filter(Boolean).forEach((line) => {
      const parsed = JSON.parse(line);
      if (parsed?.id) { vectors.push(parsed); seenIds.add(parsed.id); }
    });
    return { vectors, seenIds };
  } catch { return { vectors: [], seenIds: new Set() }; }
}

async function embedBatchWithRetry(texts, config, apiKey) {
  let attempt = 0;
  let backoff = config.initialBackoffMs;
  while (attempt <= config.maxRetries) {
    try { return await embedBatch(texts, config, apiKey); } catch (e) {
      if (!e.message.includes("429") || attempt >= config.maxRetries) throw e;
      attempt++;
      console.log(`Rate limited. Retry ${attempt} in ${backoff}ms...`);
      await sleep(backoff);
      backoff *= config.backoffMultiplier;
    }
  }
}

async function main() {
  loadDotEnv(ROOT);
  const config = getRuntimeConfig();
  const apiKey = process.env.GOOGLE_API_KEY || process.env.GEMINI_API_KEY;
  if (!apiKey) throw new Error("API Key required.");

  const files = await collectFiles(config);
  await mkdir(config.outputDir, { recursive: true });

  const jsonlRecords = [];
  let totalChars = 0;

  for (const absPath of files) {
    const relPath = path.relative(ROOT, absPath).replaceAll(path.sep, "/");
    const content = normalizeText(await readFile(absPath, "utf8"));
    if (!content.trim()) continue;
    totalChars += content.length;

    chunkText(content, config.chunkSize, config.chunkOverlap).forEach((chunk, i) => {
      jsonlRecords.push({
        id: `${sha1(relPath).slice(0, 12)}:${i}`,
        path: relPath,
        text: chunk.text,
        start_line: charIndexToLine(content, chunk.start),
        end_line: charIndexToLine(content, Math.max(chunk.start, chunk.end - 1)),
        file_hash: sha1(content),
      });
    });
  }

  await writeFile(config.jsonlOutputFile, jsonlRecords.map(r => JSON.stringify(r)).join("\n") + "\n");
  
  const existing = config.resume ? await readExistingVectors(config.vectorsOutputFile) : { vectors: [], seenIds: new Set() };
  if (!config.resume) await writeFile(config.vectorsOutputFile, "");

  const pending = jsonlRecords.filter(r => !existing.seenIds.has(r.id));

  for (let i = 0; i < pending.length; i += config.batchSize) {
    const batch = pending.slice(i, i + config.batchSize);
    const embeddings = await embedBatchWithRetry(batch.map(r => r.text), config, apiKey);
    const vectorBatch = batch.map((r, j) => JSON.stringify({ ...r, embedding: embeddings[j] })).join("\n");
    await appendFile(config.vectorsOutputFile, vectorBatch + "\n");
    console.log(`Indexed ${existing.vectors.length + i + batch.length}/${jsonlRecords.length} chunks...`);
    await sleep(config.requestDelayMs);
  }

  const manifest = {
    generated_at: new Date().toISOString(),
    files_indexed: files.length,
    total_characters: totalChars,
    embedding_model: config.embedModel
  };
  await writeFile(config.manifestFile, JSON.stringify(manifest, null, 2));
  console.log("🚀 Laravel RAG Index Complete.");
}

main().catch(console.error);