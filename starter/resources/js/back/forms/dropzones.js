/** إدارة نسخ Dropzone لكل حقل مع إبقاء myDropzone كاسم توافق للصفحات القديمة. */

const instances = new Set();

function activeInstances(root = document) {
    return [...instances].filter(instance => instance?.element?.isConnected && root.contains(instance.element));
}

/** ينشئ نسخة واحدة لعنصر الرفع ويزيل النسخة السابقة لنفس العنصر. */
export function createDropzone(element, options = {}) {
    if (!element || !globalThis.Dropzone) return null;
    destroyDropzones(element);
    const maxFiles = Number.parseInt(element.dataset.maxFiles || '5', 10);
    const instance = new Dropzone(element, {
        url: '#',
        autoProcessQueue: false,
        ...(globalThis.window?.dropzone_preview ? { previewTemplate: window.dropzone_preview } : {}),
        parallelUploads: maxFiles,
        maxFilesize: Number.parseFloat(element.dataset.maxFileSize || '2'),
        maxFiles,
        acceptedFiles: element.dataset.acceptedFiles || null,
        paramName: element.dataset.paramName || 'images[]',
        addRemoveLinks: true,
        ...options,
    });
    instances.add(instance);
    if (globalThis.window) window.myDropzone = instance;
    return instance;
}

/** يعيد كل نسخ الرفع المتصلة داخل الجذر المحدد. */
export function getDropzones(root = document) {
    const current = activeInstances(root);
    const legacy = globalThis.window?.myDropzone;
    if (legacy && !current.includes(legacy) && (!legacy.element || root.contains?.(legacy.element))) current.push(legacy);
    return current;
}

/** يدمر النسخ الموجودة داخل عنصر أو الجذر كله قبل إزالة DOM. */
export function destroyDropzones(root = document) {
    for (const instance of [...instances]) {
        const belongs = root === instance.element || root.contains?.(instance.element);
        if (!belongs) continue;
        instance.dashboardResetting = true;
        try { instance.destroy(); } finally { instances.delete(instance); }
    }
    if (globalThis.window && window.myDropzone && !window.myDropzone.element?.isConnected) window.myDropzone = null;
}

/** يتحقق من الحقول المطلوبة ثم يضيف الملفات الجديدة إلى FormData بأسماء حقولها. */
export function appendDropzoneFiles(formData, form) {
    const formInstances = getDropzones(form);
    const requiredElements = form.querySelectorAll
        ? [...form.querySelectorAll('.dropzone[data-required="true"]')]
        : [form.querySelector?.('.dropzone[data-required]')].filter(Boolean);
    for (const element of requiredElements) {
        const instance = formInstances.find(candidate => candidate.element === element);
        if (!instance || (instance.getAcceptedFiles?.() || []).length === 0) {
            element.classList.add('border-danger');
            return false;
        }
    }
    for (const instance of formInstances) {
        const element = instance.element;
        const accepted = instance.getAcceptedFiles?.() || [];
        const limit = Number(instance.options?.maxFiles ?? element?.dataset?.maxFiles);
        if ((Number.isFinite(limit) && limit > 0 && accepted.length > limit)
            || (instance.files || []).some(file => file.accepted === false)) {
            element?.classList?.add?.('border-danger');
            return false;
        }
        if (element?.dataset?.required === 'true' && accepted.length === 0) {
            element.classList.add('border-danger');
            return false;
        }
        element?.classList?.remove?.('border-danger');
        for (const file of instance.files || []) {
            if (file.status === 'queued') formData.append(instance.options.paramName, file, file.name);
        }
    }
    return true;
}

/** يمسح الملفات المعروضة لكل حقول الرفع داخل النموذج. */
export function resetDropzones(root) {
    for (const instance of getDropzones(root)) {
        instance.dashboardResetting = true;
        try { instance.removeAllFiles(); } finally { instance.dashboardResetting = false; }
    }
    root.querySelectorAll?.('input[data-upload-deletion]').forEach(input => input.remove());
}
