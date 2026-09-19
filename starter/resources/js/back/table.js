// نقطة دخول Vite القديمة: الاستيرادات الثابتة تضمن نشر واجهات CRUD وDataTables قبل إعلان الجاهزية.
import './crud.js';
import './datatable-init.js';
import { prepareDataTable } from './tables/configuration.js';
import { setUpDatatable, destroyDataTable } from './tables/lifecycle.js';
import { bindTextToggles } from './tables/text.js';
import { editItem, fillForm, createItem, updateItem, deleteItem, updateValue } from './tables/records.js';
import { registerDashboardApi } from './core/dashboard.js';

window.optioanl_filter ??= {};
const tableApi = {
    prepareDataTable, setUpDatatable, destroyDataTable,
    editItem, fillForm, createItem, updateItem, deleteItem, updateValue,
};
registerDashboardApi('tables', tableApi, {
    prepareDataTable: 'prepareDataTable', setUpDatatable: 'setUpDatatable', destroyDataTable: 'destroyDataTable',
    edit_item: 'editItem', fill_form: 'fillForm', create_item: 'createItem',
    update_item: 'updateItem', delete_item: 'deleteItem', update_val: 'updateValue',
});

// يسبق التسجيل المتزامن أعلاه أي DOMContentLoaded أو استبدال HTML عبر AJAX.
window.dispatchEvent(new CustomEvent('dashboard:table-api-ready'));
bindTextToggles();
$(document).ready(() => {
    if (window.auto_start == 1) window.setUpDatatable();
});
