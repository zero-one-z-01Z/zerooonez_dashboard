import {createHash} from 'node:crypto';
import {readdir, readFile, writeFile} from 'node:fs/promises';
import {join, relative} from 'node:path';

const root = new URL('../public/', import.meta.url);
async function walk(directory) {
  const entries = await readdir(directory, {withFileTypes: true});
  const files = [];
  for (const entry of entries) {
    const path = join(directory, entry.name);
    if (entry.isDirectory()) files.push(...await walk(path));
    else if (entry.isFile()) files.push(path);
  }
  return files;
}
const sourceRoot = decodeURIComponent(root.pathname);
const files = {};
for (const file of (await walk(sourceRoot)).sort()) {
  const key = relative(sourceRoot, file).replaceAll('\\', '/');
  files[key] = createHash('sha256').update(await readFile(file)).digest('hex');
}
await writeFile(new URL('../assets-manifest.json', import.meta.url), JSON.stringify({algorithm: 'sha256', files}, null, 2) + '\n');
