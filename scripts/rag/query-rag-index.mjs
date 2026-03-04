#!/usr/bin/env node

import { readFile } from "node:fs/promises";
import path from "node:path";
import { loadDotEnv } from "./load-dotenv.mjs";

const ROOT = process.cwd();

function getRuntimeConfig() {
  const outputDir = path.join(ROOT, "storage", "gemini_rag");
  return {
    vectorsFile: path.join(outputDir, "vectors.jsonl"),
    embedModel: "models/gemini-embedding-001",
    llmModel: "models/gemini-1.5-flash",
    topK: 5,
    apiBaseUrl: "https://generativelanguage.googleapis.com/v1beta",
  };
}

async function main() {
  loadDotEnv(ROOT);
  const query = process.argv.slice(2).join(" ");
  const apiKey = process.env.GOOGLE_API_KEY;
  const config = getRuntimeConfig();
  const vectors = await (await readFile(config.vectorsFile, "utf8")).split("\n").filter(Boolean).map(JSON.parse);

  // 1. Embed Query
  const embedRes = await fetch(`${config.apiBaseUrl}/${config.embedModel}:embedContent?key=${apiKey}`, {
    method: "POST",
    body: JSON.stringify({ model: config.embedModel, content: { parts: [{ text: query }] }, taskType: "RETRIEVAL_QUERY" })
  });
  const queryVec = (await embedRes.json()).embedding.values;

  // 2. Similarity Search
  const scored = vectors.map(v => {
    let dot = 0, nA = 0, nB = 0;
    for(let i=0; i<queryVec.length; i++) { dot += queryVec[i]*v.embedding[i]; nA += queryVec[i]**2; nB += v.embedding[i]**2; }
    return { ...v, score: dot / (Math.sqrt(nA) * Math.sqrt(nB)) };
  }).sort((a,b) => b.score - a.score).slice(0, config.topK);

  // 3. Generate Answer
  const context = scored.map((s, i) => `Source [${i+1}] (${s.path}):\n${s.text}`).join("\n---\n");
  const prompt = `Answer the question using only the Laravel code below. Cite sources as [1], [2].\n\nQuestion: ${query}\n\nContext:\n${context}`;

  const genRes = await fetch(`${config.apiBaseUrl}/${config.llmModel}:generateContent?key=${apiKey}`, {
    method: "POST",
    body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }] })
  });
  
  const result = await genRes.json();
  console.log("\n--- ANSWER ---\n");
  console.log(result.candidates[0].content.parts[0].text);
}

main().catch(console.error);