/** إرسال تسجيل الدخول مع منع الإرسال المكرر وضمان إخفاء مؤشر التحميل. */
import '../crud.js';

function startLoginPage() {
    const form = document.getElementById('login_form');
    if (!form) return;
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';
        window.togglePageLoader?.('on');
        $.ajax({
            type: form.method || 'POST', url: form.action, data: new FormData(form),
            cache: false, contentType: false, processData: false,
        }).done(data => {
            if (data.code == 200) {
                window.notifySuccess?.(data.message);
                window.location.reload();
            } else window.notifyError?.(data.message);
        }).fail(xhr => window.notifyError?.(xhr.responseJSON?.message || xhr.statusText || 'Unknown error'))
            .always(() => {
                form.dataset.submitting = 'false';
                window.togglePageLoader?.('off');
            });
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startLoginPage, { once: true });
else startLoginPage();

