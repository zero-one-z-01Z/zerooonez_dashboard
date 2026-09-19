/** فتح نوافذ الإضافة والاستيراد وتجهيز Dropzone والخرائط والحقول المتكررة المتاحة. */

import { initializeBoundaryMap, initMap } from './maps.js';
import { createDropzone } from '../forms/dropzones.js';

/**
 * يعيد إنشاء Dropzone الإضافة مع إعدادات الحقل الحالية ويزيل النسخة السابقة لنفس العنصر.
 * @returns {void} يحدث window.myDropzone ويصفر الملفات المنتظرة؛ لا يرفع الملفات تلقائيا.
 */
function initializeCreateDropzone() {
    const fields = [...(document.getElementById('add_form')?.querySelectorAll('[data-upload-field]') ?? [])];
    const legacy = document.getElementById('add_formdropzone-multi');
    if (legacy && !fields.includes(legacy)) fields.push(legacy);
    if (!window.dropzone || !fields.length) { window.myDropzone = null; return; }
    fields.forEach(element => createDropzone(element)?.removeAllFiles());
}

/**
 * يفتح نافذة مطلوبة ثم يهيئ أدوات الإضافة المتاحة في تعريف الشاشة.
 * @param {string} modalId معرف نافذة الإضافة أو الاستيراد.
 * @returns {void} يركب Dropzone والخرائط عند وجودها ويعرض نافذة Bootstrap.
 */
function openCreateModal(modalId) {
    const element = document.getElementById(modalId);
    if (!element) return;
    initializeCreateDropzone();
    bootstrap.Modal.getOrCreateInstance(element).show();
    if ((window.inputs ?? []).some(input => input.input === 'boundary' || input.id === 'boundary')) initializeBoundaryMap();
    if ((window.inputs ?? []).some(input => input.input === 'map' || input.id === 'map')) initMap();
}

/**
 * يفتح نافذة الاستيراد الحالية بنفس نقطة الاستدعاء المستخدمة في شريط الجدول.
 * @returns {void} يعرض import_file_modal ويهيئ أدوات الإضافة المشتركة.
 */
export function showImportModal() {
    openCreateModal('import_file_modal');
}

/**
 * يصفر صفوف multiform في نموذج الإضافة ثم يفتح النافذة وأدواتها.
 * @returns {void} يعدل صفوف النموذج المحلي وحالة النافذة، دون حفظ بيانات بالخادم.
 */
export function showAddModal() {
    $('#add_modal').find('[data-multiform-container]').each(function () {
        const [prefix, ...parts] = this.id.split('_');
        window.resetMultiForm(prefix, parts.join('_'));
    });
    openCreateModal('add_modal');
}
