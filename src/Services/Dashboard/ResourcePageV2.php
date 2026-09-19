<?php

namespace ZeroOneZ\Dashboard\Services\Dashboard;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

/** Adapts a normalized direct v2 definition to the existing dashboard table contract. */
final class ResourcePageV2
{
    /** @return array<string, mixed> */
    public function data(array $definition): array
    {
        $prefix = $this->routePrefix($definition['resource']);
        $capabilities = $definition['capabilities'];

        return [
            'title' => $this->label($definition['title_key'], $definition['title_ar'], $definition['title_en'], 'admin'),
            'columns' => ['id', ...array_column($definition['columns'], 'key')],
            'show_list' => $this->showList($definition),
            'inputs' => $this->inputs($definition['fields']),
            'schema_fields' => $this->schemaFields($definition),
            'update_inputs' => $this->updateInputs($definition),
            'filters' => $this->filterList($definition),
            'statistics' => [],
            'filter_display' => 'modal',
            'daterange_filter' => false,
            'daterannge_filter_name' => 'created_at',
            'daterange_filter_tooltip' => '',
            'show_columns' => false,
            'have_actions' => $this->actions($definition) !== [],
            'have_check_box' => $capabilities['delete_all'],
            'have_validation' => true,
            'have_add' => $capabilities['create'],
            'have_delete_all' => $capabilities['delete_all'],
            'have_export' => $capabilities['export'],
            'datatable' => true,
            'select2' => $this->usesSelect2($definition),
            'auto_start' => false,
            'permissions' => app(ResourceDefinitionV2::class)->permissions($definition),
            'datatable_url' => $this->route($prefix.'datatable'),
            'store_url' => $capabilities['create'] ? $this->route($prefix.'store') : null,
            'delete_all_url' => $capabilities['delete_all'] ? $this->route($prefix.'delete_all') : null,
            'update_url' => $capabilities['update'] ? $this->route($prefix.'update', '#placeholder#') : null,
            'delete_url' => $capabilities['delete'] ? $this->route($prefix.'delete', '#placeholder#') : null,
            'get_single_item' => $capabilities['update'] ? $this->route($prefix.'get_single_item', '#placeholder#') : null,
            'datatable_actions' => $this->actions($definition),
            'modals' => $this->modals($definition),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function inputs(array $fields): array
    {
        return array_map($this->input(...), $fields);
    }

    /** @return list<array<string, mixed>> */
    public function updateInputs(array $definition): array
    {
        $fields = $definition['edit_mode'] === 'custom' ? $definition['edit_fields'] : $definition['fields'];
        return [['id' => 'id', 'input' => 'hidden'], ...$this->inputs($fields)];
    }

    /** @return list<array<string, mixed>> */
    public function showList(array $definition): array
    {
        $columns = [['key' => 'id', 'type' => 'id', 'title' => 'ID', 'searchable' => false, 'sortable' => true]];
        foreach ($definition['columns'] as $column) {
            $columns[] = [
                'key' => $column['key'],
                'type' => $column['type'],
                'title' => $this->label($column['label_key'], $column['label_ar'], $column['label_en'], 'inputs'),
                'searchable' => $column['searchable'],
                'sortable' => $column['sortable'],
            ];
        }
        return $columns;
    }

    /** @return list<array<string, mixed>> */
    public function filterList(array $definition): array
    {
        if (!$definition['capabilities']['filters']) {
            return [];
        }
        return array_map(function (array $filter): array {
            $input = $this->input($filter);
            $input['required'] = false;
            $input['operator'] = $filter['operator'];
            $input['target'] = $filter['target'];
            $input['audience'] = $filter['audience'];
            return $input;
        }, $definition['filters']);
    }

    /** @return list<array<string, mixed>> */
    public function actions(array $definition): array
    {
        if ($definition['actions'] !== []) {
            return array_values(array_filter(array_map(function (array $action): ?array {
                // A show shortcut records the conventional future route, but it must
                // never render a dangling button in a generated table.
                if (($action['pending'] ?? false) === true) return null;
                return array_filter([
                    'name' => $this->label($action['name_key'], $action['name_ar'], $action['name_en'], 'admin'),
                    'type' => $action['type'],
                    'link' => $action['route'] ? $this->route($action['route'], '#placeholder#') : $action['link'],
                    'form_id' => $action['form_id'], 'icon' => $action['icon'], 'value' => $action['value'],
                    'blank' => $action['blank'], 'permission' => $action['permission'], 'onclick' => $action['onclick'],
                    'if' => $action['if'], 'operation' => $action['operation'],
                ], fn ($value) => $value !== '' && $value !== null && $value !== []);
            }, $definition['actions'])));
        }

        $prefix = $this->routePrefix($definition['resource']);
        $permission = $definition['permission'];
        $actions = [];
        if ($definition['capabilities']['update']) {
            $actions[] = [
                'name' => __('buttons.edit'), 'type' => 'modal', 'link' => '#edit_modal',
                'icon' => 'icon-base ti tabler-edit', 'onclick' => 'edit_item',
                'permission' => 'update_'.$permission, 'operation' => 'edit',
            ];
        }
        if ($definition['capabilities']['delete']) {
            $actions[] = [
                'name' => __('buttons.delete'), 'type' => 'delete',
                'link' => $this->route($prefix.'delete', '#placeholder#'), 'value' => 'id',
                'icon' => 'icon-base ti tabler-trash', 'onclick' => 'delete_item',
                'permission' => 'delete_'.$permission, 'operation' => 'delete',
            ];
        }
        return $actions;
    }

    /** @return list<array<string, mixed>> */
    public function modals(array $definition): array
    {
        return array_map(function (array $modal): array {
            return [
                'id' => $modal['id'], 'form_id' => $modal['form_id'],
                'title' => $this->label($modal['title_key'], $modal['title_ar'], $modal['title_en'], 'admin'),
                'link' => $modal['route'] ? $this->route($modal['route']) : null,
                'method' => $modal['method'], 'permission' => $modal['permission'],
                'operation' => $modal['operation'], 'inputs' => $this->inputs($modal['inputs']),
            ];
        }, $definition['modals']);
    }

    /** @return array<string, mixed> */
    private function input(array $field): array
    {
        if ($field['input'] === 'empty') {
            return ['input' => 'empty', 'width' => $field['width']];
        }
        $items = array_map(fn (array $item) => [
            'value' => $item['value'],
            'text' => app()->getLocale() === 'en' ? $item['text_en'] : $item['text_ar'],
            'selected' => $item['selected'],
        ], $field['items']);
        $input = [
            'id' => $field['name'],
            'label' => $this->label($field['label_key'], $field['label_ar'], $field['label_en'], 'inputs'),
            'value' => $field['default'] ?? '',
            'input' => $field['input'],
            'type' => $field['input'] === 'text' ? $field['html_type'] : $field['input'],
            'validation' => array_filter($field['validation'], fn ($value) => $value !== null),
            'width' => $field['width'],
            'read_only' => $field['read_only'],
            'items' => $items,
            'select2' => $field['select2'],
            'url' => $field['route'] ? $this->route($field['route']) : null,
            'parent_id' => $field['parent_id'], 'child_id' => $field['child_id'],
            'parent_key' => $field['parent_key'], 'child_key' => $field['child_key'],
            'key_name' => $field['relation']['key_name'] ?? null,
            'value_name' => $field['relation']['value_name'] ?? null,
            'read_path' => $field['read_path'],
            'max_files' => $field['max_files'], 'max_file_size' => $field['max_file_size'],
            'accepted_files' => $field['accepted_files'], 'min_rows' => $field['min_rows'],
            'max_rows' => $field['max_rows'], 'inputs' => $this->inputs($field['inputs']),
            'lat_field' => $field['lat_field'] ?? null, 'lng_field' => $field['lng_field'] ?? null,
            'allowed_groups' => $field['allowed_groups'] ?? [],
            'storage' => $field['storage'],
        ];
        if ($field['type'] === 'decimal') {
            $input['step'] = '0.01';
        }
        return array_filter($input, fn ($value) => $value !== null);
    }

    private function usesSelect2(array $definition): bool
    {
        foreach ([...$definition['fields'], ...$definition['edit_fields'], ...$definition['filters']] as $field) {
            if ($field['select2'] ?? false) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string, array<string, mixed>> */
    private function schemaFields(array $definition): array
    {
        $result = [];
        $fields = $definition['edit_mode'] === 'custom'
            ? [...$definition['fields'], ...$definition['edit_fields']]
            : $definition['fields'];
        foreach ($fields as $field) {
            if ($field['name'] === null) continue;
            $result[$field['name']] = $this->fieldDescriptor($field);
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function fieldDescriptor(array $field): array
    {
        return array_filter([
            'type' => $field['type'], 'input' => $field['input'],
                'lat_field' => $field['lat_field'] ?? null, 'lng_field' => $field['lng_field'] ?? null,
                'allowed_groups' => ($field['allowed_groups'] ?? []) ?: null,
            'inputs' => $field['inputs']
                ? array_map(fn (array $child) => ['id' => $child['name'], ...$this->fieldDescriptor($child)], $field['inputs'])
                : null,
        ], fn ($value) => $value !== null);
    }

    private function label(string $key, string $arabic, string $english, string $file): string
    {
        $translation = preg_match('/^(admin|inputs|buttons)\./', $key) ? $key : $file.'.'.$key;
        if (Lang::has($translation)) {
            return __($translation);
        }
        return app()->getLocale() === 'en' ? $english : $arabic;
    }

    private function route(string $name, mixed $parameter = null): string
    {
        if (!Route::has($name)) {
            return $name;
        }
        return route($name, $parameter === null ? [] : $parameter);
    }

    private function routePrefix(string $resource): string
    {
        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        return ($prefix === '' ? '' : $prefix.'.').$resource.'.';
    }
}
