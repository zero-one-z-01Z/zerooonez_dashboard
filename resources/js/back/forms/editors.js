/** مدير محررات Quill متعددة النسخ وربطها بالحقول المخفية. */

const editors = new Map();
export const editorToolbar = [
    [{ font: [] }, { size: [] }], ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }], [{ script: 'super' }, { script: 'sub' }],
    [{ header: [false, 1, 2, 3, 4, 5, 6] }, 'blockquote', 'code-block'],
    [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
    ['direction', { align: [] }], ['link', 'image', 'video', 'formula'], ['clean'],
];

/** ينشئ محررًا مرة واحدة ويزامن HTML مع الحقل المرسل للخادم. */
export function createEditor(container, input, options = {}) {
    if (!container || !input || !globalThis.Quill) return null;
    const current = editors.get(container);
    if (current) {
        current.editor.root.innerHTML = input.value || '';
        return current.editor;
    }
    const editor = new Quill(container, {
        theme: 'snow', modules: { toolbar: editorToolbar }, ...options,
    });
    editor.root.innerHTML = input.value || '';
    const sync = () => { input.value = editor.root.innerHTML; };
    editor.on('text-change', sync);
    editors.set(container, { editor, input, sync });
    return editor;
}

/** يزيل handlers وDOM المولد للمحررات داخل الجذر قبل استبداله. */
export function destroyEditors(root = document) {
    for (const [container, entry] of [...editors]) {
        if (!(root === container || root.contains?.(container))) continue;
        entry.editor.off('text-change', entry.sync);
        container.innerHTML = '';
        container.classList.remove('ql-container', 'ql-snow');
        editors.delete(container);
    }
}

