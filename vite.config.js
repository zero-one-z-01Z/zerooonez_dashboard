import {defineConfig} from 'vite';
import {readdirSync} from 'node:fs';

const entries = [
  'resources/css/dashboard-builder.css',
  ...readdirSync('resources/js/back').filter(file => file.endsWith('.js')).map(file => `resources/js/back/${file}`),
  ...readdirSync('resources/js/back/pages').filter(file => file.endsWith('.js')).map(file => `resources/js/back/pages/${file}`),
];

export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {input: entries},
  },
});
