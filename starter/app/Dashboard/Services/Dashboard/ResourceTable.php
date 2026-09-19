<?php

namespace App\Dashboard\Services\Dashboard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** بحث وترتيب وترقيم صفحات الموارد المولّدة مع حصر أسماء الأعمدة في التعريف. */
final class ResourceTable
{
    /**
     * ينفذ DataTables داخل نطاق الاستعلام، ويعيد عدد السجلات قبل وبعد البحث والمرشحات.
     *
     * البحث العام يشمل text وtextarea وemail. فلاتر النص تستخدم like وبقية الأنواع مساواة.
     * الترتيب يأتي من موضع عمود العرض المسموح، والافتراضي id؛ length بين 1 و500.
     *
     * @param Request $request draw وstart وlength والبحث والمرشحات وأول ترتيب مطلوب.
     * @param Builder $query استعلام المورد الذي تضاف إليه الشروط والترتيب والترقيم قبل التنفيذ.
     * @param array<string, mixed> $definition الحقول وأنواعها وأعلام in_table وfilterable.
     * @return array<string, mixed> draw وrecordsTotal وrecordsFiltered وdata المجهزة بواسطة ResourceRecord.
     */
    public function response(Request $request, Builder $query, array $definition): array
    {
        $request->validate([
            'draw' => ['required', 'integer', 'min:0'], 'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:500'],
            'search.value' => ['nullable', 'string', 'max:255'], 'filters' => ['sometimes', 'array'],
            'filters.*' => ['nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'array'], 'order.0.column' => ['sometimes', 'integer', 'min:0'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
        ]);
        $total = (clone $query)->count();
        $fields = $definition['fields'];
        $searchable = array_column(array_filter($fields, fn ($field) => in_array($field['type'], ['text', 'textarea', 'email'], true)), 'name');
        $search = $request->input('search.value');
        if ($search !== null && $search !== '' && $searchable) {
            $query->where(function (Builder $nested) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    $nested->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
        foreach ($fields as $field) {
            $value = $request->input('filters.'.$field['name']);
            if ($field['filterable'] && $value !== null && $value !== '') {
                $isText = in_array($field['type'], ['text', 'textarea', 'email'], true);
                $query->where($field['name'], $isText ? 'like' : '=', $isText ? '%'.$value.'%' : $value);
            }
        }
        $filtered = (clone $query)->count();
        // أول عمودين للتحكم والتحديد، ثم id، ثم حقول العرض؛ لا نثق بأسماء يرسلها المتصفح.
        $display = [null, null, 'id', ...array_column(array_filter($fields, fn ($field) => $field['in_table']), 'name')];
        $sort = $display[(int) $request->input('order.0.column', 2)] ?? 'id';
        $query->orderBy($sort ?: 'id', $request->input('order.0.dir', 'desc'));
        $rows = $query->skip((int) $request->start)->take((int) $request->length)->get();

        return ['draw' => (int) $request->draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $rows->map(fn ($row) => app(ResourceRecord::class)->data($row, $fields))->all()];
    }
}
