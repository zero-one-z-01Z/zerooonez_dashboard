<?php

namespace ZeroOneZ\Dashboard\Services\Dashboard;

/** يترجم وصف المورد إلى العقد الحالي لقالب table_view دون نسخ Blade لكل صفحة. */
final class ResourcePage
{
    /**
     * يجهّز تعريف table_view من وصف المورد دون نسخ قالب لكل صفحة أو الاستعلام عن السجلات.
     *
     * ينشئ مدخلات الإضافة والتعديل والفلاتر وأزرار الصف وروابط CRUD؛ schema_fields
     * يحفظ نوع الحقول للواجهة حتى لا يعامل اسما مثل image كحقل رفع قديم.
     *
     * @param array<string, mixed> $definition التعريف المطبّع الذي توجد مساراته المسماة تحت admin.
     * @return array<string, mixed> مصفوفة data المتوافقة مع Blade وprepareDataTable.
     */
    public function data(array $definition): array
    {
        $prefix = $this->routePrefix($definition['resource']);
        $permission = $definition['permission'];
        $inputs = array_map($this->input(...), $definition['fields']);
        $columns = [['key' => 'id', 'type' => 'id', 'title' => 'ID']];
        $filters = [];
        foreach ($definition['fields'] as $field) {
            if ($field['in_table']) {
                $columns[] = ['key' => $field['name'], 'type' => 'text', 'title' => $field['label']];
            }
            if ($field['filterable']) {
                $filters[] = ['id' => $field['name'], 'label' => $field['label'], 'width' => 6,
                    'input' => 'text', 'type' => 'text', 'required' => false, 'value' => '', 'select2' => false];
            }
        }

        return [
            'title' => $definition['title'], 'columns' => ['id', ...array_column($definition['fields'], 'name')],
            'show_list' => $columns, 'inputs' => $inputs,
            'schema_fields' => array_column($definition['fields'], 'type', 'name'),
            'update_inputs' => [['id' => 'id', 'input' => 'hidden'], ...$inputs],
            'filters' => $filters, 'statistics' => [], 'filter_display' => 'modal',
            'daterange_filter' => false, 'daterannge_filter_name' => 'created_at', 'daterange_filter_tooltip' => '',
            'show_columns' => false, 'have_actions' => true, 'have_check_box' => true,
            'have_validation' => true, 'have_add' => true, 'have_delete_all' => true, 'have_export' => true,
            'datatable' => true, 'select2' => false, 'auto_start' => false,
            'permissions' => array_map(fn ($action) => $action.'_'.$permission, ['create', 'update', 'delete', 'view']),
            'datatable_url' => route($prefix.'datatable'), 'store_url' => route($prefix.'store'),
            'delete_all_url' => route($prefix.'delete_all'),
            'update_url' => route($prefix.'update', '#placeholder#'),
            'delete_url' => route($prefix.'delete', '#placeholder#'),
            'get_single_item' => route($prefix.'get_single_item', '#placeholder#'),
            'datatable_actions' => [
                ['name' => __('buttons.edit'), 'type' => 'modal', 'link' => '#edit_modal', 'icon' => 'icon-base ti tabler-edit', 'onclick' => 'edit_item', 'permission' => 'update_'.$permission],
                ['name' => __('buttons.delete'), 'type' => 'delete', 'link' => route($prefix.'delete', '#placeholder#'), 'value' => 'id', 'icon' => 'icon-base ti tabler-trash', 'onclick' => 'delete_item', 'permission' => 'delete_'.$permission],
            ],
        ];
    }

    private function routePrefix(string $resource): string
    {
        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        return ($prefix === '' ? '' : $prefix.'.').$resource.'.';
    }

    /**
     * يختار عنصر النموذج بحسب نوع الحقل ويضع قاعدة required الخاصة بالمتصفح.
     *
     * textarea يرسم مساحة نصية، وboolean مفتاحا، والأرقام number؛ decimal يحمل step=0.01.
     * قواعد الخادم الكاملة تبنى مستقلة بواسطة ResourceDefinition::rules.
     *
     * @param array<string, mixed> $field حقل مطبّع يتضمن name وlabel وtype وrequired.
     * @return array<string, mixed> id وlabel وinput وtype وvalidation وأي خيارات إضافية للعنصر.
     */
    private function input(array $field): array
    {
        $input = ['id' => $field['name'], 'label' => $field['label'], 'value' => '',
            'input' => 'text', 'type' => 'text', 'validation' => ['required' => $field['required']]];
        if ($field['type'] === 'textarea') {
            $input['input'] = 'textarea';
        } elseif ($field['type'] === 'boolean') {
            $input['input'] = 'switch';
        } else {
            $input['type'] = match ($field['type']) {
                'integer', 'decimal' => 'number', 'datetime' => 'datetime-local',
                'date' => 'date', 'email' => 'email', default => 'text',
            };
            if ($field['type'] === 'decimal') {
                $input['step'] = '0.01';
            }
        }
        return $input;
    }
}
