/** مدخل Vite المتوافق مع صفحات Blade: تسجيل الدوال العامة وتجميع وحدات النماذج. */

import { resetTheForm, populateFormFromAjax, setFormData, populateIdField, populateForm } from './forms/population.js';
import { update_switch_val, customFunction, updatePassword } from './forms/submission.js';
import { showToast, togglePageLoader } from './forms/feedback.js';
import { direction, formatDateAgo } from './forms/formatting.js';
import { resetMultiForm, addItemMultiForm, removeItemMultiForm } from './forms/repeaters.js';
import { replaceUrlPlaceholder, replaceUrlPlaceholder_v2 } from './forms/urls.js';
import { updateSelect2Options, initializeSelect2 } from './forms/select2.js';
import { ajax_exe } from './forms/ajax.js';
import { defineFormValidation, validateSummerNotes } from './forms/validation.js';
import { restrictInputToLanguageJQuery, restrictInputToLanguageInForm } from './forms/language-input.js';
import { configureAjax } from './forms/ajax.js';
import { registerFormReady, registerSwitchReady, defineEvents, registerPermissionReady } from './forms/events.js';
import { initializeBookingRange } from './forms/date-range.js';
import { registerDashboardApi } from './core/dashboard.js';
import { createDropzone, destroyDropzones, getDropzones } from './forms/dropzones.js';
import { createBoundaryMap, createLocationMap, destroyMaps } from './forms/maps.js';
import { createEditor, destroyEditors } from './forms/editors.js';

// هذه الأسماء عقد عام تستخدمه Blade وdata-call_function؛ لا تغيرها عند إعادة التنظيم.
const formsApi = {
    resetTheForm,
    populateFormFromAjax,
    setFormData,
    populateIdField,
    populateForm,
    update_switch_val,
    customFunction,
    updatePassword,
    showToast,
    togglePageLoader,
    direction,
    formatDateAgo,
    resetMultiForm,
    addItemMultiForm,
    removeItemMultiForm,
    replaceUrlPlaceholder,
    replaceUrlPlaceholder_v2,
    updateSelect2Options,
    initializeSelect2,
    ajax_exe,
    defineFormValidation,
    validateSummerNotes,
    restrictInputToLanguageJQuery,
    restrictInputToLanguageInForm
};

registerDashboardApi('forms', formsApi, Object.fromEntries(Object.keys(formsApi).map(name => [name, name])));
registerDashboardApi('fields', {
    createDropzone, destroyDropzones, getDropzones,
    createBoundaryMap, createLocationMap, destroyMaps,
    createEditor, destroyEditors,
});

// نفس ترتيب التهيئة السابق: ready للنماذج، CSRF، المفاتيح، التفويض، النطاق، الصلاحيات.
registerFormReady();
configureAjax();
registerSwitchReady();
defineEvents();
initializeBookingRange();
registerPermissionReady();
