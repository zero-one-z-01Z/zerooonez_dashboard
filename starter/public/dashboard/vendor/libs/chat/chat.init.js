

function scrollToBottom() {
    setTimeout(function () {
        // Get the scrollable conversation container
        var conversation = document.getElementById("chat-conversation");
        if (conversation) {
            conversation.scrollTo({
                top: conversation.scrollHeight,
                behavior: "smooth"
            });
        }
    }, 100);
}




document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const previewArea = document.getElementById('previewArea');
    window.clearPreview = function() {
        previewArea.innerHTML = '';
        fileInput.value = '';
    }
    fileInput.addEventListener('change', function() {

        const file = this.files[0];

        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            return;
        }
        console.log('asd5');
        const fileUrl = URL.createObjectURL(file);

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex align-items-center';
        console.log('asd6');
        const img = document.createElement('img');
        img.src = fileUrl;
        img.style.maxHeight = '100px';
        img.className = 'me-2';

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn btn-sm btn-link ms-2';
        closeBtn.innerHTML = '<i class="icon-base ti tabler-trash"></i>';
        closeBtn.onclick = window.clearPreview;

        wrapper.appendChild(img);
        wrapper.appendChild(closeBtn);

        previewArea.appendChild(wrapper);
    });
    scrollToBottom("chat-container");
});
