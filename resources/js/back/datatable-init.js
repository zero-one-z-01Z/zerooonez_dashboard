// نقطة دخول Vite القديمة؛ كل الواجهات العامة تسجل فورا قبل مستمعي جاهزية الصفحة.
import { initializeDataTable } from './datatables/engine.js';
import { replaceUrlPlaceholder, replaceUrlPlaceholderV2 } from './datatables/urls.js';
import { showAddModal, showImportModal } from './datatables/modals.js';
import { closeFilterModal, clickFilterBadge, clearFilters, initializeDateRange } from './datatables/filters.js';
import { deleteButton, bindRowSelection } from './datatables/deletion.js';
import { initMap } from './datatables/maps.js';
import { registerDashboardApi } from './core/dashboard.js';

const dataTableApi = {
    initializeDataTable, replaceUrlPlaceholder, replaceUrlPlaceholderV2,
    showAddModal, showImportModal, closeFilterModal, deleteButton, clickFilterBadge, clearFilters, initMap,
};
registerDashboardApi('datatables', dataTableApi, {
    initializeDataTable: 'initializeDataTable', replaceUrlPlaceholder: 'replaceUrlPlaceholder',
    replaceUrlPlaceholder_v2: 'replaceUrlPlaceholderV2', show_add_modal: 'showAddModal',
    show_import_modal: 'showImportModal', closeFilterModal: 'closeFilterModal', deleteButton: 'deleteButton',
    clickFilterBadge: 'clickFilterBadge', clearFilters: 'clearFilters', initMap: 'initMap',
});

$(document).ready(() => {
    initializeDateRange();
    bindRowSelection();
});
