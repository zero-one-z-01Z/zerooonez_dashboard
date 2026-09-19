/** محادثة الدعم: إرسال واستقبال الرسائل وبناء DOM آمن من بيانات الخادم. */
import '../crud.js';
import { readJsonConfig } from '../core/config.js';
import { mountContent, registerCleanup } from '../core/lifecycle.js';

function messageContent(message) {
    if (message.type === 'image') {
        const image = document.createElement('img');
        image.src = message.message;
        image.alt = '';
        image.style.cssText = 'height:200px;width:auto;max-width:400px;object-fit:contain;cursor:pointer';
        image.addEventListener('click', () => window.open(image.src, '_blank', 'noopener'));
        return image;
    }
    if (message.type === 'audio') {
        const audio = document.createElement('audio');
        audio.controls = true;
        const source = document.createElement('source');
        source.src = message.message;
        source.type = 'audio/mpeg';
        audio.appendChild(source);
        return audio;
    }
    const paragraph = document.createElement('p');
    paragraph.className = 'mb-0 ctext-content';
    paragraph.textContent = message.message ?? '';
    return paragraph;
}

export function renderChatMessage(message, side, imageUrl = null) {
    const item = document.createElement('li');
    item.className = `chat-list ${side}`;
    const conversation = document.createElement('div');
    conversation.className = 'conversation-list';
    if (side === 'left' && imageUrl) {
        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = '';
        avatar.appendChild(image);
        conversation.appendChild(avatar);
    }
    const content = document.createElement('div');
    content.className = 'user-chat-content';
    const wrap = document.createElement('div');
    wrap.className = 'ctext-wrap';
    const body = document.createElement('div');
    body.className = 'ctext-wrap-content';
    body.appendChild(messageContent(message));
    wrap.appendChild(body);
    const name = document.createElement('div');
    name.className = 'conversation-name';
    const time = document.createElement('small');
    time.className = 'text-muted time';
    time.textContent = window.formatDateAgo?.(new Date()) || '';
    name.appendChild(time);
    content.append(wrap, name);
    conversation.appendChild(content);
    item.appendChild(conversation);
    return item;
}

function scrollChat() {
    const container = document.getElementById('chat-container');
    if (container) container.scrollTop = container.scrollHeight;
}

function startChatPage() {
    const root = document.getElementById('chat-container');
    const list = document.getElementById('users-conversation');
    const form = document.getElementById('chatinput-form');
    const config = readJsonConfig('dashboard-chat-config', {});
    if (!root || !list) return;
    scrollChat();
    if (form) {
        const submit = event => {
            event.preventDefault();
            const data = new FormData(form);
            const file = document.getElementById('fileInput')?.files?.[0];
            data.delete('attachment');
            if (file?.type?.startsWith('image/')) data.set('message', file);
            window.ajax_exe({
                url: config.storeUrl, type: 'post', data, showLoading: false,
                success: response => {
                    form.reset();
                    window.clearPreview?.();
                    list.appendChild(renderChatMessage(response.data, 'right'));
                    scrollChat();
                },
                error: xhr => window.notifyError?.(xhr.responseJSON?.message || xhr.statusText),
            });
        };
        form.addEventListener('submit', submit);
        registerCleanup(root, () => form.removeEventListener('submit', submit));
    }
    if (config.support && window.Echo) {
        const channelName = `ticket_${config.chatId}`;
        const channel = Echo.channel(channelName);
        channel.listen('.new_message', data => {
            if (data.sender !== 'user') return;
            list.appendChild(renderChatMessage(data, 'left', config.userImage));
            scrollChat();
        });
        registerCleanup(root, () => Echo.leave(channelName));
    }
    mountContent(root, { page: 'chat' });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startChatPage, { once: true });
else startChatPage();

