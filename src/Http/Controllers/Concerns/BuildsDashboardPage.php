<?php

namespace ZeroOneZ\Dashboard\Http\Controllers\Concerns;

use ZeroOneZ\Dashboard\Support\Dashboard\DashboardAssetResolver;

/**
 * يجمع عقد شاشة table_view؛ يظل تعريف حقول كل شاشة وصلاحياتها في كنترولرها.
 */
trait BuildsDashboardPage
{
    /**
     * يرفق نوافذ الإضافة والتعديل والإجراءات الخاصة بالتبويب مع تعريف جدوله الحالي.
     *
     * يحتفظ بمفاتيح status وmessage وdata الصادرة من successResponse، ويضيف HTML
     * تحت fragments.modals حتى تستبدل الواجهة نماذج التبويب السابق قبل تهيئة الحقول.
     * يستخدم نفس قالب الصفحات العادية دون دفع المحتوى إلى stack غير معروض في AJAX.
     *
     * @param array<string, mixed> $data تعريف التبويب الكامل الناتج من prepareData.
     * @return \Illuminate\Http\JsonResponse تعريف الجدول ونوافذه المرسومة من Blade.
     */
    protected function dashboardTabResponse(array $data): \Illuminate\Http\JsonResponse
    {
        $response = $this->successResponse($data);
        $payload = $response->getOriginalContent();
        $payload['fragments'] = [
            'modals' => view(config('dashboard.view_namespace', 'zerooonez-dashboard').'::admin-components.modal-content', ['data' => $data])->render(),
        ];
        $payload['assets'] = DashboardAssetResolver::resolve($data);

        return $response->setData($payload);
    }

    /**
     * يحسب عنوان الشاشة ومرشحاتها وإحصاءاتها وأعمدتها ونماذجها بالترتيب المعتاد.
     *
     * يستدعى قبل تعديلات الدور في prepareData حتى لا تتغير روابط الإجراءات القديمة.
     * لا يضيف modals تلقائيا لأن بعض الشاشات تحسبها قبل تعديل الدور وأخرى بعده.
     *
     * @return array<string, mixed> أقسام الشاشة التي يعيدها الكنترولر نفسه.
     */
    protected function dashboardPageSections(): array
    {
        return [
            'title' => __('admin.'.$this->modelName),
            'filters' => $this->filters(),
            'statistics' => $this->statistics(),
            'show_list' => $this->show_list(),
            'inputs' => $this->inputs_list(),
            'datatable_actions' => $this->datatable_actions(),
            'update_inputs' => $this->update_inputs_list(),
        ];
    }

    /**
     * يربط الأقسام المحسوبة بإعدادات الشاشة الحالية وروابط CRUD المسماة.
     *
     * يقرأ الخصائص بعد تعديلات الدور ويحافظ على #placeholder# الذي يستبدله JavaScript.
     * الإضافات صريحة: columns وmodals وmap وboundary وhave_import بحسب الشاشة فقط.
     * يبقى الاسم القديم daterannge_filter_name كما هو لتوافق قوالب Blade والواجهة.
     *
     * @param array<string, mixed> $sections نتيجة dashboardPageSections().
     * @param array<string, mixed> $extra مفاتيح خاصة بالشاشة؛ لها أولوية عند التعارض.
     * @return array<string, mixed> قيمة data المتوقعة في dashboard.screen.table_view.
     */
    protected function dashboardPageData(array $sections, array $extra = []): array
    {
        $data = [
            'title' => $sections['title'],
            'daterange_filter' => $this->daterange_filter,
            'daterannge_filter_name' => $this->daterannge_filter_name,
            'daterange_filter_tooltip' => $this->daterange_filter_tooltip,
            'filters' => $sections['filters'],
            'filter_display' => $this->filter_display,
            'show_columns' => $this->show_columns,
            'have_actions' => $this->have_actions,
            'have_check_box' => $this->have_check_box,
            'get_single_item' => route($this->get_single_item, '#placeholder#'),
            'update_url' => route($this->update_url, '#placeholder#'),
            'delete_url' => route($this->delete_url, '#placeholder#'),
            'datatable_url' => route($this->datatable_url),
            'delete_all_url' => route($this->delete_all_url),
            'store_url' => route($this->store_url),
            'statistics' => $sections['statistics'],
            'select2' => $this->select2,
            'datatable' => $this->datatable,
            'show_list' => $sections['show_list'],
            'have_export' => $this->have_export,
            'have_add' => $this->have_add,
            'have_delete_all' => $this->have_delete_all,
            'inputs' => $sections['inputs'],
            'update_inputs' => $sections['update_inputs'],
            'have_validation' => $this->have_validation,
            'permissions' => $this->permissions,
            'datatable_actions' => $sections['datatable_actions'],
        ];

        return DashboardAssetResolver::withCompatibilityFlags(array_replace($data, $extra));
    }
}
