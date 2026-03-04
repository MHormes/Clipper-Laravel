#!/usr/bin/env node

import { readFile } from "node:fs/promises";
import path from "node:path";
import { loadDotEnv } from "./load-dotenv.mjs";

const ROOT = process.cwd();

function getRuntimeConfig() {
  const outputDir = process.env.RAG_OUTPUT_DIR ? path.resolve(ROOT, process.env.RAG_OUTPUT_DIR) : path.join(ROOT, "storage", "gemini_rag");
  return {
    vectorsFile: path.join(outputDir, "vectors.jsonl"),
    embedModel: "models/gemini-embedding-001",
    topK: 8,
    apiBaseUrl: "https://generativelanguage.googleapis.com/v1beta",
  };
}

async function readVectors(filePath) {
  const raw = await readFile(filePath, "utf8");
  return raw.split(/\r?\n/).filter(Boolean).map(line => JSON.parse(line));
}

function cosineSimilarity(a, b) {
  let dot = 0, normA = 0, normB = 0;
  for (let i = 0; i < a.length; i++) {
    dot += a[i] * b[i];
    normA += a[i] * a[i];
    normB += b[i] * b[i];
  }
  return dot / (Math.sqrt(normA) * Math.sqrt(normB));
}

async function main() {
  loadDotEnv(ROOT);
  const query = process.argv.slice(2).join(" ");
  const apiKey = process.env.GOOGLE_API_KEY;
  const config = getRuntimeConfig();
  const vectors = await readVectors(config.vectorsFile);

  const res = await fetch(`${config.apiBaseUrl}/${config.embedModel}:embedContent?key=${apiKey}`, {
    method: "POST",
    body: JSON.stringify({ model: config.embedModel, content: { parts: [{ text: query }] }, taskType: "RETRIEVAL_QUERY" })
  });
  const { embedding } = await res.json();

  const scored = vectors.map(v => ({ ...v, score: cosineSimilarity(embedding.values, v.embedding) }))
    .sort((a, b) => b.score - a.score).slice(0, config.topK);

  scored.forEach((r, i) => {
    console.log(`\n## ${i + 1}. ${r.path}:${r.start_line}-${r.end_line} (Score: ${r.score.toFixed(4)})`);
    console.log("```php\n" + r.text + "\n```");
  });
}

main().catch(console.error);