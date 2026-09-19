import { createHash } from 'node:crypto';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const starter = join(root, 'starter');
const packageMetadata = JSON.parse(await readFile(join(root, 'package.json'), 'utf8'));
const files = [];
async function walk(directory) {
  for (const item of await readdir(directory, { withFileTypes: true })) {
    if (item.name === '.DS_Store') continue;
    const path = join(directory, item.name);
    if (item.isDirectory()) await walk(path);
    else if (item.isFile()) {
      const source = relative(root, path).replaceAll('\\', '/');
      const target = relative(starter, path).replaceAll('\\', '/');
      files.push({ source, target, sha256: createHash('sha256').update(await readFile(path)).digest('hex') });
    }
  }
}
await walk(starter);
files.sort((a, b) => a.target.localeCompare(b.target));
await writeFile(join(root, 'starter-manifest.json'), `${JSON.stringify({ version: 1, package_version: packageMetadata.version, files }, null, 2)}\n`);
