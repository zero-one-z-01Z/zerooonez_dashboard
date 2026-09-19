/** أحداث النماذج بالتفويض حتى تعمل النوافذ والحقول التي تضيفها تبويبات AJAX لاحقًا. */

import { resetTheForm } from './population.js';
import { registerValidationRules } from './validation-rules.js';

let pageReadyRegistered = false;
const permissionGroups = {
    create: { toggle: '[id="selectAll"]', fields: '.permission-checkbox' },
    edit: { toggle: '[id="selectAllEdit"]', fields: '.permission-checkbox-edit' }
};

/**
 * يركز رابط القائمة الحالي ويسجل قواعد التحقق مرة واحدة عند اكتمال الصفحة الأصلية.
 * @returns {void} يغير التركيز ويسجل قواعد التحقق، دون تسجيل مستمعي النوافذ الديناميكية هنا.
 */
function initializeFormPage() {
    $('.sidebar a.active').focus();
    registerValidationRules();
}

/**
 * يعيد نموذجًا واحدًا من نماذج النافذة إلى حالته الأصلية؛ this هو عنصر form الحالي.
 * @returns {void} يمسح قيم النموذج وحالة التحقق عبر resetTheForm.
 */
function resetModalForm() {
    resetTheForm(this);
}

/**
 * يمسح نماذج النافذة المغلقة فقط إذا حملت reset-on-close؛ يتجاهل أحداث النوافذ المتداخلة.
 * @param {Event} event حدث Bootstrap الذي بدأ من النافذة نفسها.
 * @returns {void} يمسح نماذج النافذة المقصودة أثناء hide؛ لا يزيل DOM أو يلغي طلبات الحفظ.
 */
function resetClosingModal(event) {
    if (event.target !== this) return;
    const $modal = $(this);
    if ($modal.attr('reset-on-close') !== undefined) $modal.find('form').each(resetModalForm);
}

/**
 * يسجل تهيئة الصفحة ومسح النوافذ الحالية والمضافة لاحقًا، ويستبدل مستمع الوحدة فقط عند التكرار.
 * @returns {void} لا يحتاج إعادة استدعاء هذه الدالة بعد تبديل محتوى التبويب.
 */
export function registerFormReady() {
    $(document).off('hide.bs.modal.dashboardForms', '.modal')
        .on('hide.bs.modal.dashboardForms', '.modal', resetClosingModal);
    if (!pageReadyRegistered) {
        pageReadyRegistered = true;
        $(document).ready(initializeFormPage);
    }
}

/**
 * يرسل قيمة المفتاح المخصص ومعرف السجل عند تغييره، دون مشاركة نموذج أو حدث عام.
 * @param {Event} event حدث change؛ this هو المفتاح الذي تغيّر.
 * @returns {void} يمرر الرابط و{id,value} إلى update_switch_val لإرسال التغيير.
 */
function submitCustomSwitch(event) {
    event.preventDefault();
    const $checkbox = $(this);
    window.update_switch_val($checkbox.data('link'), {
        value: $checkbox.is(':checked') ? 1 : 0,
        id: $checkbox.data('id')
    });
}

/**
 * يفوّض تغييرات المفاتيح المخصصة إلى document لدعم الحقول الجديدة ومنع تكرار الطلبات بعد إعادة التهيئة.
 * @returns {void} يحافظ على مستمعي الوحدات الأخرى ويستبدل مستمع هذه الوحدة فقط.
 */
export function registerSwitchReady() {
    $(document).off('change.dashboardCustomSwitch', '.switch-input.custom')
        .on('change.dashboardCustomSwitch', '.switch-input.custom', submitCustomSwitch);
}

/**
 * يستدعي اسم دالة window الذي حددته Blade ويمرر الحدث ونسخة jQuery من العنصر.
 * @param {Event} event الحدث المفوّض؛ this هو العنصر المطابق لـ data-event_on.
 * @returns {void} يشغّل المعالج العام إن وجد، أو يسجل تحذيرًا باسمه؛ لا يمنع الحدث تلقائيًا.
 */
function dispatchDeclaredAction(event) {
    const name = $(this).data('call_function');
    const handler = window[name];
    if (typeof handler === 'function') handler(event, $(this));
    else console.warn(`❌ ${name} is not a function on window`);
}

/**
 * يفوّض click وkeyup وsubmit وchange إلى معالجات Blade، مع إزالة تسجيل الوحدة السابق فقط.
 * @returns {void} تعمل المعالجات مرة واحدة حتى لو أعيد استدعاء التهيئة بعد إضافة نماذج.
 */
export function defineEvents() {
    for (const eventName of ['click', 'keyup', 'submit', 'change']) {
        const selector = `[data-event_on="${eventName}"]`;
        const event = `${eventName}.dashboardFormActions`;
        $(document).off(event, selector).on(event, selector, dispatchDeclaredAction);
    }
}

/**
 * يحدد مجموعة صلاحيات الإنشاء أو التعديل من المفتاح أو مربع الصلاحية نفسه.
 * @param {jQuery} $element العنصر الذي تغيّر داخل النموذج.
 * @returns {{toggle: string, fields: string}} محددا المجموعة داخل النموذج الحالي فقط.
 */
function permissionGroup($element) {
    return $element.attr('id') === 'selectAllEdit' || $element.hasClass('permission-checkbox-edit')
        ? permissionGroups.edit : permissionGroups.create;
}

/**
 * يطابق جميع مربعات المجموعة مع مفتاح تحديد الكل في نفس form؛ this هو المفتاح.
 * @returns {void} يغير checked لمربعات المجموعة داخل أقرب form فقط.
 */
function toggleFormPermissions() {
    const $toggle = $(this);
    const group = permissionGroup($toggle);
    $toggle.closest('form').find(group.fields).prop('checked', $toggle.prop('checked'));
}

/**
 * يحدّث مفتاح تحديد الكل من مربعات المجموعة في نفس form؛ this هو مربع الصلاحية المتغير.
 * @returns {void} يضبط مفتاح المجموعة إلى true فقط عندما تكون جميع مربعاتها محددة.
 */
function synchronizePermissionToggle() {
    const $checkbox = $(this);
    const group = permissionGroup($checkbox);
    const $form = $checkbox.closest('form');
    const $permissions = $form.find(group.fields);
    const allChecked = $permissions.length > 0 && $permissions.filter(':checked').length === $permissions.length;
    $form.find(group.toggle).prop('checked', allChecked);
}

/**
 * يفوّض مفاتيح صلاحيات الإنشاء والتعديل ويعزل كل مجموعة داخل نموذجها، بما يشمل النماذج المضافة لاحقًا.
 * @returns {void} إعادة الاستدعاء تستبدل مستمعي الصلاحيات فقط ولا تضاعفهم.
 */
export function registerPermissionReady() {
    $(document).off('change.dashboardPermissions', '[id="selectAll"], [id="selectAllEdit"]')
        .on('change.dashboardPermissions', '[id="selectAll"], [id="selectAllEdit"]', toggleFormPermissions);
    $(document).off('change.dashboardPermissions', '.permission-checkbox, .permission-checkbox-edit')
        .on('change.dashboardPermissions', '.permission-checkbox, .permission-checkbox-edit', synchronizePermissionToggle);
}
