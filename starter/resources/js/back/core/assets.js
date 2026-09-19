/** محمل أصول مركزي يدعم التكرار الآمن وإعادة المحاولة بعد فشل الشبكة. */

import { dashboardRuntimeConfig } from './config.js';

function absoluteUrl(url, documentRef) {
    try { return new URL(url, documentRef.baseURI || globalThis.location?.href || 'http://localhost/').href; }
    catch { return url; }
}

export class DashboardAssetLoader {
    constructor(documentRef = globalThis.document) {
        this.document = documentRef;
        this.definitions = new Map();
        this.pending = new Map();
    }

    /** يسجل تعريف أصل باسم ثابت، ويمكن استدعاؤه أكثر من مرة بنفس البيانات. */
    register(name, definition = {}) {
        this.definitions.set(name, {
            styles: [...(definition.styles || [])],
            scripts: [...(definition.scripts || [])],
            test: definition.test,
        });
        return this;
    }

    /** يسجل مجموعة التعريفات القادمة من إعدادات Blade. */
    registerAll(definitions = {}) {
        for (const [name, definition] of Object.entries(definitions)) this.register(name, definition);
        return this;
    }

    isReady(name) {
        const definition = this.definitions.get(name);
        return Boolean(definition && typeof definition.test === 'function' && definition.test());
    }

    /** يحمل الأصول المطلوبة بالترتيب مع مشاركة نفس Promise بين الطلبات المتزامنة. */
    load(name) {
        if (this.isReady(name)) return Promise.resolve(name);
        if (this.pending.has(name)) return this.pending.get(name);
        const definition = this.definitions.get(name);
        if (!definition) return Promise.reject(new Error(`Unknown dashboard asset: ${name}`));

        const promise = this.loadDefinition(definition)
            .then(() => name)
            .catch(error => {
                this.pending.delete(name);
                throw error;
            });
        this.pending.set(name, promise);
        return promise;
    }

    async loadMany(names = []) {
        for (const name of [...new Set(names.filter(Boolean))]) await this.load(name);
        return names;
    }

    async loadDefinition(definition) {
        if (!this.document) throw new Error('Dashboard assets require a document');
        await Promise.all(definition.styles.map(url => this.loadElement('link', url)));
        for (const url of definition.scripts) await this.loadElement('script', url);
        if (typeof definition.test === 'function' && !definition.test()) {
            throw new Error('Dashboard asset loaded but its API is unavailable');
        }
    }

    loadElement(kind, url) {
        const target = absoluteUrl(url, this.document);
        const selector = kind === 'script' ? 'script[src]' : 'link[rel="stylesheet"][href]';
        const attribute = kind === 'script' ? 'src' : 'href';
        const existing = [...this.document.querySelectorAll(selector)]
            .find(element => absoluteUrl(element.getAttribute(attribute), this.document) === target);
        if (existing?.dataset.dashboardLoaded === 'true' || (existing && !existing.dataset.dashboardLoading)) {
            return Promise.resolve(existing);
        }

        const element = existing || this.document.createElement(kind);
        if (!existing) {
            if (kind === 'script') {
                element.src = url;
                element.async = false;
            } else {
                element.rel = 'stylesheet';
                element.href = url;
            }
            element.dataset.dashboardLoading = 'true';
            this.document.head.appendChild(element);
        }

        return new Promise((resolve, reject) => {
            const complete = () => {
                element.dataset.dashboardLoaded = 'true';
                delete element.dataset.dashboardLoading;
                resolve(element);
            };
            const fail = () => {
                delete element.dataset.dashboardLoading;
                if (element.dataset.dashboardAsset === 'dynamic') element.remove();
                reject(new Error(`Failed to load dashboard asset: ${url}`));
            };
            element.dataset.dashboardAsset = element.dataset.dashboardAsset || 'dynamic';
            element.addEventListener('load', complete, { once: true });
            element.addEventListener('error', fail, { once: true });
        });
    }
}

function apiTest(path) {
    return () => path.split('.').reduce((value, key) => value?.[key], globalThis) != null;
}

export const assetLoader = new DashboardAssetLoader();

/** يسجل الأصول المعرفة في layout ويضيف اختبارات APIs المعروفة. */
export function configureAssets(config = dashboardRuntimeConfig()) {
    const definitions = config.assets || {};
    const tests = {
        select2: () => Boolean(globalThis.jQuery?.fn?.select2),
        datatable: () => Boolean(globalThis.jQuery?.fn?.DataTable),
        dropzone: apiTest('Dropzone'),
        quill: apiTest('Quill'),
        googleMaps: apiTest('google.maps.Map'),
        leaflet: apiTest('L.map'),
        swiper: apiTest('Swiper'),
        daterangepicker: () => Boolean(globalThis.jQuery?.fn?.daterangepicker),
    };
    for (const [name, definition] of Object.entries(definitions)) {
        assetLoader.register(name, { ...definition, test: tests[name] });
    }
    return assetLoader;
}
