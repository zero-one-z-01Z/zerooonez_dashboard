// نقطة دخول صفحات التفاصيل؛ الوحدات المشتركة تسجل CRUD قبل بدء تحميل التبويبات.
import './table.js';
import { generateTableHeaders } from './item-tables/headers.js';
import { loadTableData, startItemTables } from './item-tables/navigation.js';
import { registerDashboardApi } from './core/dashboard.js';

registerDashboardApi('itemTables', { loadTableData, generateTableHeaders }, {
    loadTableData: 'loadTableData', generateTableHeaders: 'generateTableHeaders',
});
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startItemTables, { once: true });
else startItemTables();
