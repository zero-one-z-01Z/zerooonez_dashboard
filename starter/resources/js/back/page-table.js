/** نقطة دخول شاشة الجدول العادية: تعريف JSON من Blade ثم إعداد الحالة وإنشاء DataTables. */

import './table.js';
import { Dashboard } from './core/dashboard.js';
import { mountContent } from './core/lifecycle.js';

/**
 * يقرأ تعريف Blade بعد جاهزية DOM؛ الاستيراد الثابت يضمن وجود كل واجهات الجدول قبل الاستخدام.
 * @returns {void} ينشر تعريف dashboard-table-definition ثم ينشئ الجدول، أو يتوقف إن غاب التعريف.
 */
async function startPageTable() {
    const element = document.querySelector('#dashboard-table-definition');
    if (!element) return;
    const definition = JSON.parse(element.textContent);
    try {
        if (definition.assets?.length) await Dashboard.assets.loadMany(definition.assets);
        window.prepareDataTable(definition);
        window.setUpDatatable();
        mountContent(document.getElementById('dashboard-item-table-region') || document.body, { definition });
    } catch (error) {
        console.error('Failed to start dashboard table:', error);
        window.showNotification?.('error', error.message);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startPageTable, { once: true });
else startPageTable();
