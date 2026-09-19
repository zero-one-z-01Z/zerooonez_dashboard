/** نقل تعريف المورد بصيغة JSON إلى مساري المعاينة والإنشاء مع CSRF ورسائل فشل موحدة. */

/**
 * يرسل وصف المورد إلى Laravel مع CSRF، ويحوّل أخطاء التحقق إلى رسالة مفهومة.
 * @param {string} url مسار المعاينة أو الإنشاء الذي وضعته Blade في dataset.
 * @param {object} definition وصف المورد؛ يشمل fingerprint عند طلب الإنشاء.
 * @returns {Promise<object>} جسم JSON الناجح؛ يرسل طلب POST واحدًا ولا يكتب ملفات من المتصفح مباشرة.
 * @throws {Error} عند فشل HTTP؛ تدمج رسائل التحقق، وتنتقل أخطاء الشبكة إلى المستدعي.
 */
export async function sendDefinition(url, definition) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(definition),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(body.errors ? Object.values(body.errors).flat().join('\n') : body.message || `تعذّر إكمال الطلب (${response.status}).`);
    }
    return body;
}

/** يجلب metadata مسموحة فقط لإكمال Model موجود، من دون قراءة سجلات أو حقول مخفية. */
export async function fetchCatalog(url, model = '') {
    const requestUrl = new URL(url, window.location.origin);
    if (model) requestUrl.searchParams.set('model', model);
    const response = await fetch(requestUrl.toString(), { headers: { Accept: 'application/json' } });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || `تعذّر قراءة بيانات الـModel (${response.status}).`);
    return body;
}
