{{--
    غلاف الصفحات الكاملة: يحتفظ بموضع النوافذ في @stack('modals') داخل layout.
    لا ترسل ناتج render لهذا الغلاف في AJAX؛ استخدم modal-content لأن @push لا يطبع المحتوى هنا.
    كلا المسارين يستعمل نفس تعريف $data ونفس قالب النماذج، فلا توجد نسخة حقول مستقلة للتبويبات.
--}}
@push('modals')
    @include('zerooonez-dashboard::admin-components.modal-content', ['data' => $data])
@endpush
