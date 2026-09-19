import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
  build: {
    manifest: true,
    outDir: 'public/dashboard/build',
    emptyOutDir: true,
    rollupOptions: { input: {
      dashboard: resolve('resources/js/back/core/dashboard.js'), builder: resolve('resources/js/back/builder.js'),
      home: resolve('resources/js/back/home.js'), login: resolve('resources/js/back/pages/login.js'),
      settings: resolve('resources/js/back/pages/settings.js'), table: resolve('resources/js/back/table.js'),
      'dashboard-builder': resolve('resources/css/dashboard-builder.css'),
    } },
  },
});
