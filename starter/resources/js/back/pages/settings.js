/** تشغيل صفحة الإعدادات من تعريف JSON دون JavaScript داخل Blade. */
import '../crud.js';
import { Dashboard } from '../core/dashboard.js';
import { readJsonConfig } from '../core/config.js';
import { populateField } from '../forms/fields.js';
import { createEditor } from '../forms/editors.js';
import { mountContent } from '../core/lifecycle.js';

async function startSettingsPage() {
    const form = document.getElementById('settings_form');
    const config = readJsonConfig('dashboard-settings-config', { inputs: [], assets: [] });
    if (!form) return;
    try {
        await Dashboard.assets.loadMany(config.assets || ['quill']);
        window.update_inputs = config.inputs;
        const record = Object.fromEntries(config.inputs.map(input => [input.id, input.value]));
        for (const input of config.inputs) {
            if (input.input === 'html') createEditor(document.getElementById(`full-editor${input.id}`), document.getElementById(input.id));
            else populateField('#settings_form', input.id, input.value, record);
        }
        mountContent(form, { page: 'settings' });
    } catch (error) {
        console.error('Failed to initialize settings page:', error);
        window.showNotification?.('error', error.message);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startSettingsPage, { once: true });
else startSettingsPage();

