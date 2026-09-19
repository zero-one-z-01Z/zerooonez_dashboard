#!/usr/bin/env node
/** Refresh canonical theme inputs in the already-versioned local starter payload. */
import { cp, mkdir, readdir, readFile, writeFile, access } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const copy = async (from, to) => { await mkdir(dirname(to), { recursive: true }); await cp(from, to, { recursive: true }); };
const from = (...paths) => join(root, ...paths);
const to = (...paths) => join(root, 'starter', ...paths);

await copy(from('resources/views/dashboard'), to('resources/views/dashboard'));
await copy(from('resources/js/back'), to('resources/js/back'));
await copy(from('resources/css/dashboard-builder.css'), to('resources/css/dashboard-builder.css'));
await copy(from('resources/lang'), to('resources/lang'));
await copy(from('public/dashboard'), to('public/dashboard'));
await copy(from('public/build'), to('public/dashboard/build'));
for (const logo of ['logo.png', 'logo.svg', 'small-logo.png', 'white-logo.jpeg']) {
  try { await access(from('public', logo)); await copy(from('public', logo), to('public', logo)); } catch { /* keep captured starter logo when package has none */ }
}
// Package theme sources retain their historical namespace. The installed
// starter is source-local, so refreshes must preserve the local namespace.
async function localizeViews(directory) {
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const file = join(directory, entry.name);
    if (entry.isDirectory()) await localizeViews(file);
    else if (entry.name.endsWith('.blade.php')) await writeFile(file, (await readFile(file, 'utf8')).replaceAll('ZeroOneZ\\Dashboard', 'App\\Dashboard').replaceAll('zerooonez-dashboard::', 'dashboard.'));
  }
}
await localizeViews(to('resources/views/dashboard'));
console.log('Refreshed canonical theme inputs; local PHP starter sources were preserved.');
