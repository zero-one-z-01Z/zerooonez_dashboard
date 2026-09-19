/** معاينات الصور والفيديو وملفات Dropzone المحفوظة. */

import { createDropzone } from './dropzones.js';

/**
 * يستبدل شرائح carouselExample ومؤشراته بصور السجل وتعليقاتها.
 * @param {Array} value صور تحتوي image وtitle وdescription اختياريًا.
 * @returns {void} يستبدل مؤشرات وصور carouselExample؛ يتطلب الحاوية وعناصرها في الصفحة.
 */
export function populateCarousel(value) {
    const indicatorsContainer = document.querySelector('#carouselExample .carousel-indicators');
    const innerContainer = document.querySelector('#carouselExample .carousel-inner');

    indicatorsContainer.innerHTML = ''; // Clear previous indicators
    innerContainer.innerHTML = '';      // Clear previous slides

    value.forEach((item, index) => {

        const indicator = document.createElement('button');
        indicator.type = 'button';
        indicator.setAttribute('data-bs-target', '#carouselExample');
        indicator.setAttribute('data-bs-slide-to', index);
        indicator.setAttribute('aria-label', `Slide ${index + 1}`);
        if (index === 0) {
            indicator.classList.add('active');
            indicator.setAttribute('aria-current', 'true');
        }
        indicatorsContainer.appendChild(indicator);

        const carouselItem = document.createElement('div');
        carouselItem.classList.add('carousel-item');
        if (index === 0) carouselItem.classList.add('active');

        carouselItem.innerHTML = `
<img src="${item.image}" class="d-block w-100" alt="Slide ${index + 1}">
${item.title || item.description ? `
<div class="carousel-caption d-none d-md-block">
  ${item.title ? `<h4>${item.title}</h4>` : ''}
  ${item.description ? `<p>${item.description}</p>` : ''}
</div>
` : ''}
`;
        innerContainer.appendChild(carouselItem);
    });
}

/**
 * يعيد إنشاء Dropzone للنموذج ويعرض الصور المحفوظة ويسجل معرفات الصور المحذوفة.
 * @param {string} formSelector محدد النموذج.
 * @param {Array} value الصور المحفوظة بمعرفاتها وروابطها.
 * @returns {void} ينشئ نسخة خاصة بالحقل ويضيف deleted_images[] عند إزالة صورة محفوظة.
 */
export function populateImages(formSelector, value, key = 'images') {
    if(globalThis.Dropzone){
        const form = document.querySelector(formSelector);
        if (!form) return;
        const multiEl = [...form.querySelectorAll('[data-upload-field]')].find(el => el.dataset.uploadField === key.replace(/\[\]$/, ''))
            ?? (key === 'images' ? document.querySelector(formSelector+"dropzone-multi") : null);
        const deletedKey = multiEl?.dataset.deletedKey || 'deleted_images';
        form.querySelectorAll('input[data-upload-deletion]').forEach(input => { if (input.dataset.uploadDeletion === key) input.remove(); });
        const dropzone = multiEl ? createDropzone(multiEl) : null;
        if (!dropzone) return;
        dropzone.on('removedfile', file => {
            if (dropzone.dashboardResetting || !file.dashboardExisting || file.id == null) return;
            const duplicate = [...form.querySelectorAll('input[data-upload-deletion]')].some(input => input.dataset.uploadDeletion === key && input.value === String(file.id));
            if (duplicate) return;
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = deletedKey + '[]'; input.value = file.id;
            input.dataset.uploadDeletion = key;
            form.appendChild(input);
        });
        (Array.isArray(value) ? value : []).forEach((image) => {

            const mockFile = {
                name: image.image,
                id: image.id,
                dashboardExisting: true,
                accepted: true,
            };

            dropzone.emit("addedfile", mockFile);
            dropzone.emit("thumbnail", mockFile, image.image);
            dropzone.emit("complete", mockFile);
            dropzone.files.push(mockFile);
            mockFile.previewElement?.setAttribute('data-existing-file', String(image.id));
        });
    }else{
        window.myDropzone = null;
}
}

/**
 * يحدّث مصدر معاينة الصورة داخل النموذج.
 * @param {string} formSelector محدد النموذج.
 * @param {string} key اسم حقل الصورة.
 * @param {string} value رابط الصورة.
 * @returns {void} يحدث src لمعاينة preview المتاحة داخل النموذج، دون رفع صورة.
 */
export function populateImage(formSelector, key, value) {
var previewId = '#preview' + key; // e.g. #createimage
var $preview = $(formSelector).find(previewId);

if ($preview.length > 0) {
    $preview.attr('src', value); // Set the image source
}
}

/**
 * يعرض فيديو محفوظًا مع زر يضيف delete_video للنموذج ويزيل المعاينة عند إغلاق النافذة.
 * @param {string} formSelector محدد النموذج.
 * @param {string} videoUrl رابط الفيديو.
 * @returns {void} يضيف معاينة وزر حذف؛ الحذف يسجل delete_video=true للإرسال اللاحق.
 */
export function populateVideo(formSelector, videoUrl) {
    removeVideoPreview();

        const previewDiv = document.createElement('div');
        previewDiv.id = 'videoPreview';
        previewDiv.style.marginTop = '20px';
        previewDiv.style.display = 'flex';
        previewDiv.style.justifyContent = 'center';
        previewDiv.style.position = 'relative'; // relative for absolute X button
        previewDiv.style.width = '100%';

        const video = document.createElement('video');
        video.setAttribute('controls', true);
        video.setAttribute('autoplay', false);
        video.setAttribute('muted', true);
        video.setAttribute('width', '300');

        const source = document.createElement('source');
        source.setAttribute('src', videoUrl);
        source.setAttribute('type', 'video/mp4');

        video.appendChild(source);

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.type = 'button';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '5px';
        closeBtn.style.right = '5px';
        closeBtn.style.background = 'rgba(0,0,0,0.6)';
        closeBtn.style.color = '#fff';
        closeBtn.style.border = 'none';
        closeBtn.style.borderRadius = '50%';
        closeBtn.style.width = '30px';
        closeBtn.style.height = '30px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontSize = '18px';
        closeBtn.style.lineHeight = '30px';

        const form = document.querySelector(formSelector);
        closeBtn.addEventListener('click', function () {

            previewDiv.remove();

            let deleteInput = form.querySelector('input[name="delete_video"]');
            if (!deleteInput) {
                deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_video';
                form.appendChild(deleteInput);
            }
            deleteInput.value = 'true';
        });

        previewDiv.appendChild(video);
        previewDiv.appendChild(closeBtn);

        form.insertBefore(previewDiv, form.firstChild);

        $(formSelector.replace("form", "modal")).on('hidden.bs.modal', function () {

            removeVideoPreview();
        });
}

/**
 * يحذف معاينة الفيديو الحالية إذا كانت موجودة.
 * @returns {void} يزيل عنصر videoPreview فقط إذا وجد.
 */
export function removeVideoPreview() {
    const oldPreview = document.getElementById('videoPreview');
    if (oldPreview) {
        oldPreview.remove();
    }
}
