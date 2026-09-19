#!/usr/bin/env node
import { createHash } from 'node:crypto';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const starter = join(root, 'starter');
const packageMetadata = JSON.parse(await readFile(join(root, 'package.json'), 'utf8'));
async function walk(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  return (await Promise.all(entries.map(async (entry) => entry.isDirectory() ? walk(join(directory, entry.name)) : [join(directory, entry.name)]))).flat();
}
const manifest = { version: 1, package_version: packageMetadata.version, files: await Promise.all((await walk(starter)).map(async (file) => {
  const source = relative(root, file).replaceAll('\\', '/');
  return { source, target: source.replace(/^starter\//, ''), sha256: createHash('sha256').update(await readFile(file)).digest('hex') };
})) };
manifest.files.sort((a, b) => a.target.localeCompare(b.target));
await writeFile(join(root, 'starter-manifest.json'), JSON.stringify(manifest, null, 2)+'\n');
console.log(`Updated manifest for ${manifest.files.length} files.`);
