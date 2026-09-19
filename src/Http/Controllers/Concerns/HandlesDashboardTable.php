<?php

namespace ZeroOneZ\Dashboard\Http\Controllers\Concerns;

use ZeroOneZ\Dashboard\Services\DataTableService;
use Illuminate\Http\Request;

/**
 * يفصل خطوات DataTables المشتركة عن استعلام كل مورد وقواعد تصفيته الخاصة.
 */
trait HandlesDashboardTable
{
    /**
     * ينشئ استعلام الشاشة ويمرر المرشحات والبحث والترتيب والترقيم إلى DataTableService.
     *
     * يدمج المرشحات العامة في الطلب كما في الكنترولرز القديمة؛ تحتفظ الدالة المخصصة
     * applyCustomFilters بالمرشحات الأصلية، ومنها قيود الشركة أو التأمين أو الموظف.
     * لا يحول النتيجة إلى JSON حتى يستطيع AuctionController تجهيز خلاياه أولا.
     *
     * @param Request $request طلب DataTables الذي يحتوي draw وstart وlength وfilters.
     * @param array<int, string> $filterableColumns أعمدة المورد ومسارات العلاقات المسموح ببحثها.
     * @param string|null $sortBy ترتيب افتراضي خاص مثل sort_number، أو null لسلوك الخدمة.
     * @param string|null $sortDirection اتجاه الترتيب الخاص؛ null يترك القرار للخدمة.
     * @return array<string, mixed> الصفوف والأعداد وdraw بنفس عقد DataTables الحالي.
     */
    protected function dashboardTableData(
        Request $request,
        array $filterableColumns,
        ?string $sortBy = null,
        ?string $sortDirection = null
    ): array {
        $query = $this->initializeDataTableQuery();
        $filters = $this->preprocessFilters($request->filters ?? []);
        $request->merge(['filters' => $filters['dataTableFilters']]);

        $service = new DataTableService(
            $request,
            $query,
            function ($query) use ($filters) {
                $this->applyCustomFilters($query, $filters['filters']);
            },
            false,
            $filterableColumns,
            $sortBy,
            $sortDirection
        );

        return $service->getData($filterableColumns);
    }

    /**
     * يمرر المرشحات كاملة للمسارين العام والمخصص عندما لا تحتاج الشاشة فصلهما.
     *
     * يمكن للكنترولر تعريف نفس الدالة لاستبعاد مفاتيح يعالجها بنفسه، مثل buyer_id.
     *
     * @param array<string, mixed> $filters مرشحات الطلب قبل أي استبعاد.
     * @return array{filters: array, dataTableFilters: array} المرشحات الأصلية ومرشحات الخدمة.
     */
    private function preprocessFilters(array $filters): array
    {
        return ['filters' => $filters, 'dataTableFilters' => $filters];
    }

    /**
     * نقطة تخصيص فارغة للشاشات التي تكفيها تصفية DataTableService العامة.
     *
     * الكنترولر ذو القيود الخاصة يعرف هذه الدالة ليضيف شروطه إلى نفس الاستعلام.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query الاستعلام الجاري تجهيزه؛ لا يعدل هنا.
     * @param array<string, mixed> $filters المرشحات الأصلية قبل تجهيز مرشحات الخدمة.
     * @return void
     */
    private function applyCustomFilters($query, $filters)
    {
    }
}
