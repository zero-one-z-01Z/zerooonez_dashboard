/** الواجهة العامة الوحيدة للداشبورد مع طبقة توافق للأسماء القديمة. */

import { assetLoader, configureAssets } from './assets.js';
import { mountContent, registerCleanup, remountContent, unmountContent } from './lifecycle.js';
import { dashboardRuntimeConfig, readJsonConfig } from './config.js';

const Dashboard = globalThis.window?.Dashboard || {};

Object.assign(Dashboard, {
    assets: assetLoader,
    config: dashboardRuntimeConfig(),
    lifecycle: { mount: mountContent, unmount: unmountContent, remount: remountContent, cleanup: registerCleanup },
    readJsonConfig,
});

if (globalThis.window) globalThis.window.Dashboard = Dashboard;
configureAssets(Dashboard.config);

/** يسجل API داخل namespace ويضيف aliases قديمة عند الحاجة. */
export function registerDashboardApi(group, api, aliases = {}) {
    Dashboard[group] = Object.assign(Dashboard[group] || {}, api);
    if (globalThis.window) {
        for (const [legacyName, functionName] of Object.entries(aliases)) {
            globalThis.window[legacyName] = api[functionName];
        }
    }
    return Dashboard[group];
}

export { Dashboard };

