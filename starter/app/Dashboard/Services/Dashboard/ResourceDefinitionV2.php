<?php

namespace App\Dashboard\Services\Dashboard;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Normalizes the complete, lossless v2 builder contract without inspecting the database. */
final class ResourceDefinitionV2
{
    public const INPUTS = [
        'text', 'textarea', 'single', 'multi_select', 'switch', 'hidden', 'empty',
        'image', 'file', 'upload_images', 'image_preview', 'html', 'boundary', 'map',
        'multiform', 'permissions',
    ];

    public const TYPES = ['text', 'textarea', 'email', 'integer', 'decimal', 'boolean', 'date', 'datetime', 'json'];

    private const DIRECT_INPUTS = ['text', 'textarea', 'single', 'switch', 'hidden', 'empty'];

    /** @return array<string, mixed> */
    public function normalize(array $input): array
    {
        if ((int) ($input['schema_version'] ?? 0) !== 2) {
            throw ValidationException::withMessages(['schema_version' => 'تعريف v2 يجب أن يحمل schema_version=2.']);
        }

        $model = $this->identifier($input['model'] ?? null, 'model', '/^[A-Z][A-Za-z0-9]*$/', 80);
        $controller = $this->identifier($input['controller'] ?? ($model.'Controller'), 'controller', '/^[A-Z][A-Za-z0-9]*Controller$/', 100);
        $this->assertPhpClass($model, 'model');
        $this->assertPhpClass($controller, 'controller');
        $resource = $this->identifier($input['resource'] ?? null, 'resource', '/^[a-z][a-z0-9_]*$/', 64);
        $permission = $this->identifier($input['permission'] ?? null, 'permission', '/^[a-z][a-z0-9_]*$/', 64);

        $definition = [
            'schema_version' => 2,
            'model' => $model,
            'controller' => $controller,
            'resource' => $resource,
            'permission' => $permission,
            'title_key' => $this->key($input['title_key'] ?? $resource, 'title_key'),
            'title_ar' => $this->translated($input, 'title_ar', 'title', 'title_ar'),
            'title_en' => $this->translated($input, 'title_en', null, 'title_en'),
            'create_model' => $this->boolean($input['create_model'] ?? false, 'create_model'),
            'timestamps' => $this->boolean($input['timestamps'] ?? true, 'timestamps'),
            'page_type' => $this->enum($input['page_type'] ?? 'table', ['table', 'custom'], 'page_type'),
            'capabilities' => $this->capabilities((array) ($input['capabilities'] ?? []), $input),
            'edit_mode' => $this->enum($input['edit_mode'] ?? 'same', ['same', 'custom'], 'edit_mode'),
            'sidebar' => $this->sidebar((array) ($input['sidebar'] ?? [])),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];

        $definition['fields'] = $this->fields($input['fields'] ?? null, 'fields');
        if ($definition['fields'] === []) {
            throw ValidationException::withMessages(['fields' => 'أضف حقلًا واحدًا على الأقل.']);
        }
        if ($definition['edit_mode'] === 'custom') {
            $definition['edit_fields'] = $this->fields($input['edit_fields'] ?? null, 'edit_fields');
        } else {
            $definition['edit_fields'] = [];
        }
        $this->assertUniqueNames($definition['fields'], 'fields');
        $this->assertUniqueNames($definition['edit_fields'], 'edit_fields');
        $this->assertAcyclic($definition['fields']);
        $this->assertAcyclic($definition['edit_fields']);

        $definition['columns'] = $this->columns($input['columns'] ?? [], $definition['fields']);
        $definition['filters'] = $this->filters($input['filters'] ?? [], $definition['fields']);
        $definition['actions'] = $this->actions($input['actions'] ?? [], $definition);
        $definition['modals'] = $this->modals($input['modals'] ?? []);

        return $definition;
    }

    /** @return list<string> */
    public function reasons(array $definition): array
    {
        $reasons = [];
        if (($definition['page_type'] ?? 'table') !== 'table') {
            $reasons[] = 'الصفحات المخصصة تحتاج تنفيذ Agent.';
        }
        if (($definition['capabilities']['show'] ?? false) === true) {
            $reasons[] = 'صفحة التفاصيل تحتاج وجهة وتنفيذًا فعليًا بواسطة Agent.';
        }
        if (trim((string) ($definition['notes'] ?? '')) !== '') {
            $reasons[] = 'قواعد العمل الخاصة تحتاج تنفيذ Agent.';
        }

        foreach ([...($definition['fields'] ?? []), ...($definition['edit_fields'] ?? [])] as $field) {
            $input = $field['input'] ?? 'text';
            $strategy = $field['storage']['strategy'] ?? 'scalar';
            if (!in_array($input, self::DIRECT_INPUTS, true)) {
                $reasons[] = "الحقل {$field['name']} من النوع {$input} يحتاج تنفيذ Agent.";
            }
            if (!in_array($strategy, ['scalar', 'belongsTo'], true)) {
                $reasons[] = "استراتيجية {$strategy} للحقل {$field['name']} تحتاج تنفيذ Agent.";
            }
            if (($field['parent_id'] ?? null) || ($field['child_id'] ?? null)) {
                $reasons[] = "العلاقة المتسلسلة للحقل {$field['name']} تحتاج تنفيذ Agent.";
            }
            if ($input === 'single' && ($field['select2'] ?? false) && !$this->completeBelongsTo($field)) {
                $reasons[] = "علاقة الحقل {$field['name']} غير مكتملة للتوليد المباشر.";
            }
            if ($input === 'single' && !($field['select2'] ?? false) && ($field['items'] ?? []) === []) {
                $reasons[] = "الاختيارات الثابتة للحقل {$field['name']} فارغة.";
            }
            if ($strategy === 'belongsTo' && $input !== 'single') {
                $reasons[] = "علاقة belongsTo للحقل {$field['name']} تتطلب input=single.";
            }
            if (is_array($field['relation'] ?? null) && str_contains($field['relation']['value_name'], '.')) {
                $reasons[] = "مسار عرض العلاقة المتداخل للحقل {$field['name']} يحتاج تنفيذ Agent.";
            }
            if (($field['html_type'] ?? null) === 'password') {
                $reasons[] = "الحقل {$field['name']} سري ويحتاج حفظًا write-only مع hash أو تشفير بواسطة Agent.";
            }
            if ($input === 'permissions' && ($field['allowed_groups'] ?? []) === []) {
                $reasons[] = "حقل الصلاحيات {$field['name']} يحتاج allowed_groups صريحة.";
            }
            if (($field['read_path'] ?? null) !== null && $field['read_path'] !== $field['column']
                && !$this->directReadPath($definition, $field['read_path'])) {
                $reasons[] = "مسار القراءة {$field['read_path']} للحقل {$field['name']} مشتق وغير معلن للتنفيذ المباشر.";
            }
        }
        foreach (['fields', 'edit_fields'] as $collection) {
            $columns = array_values(array_map(fn (array $field) => $field['column'], array_filter(
                $definition[$collection] ?? [],
                fn (array $field) => $field['input'] !== 'empty' && !$field['read_only'] && $field['column'] !== null
            )));
            if (count($columns) !== count(array_unique($columns))) {
                $reasons[] = 'أكثر من حقل قابل للحفظ يستهدف عمود التخزين نفسه؛ يلزم Agent لحسم دورة الحفظ.';
            }
        }
        foreach ($definition['columns'] ?? [] as $column) {
            if (!$this->directTarget($definition, $column['key'])) {
                $reasons[] = "عمود الجدول {$column['key']} لا يطابق حقلًا أو علاقة مباشرة معرّفة.";
            }
        }
        foreach ($definition['filters'] ?? [] as $filter) {
            if (!$this->directTarget($definition, $filter['target'])) {
                $reasons[] = "هدف الفلتر {$filter['target']} لا يطابق حقلًا أو علاقة مباشرة معرّفة.";
            }
        }
        if (($definition['modals'] ?? []) !== []) {
            $reasons[] = 'المودالات الإضافية تحتاج تنفيذ Agent.';
        }
        foreach ($definition['actions'] ?? [] as $action) {
            if (!$this->isCanonicalAction($action, $definition)) {
                $reasons[] = 'الأكشنات المخصصة تحتاج تحقق Agent من الوجهة والصلاحية وشروط التنفيذ الخادمية.';
                break;
            }
        }

        return array_values(array_unique($reasons));
    }

    /** @return list<string> */
    public function permissions(array $definition): array
    {
        $base = $definition['permission'];
        $permissions = ['view_'.$base];
        foreach (['create', 'update', 'delete', 'export', 'show'] as $capability) {
            if (($definition['capabilities'][$capability] ?? false) === true) {
                $permissions[] = $capability.'_'.$base;
            }
        }
        foreach ([...($definition['actions'] ?? []), ...($definition['modals'] ?? [])] as $item) {
            if (($item['permission'] ?? '') !== '') {
                $permissions[] = $item['permission'];
            }
        }
        return array_values(array_unique($permissions));
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<string, array<int, mixed>>
     */
    public function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            if (($field['input'] ?? null) === 'empty' || ($field['read_only'] ?? false)) {
                continue;
            }
            $validation = $field['validation'] ?? [];
            $fieldRules = [($validation['required'] ?? false) ? 'required' : 'nullable'];
            $type = $field['type'] ?? 'text';
            $fieldRules = [...$fieldRules, ...match ($type) {
                'text' => ['string'],
                'textarea' => ['string'],
                'email' => ['email'],
                'integer' => ['integer'],
                'decimal' => ['numeric'],
                'boolean' => ['boolean'],
                'date' => ['date_format:Y-m-d'],
                'datetime' => ['date'],
                'json' => ['array'],
                default => [],
            }];
            if (isset($validation['min']) && in_array($type, ['text', 'textarea', 'email', 'integer', 'decimal'], true)) {
                $fieldRules[] = 'min:'.$validation['min'];
            }
            if (in_array($type, ['text', 'textarea', 'email'], true)) {
                $fieldRules[] = 'max:'.($validation['max'] ?? ($type === 'textarea' ? 10000 : 255));
            } elseif (isset($validation['max']) && in_array($type, ['integer', 'decimal'], true)) {
                $fieldRules[] = 'max:'.$validation['max'];
            }
            if (($field['input'] ?? null) === 'single' && !($field['select2'] ?? false)) {
                $fieldRules[] = Rule::in(array_column($field['items'] ?? [], 'value'));
            }
            if (($field['storage']['strategy'] ?? null) === 'belongsTo') {
                $fieldRules[] = $this->relatedExistsRule($field);
            }
            $rules[$field['name']] = $fieldRules;
        }
        return $rules;
    }

    /** @return array<string, bool> */
    private function capabilities(array $capabilities, array $input): array
    {
        $aliases = [
            'create' => 'have_add', 'update' => 'have_update', 'delete' => 'have_delete',
            'delete_all' => 'have_delete_all', 'export' => 'have_export',
            'filters' => 'have_filters', 'show' => 'have_show',
        ];
        $result = [];
        foreach ($aliases as $name => $alias) {
            $value = $capabilities[$name] ?? $input[$alias] ?? false;
            $result[$name] = $this->boolean($value, 'capabilities.'.$name);
        }
        if ($result['delete_all'] && !$result['delete']) {
            throw ValidationException::withMessages(['capabilities.delete_all' => 'الحذف الجماعي يتطلب تفعيل الحذف الفردي.']);
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function sidebar(array $sidebar): array
    {
        $mode = $this->enum($sidebar['mode'] ?? 'hidden', ['root', 'sub', 'hidden'], 'sidebar.mode');
        return [
            'mode' => $mode,
            'group' => trim((string) ($sidebar['group'] ?? '')),
            'group_ar' => trim((string) ($sidebar['group_ar'] ?? '')),
            'group_en' => trim((string) ($sidebar['group_en'] ?? '')),
            'icon' => trim((string) ($sidebar['icon'] ?? '')),
            'order' => max(0, (int) ($sidebar['order'] ?? 0)),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fields(mixed $rawFields, string $path): array
    {
        if (!is_array($rawFields) || !array_is_list($rawFields) || count($rawFields) > 80) {
            throw ValidationException::withMessages([$path => 'يجب إرسال قائمة حقول صحيحة لا تتجاوز 80 حقلًا.']);
        }
        $fields = [];
        foreach ($rawFields as $index => $raw) {
            if (!is_array($raw)) {
                throw ValidationException::withMessages(["$path.$index" => 'تعريف الحقل غير صالح.']);
            }
            $field = $this->field($raw, "$path.$index");
            if (($field['language'] ?? null) === 'both') {
                $baseName = preg_replace('/_(ar|en)$/', '', $field['name']);
                $baseColumn = preg_replace('/_(ar|en)$/', '', $field['column']);
                foreach (['ar', 'en'] as $language) {
                    $copy = $field;
                    $copy['name'] = $baseName.'_'.$language;
                    $copy['column'] = $baseColumn.'_'.$language;
                    $copy['language'] = $language;
                    $copy['label_key'] = preg_replace('/_(ar|en)$/', '', $field['label_key']).'_'.$language;
                    $copy['label_ar'] = $field['label_ar'].($language === 'ar' ? ' (عربي)' : ' (إنجليزي)');
                    $copy['label_en'] = $field['label_en'].($language === 'ar' ? ' (Arabic)' : ' (English)');
                    if (($copy['read_path'] ?? '') === $field['column']) {
                        $copy['read_path'] = $copy['column'];
                    }
                    $fields[] = $copy;
                }
            } else {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /** @return array<string, mixed> */
    private function field(array $raw, string $path): array
    {
        $input = $this->enum($raw['input'] ?? $this->inputForType((string) ($raw['type'] ?? 'text')), self::INPUTS, "$path.input");
        if ($input === 'empty') {
            return [
                'name' => null, 'column' => null, 'input' => 'empty',
                'type' => 'text', 'html_type' => 'text', 'label_key' => '', 'label_ar' => '', 'label_en' => '',
                'language' => 'neutral', 'validation' => ['required' => false, 'min' => null, 'max' => null],
                'width' => max(1, min(12, (int) ($raw['width'] ?? 12))), 'default' => null,
                'read_only' => true, 'in_table' => false, 'filterable' => false, 'items' => [],
                'select2' => false, 'route' => null, 'relation' => null,
                'parent_id' => null, 'child_id' => null, 'parent_key' => null, 'child_key' => null,
                'storage' => ['strategy' => 'scalar', 'relation' => null, 'disk' => null, 'directory' => null, 'deleted_key' => null],
                'max_files' => null, 'max_file_size' => null, 'accepted_files' => [],
                'min_rows' => null, 'max_rows' => null, 'inputs' => [], 'read_path' => null,
                'lat_field' => null, 'lng_field' => null,
                'allowed_groups' => [],
            ];
        }

        $name = $this->identifier($raw['name'] ?? $raw['id'] ?? null, "$path.name", '/^[a-z][a-z0-9_]*$/', 64);
        $validation = is_array($raw['validation'] ?? null) ? $raw['validation'] : [];
        if (array_key_exists('required', $raw) && array_key_exists('required', $validation)
            && $this->boolean($raw['required'], "$path.required") !== $this->boolean($validation['required'], "$path.validation.required")) {
            throw ValidationException::withMessages(["$path.validation.required" => 'تعارضت قيمتا required؛ استخدم validation.required كمصدر واحد.']);
        }
        $required = $this->boolean($validation['required'] ?? $raw['required'] ?? false, "$path.validation.required");
        $relation = $this->relation($raw['relation'] ?? null, "$path.relation");
        $storage = $this->storage($raw['storage'] ?? null, $relation, $input, "$path.storage");
        $column = (string) ($raw['column'] ?? ($storage['strategy'] === 'belongsTo' ? ($relation['foreign_key'] ?? $name) : $name));
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $column)) {
            throw ValidationException::withMessages(["$path.column" => 'اسم عمود التخزين غير صالح.']);
        }

        $type = $this->enum($raw['type'] ?? $this->typeForInput($input), self::TYPES, "$path.type");
        if ($input === 'switch' && $type !== 'boolean') {
            throw ValidationException::withMessages(["$path.type" => 'حقل switch يتطلب type=boolean.']);
        }

        $field = [
            'name' => $name,
            'column' => $column,
            'input' => $input,
            'type' => $type,
            'html_type' => $this->enum($raw['html_type'] ?? $this->htmlType($raw, $input), ['text', 'email', 'number', 'date', 'datetime-local', 'tel', 'url', 'password'], "$path.html_type"),
            'label_key' => $this->key($raw['label_key'] ?? $name, "$path.label_key"),
            'label_ar' => $this->translatedOptional($raw, 'label_ar', 'label'),
            'label_en' => $this->translatedOptional($raw, 'label_en', null),
            'language' => $this->enum($raw['language'] ?? 'neutral', ['neutral', 'ar', 'en', 'both'], "$path.language"),
            'validation' => [
                'required' => $required,
                'min' => $this->nullableNumber($validation['min'] ?? $raw['min'] ?? null, "$path.validation.min"),
                'max' => $this->nullableNumber($validation['max'] ?? $raw['max'] ?? null, "$path.validation.max"),
            ],
            'width' => max(1, min(12, (int) ($raw['width'] ?? 6))),
            'default' => $input === 'switch'
                ? $this->switchDefault($raw['default'] ?? false, "$path.default")
                : ($raw['default'] ?? null),
            'read_only' => $this->boolean($raw['read_only'] ?? $raw['readonly'] ?? false, "$path.read_only"),
            'in_table' => $this->boolean($raw['in_table'] ?? false, "$path.in_table"),
            'filterable' => $this->boolean($raw['filterable'] ?? false, "$path.filterable"),
            'items' => $this->items($raw['items'] ?? [], "$path.items"),
            'select2' => $this->boolean($raw['select2'] ?? false, "$path.select2"),
            'route' => $this->nullableRoute($raw['route'] ?? $raw['url'] ?? null, "$path.route"),
            'relation' => $relation,
            'parent_id' => $this->nullableIdentifier($raw['parent_id'] ?? null, "$path.parent_id"),
            'child_id' => $this->nullableIdentifier($raw['child_id'] ?? null, "$path.child_id"),
            'parent_key' => $this->nullableIdentifier($raw['parent_key'] ?? null, "$path.parent_key"),
            'child_key' => $this->nullableIdentifier($raw['child_key'] ?? null, "$path.child_key"),
            'storage' => $storage,
            'max_files' => $this->nullableInteger($raw['max_files'] ?? null, "$path.max_files"),
            'max_file_size' => $this->nullableInteger($raw['max_file_size'] ?? null, "$path.max_file_size"),
            'accepted_files' => $this->stringList($raw['accepted_files'] ?? []),
            'min_rows' => $this->nullableInteger($raw['min_rows'] ?? null, "$path.min_rows"),
            'max_rows' => $this->nullableInteger($raw['max_rows'] ?? null, "$path.max_rows"),
            'inputs' => [],
            'read_path' => trim((string) ($raw['read_path'] ?? $column)),
            'lat_field' => $this->nullableIdentifier($raw['lat_field'] ?? $raw['lat'] ?? null, "$path.lat_field"),
            'lng_field' => $this->nullableIdentifier($raw['lng_field'] ?? $raw['lng'] ?? null, "$path.lng_field"),
            'allowed_groups' => $this->identifierList($raw['allowed_groups'] ?? [], "$path.allowed_groups"),
        ];
        if (isset($raw['inputs'])) {
            $field['inputs'] = $this->fields($raw['inputs'], "$path.inputs");
        }
        return $field;
    }

    /** @return list<array<string, mixed>> */
    private function columns(mixed $rawColumns, array $fields): array
    {
        if ($rawColumns === [] || $rawColumns === null) {
            return array_values(array_map(fn (array $field) => [
                'key' => $field['name'], 'label_key' => $field['label_key'], 'label_ar' => $field['label_ar'],
                'label_en' => $field['label_en'], 'type' => $field['input'] === 'switch' ? 'switch' : 'text',
                'searchable' => in_array($field['type'], ['text', 'textarea', 'email'], true), 'sortable' => true,
            ], array_filter($fields, fn (array $field) => $field['in_table'])));
        }
        $this->assertList($rawColumns, 'columns');
        $result = [];
        foreach ($rawColumns as $index => $raw) {
            $field = $this->fieldForKey($fields, (string) ($raw['key'] ?? ''));
            $result[] = [
                'key' => $this->path((string) ($raw['key'] ?? ''), "columns.$index.key"),
                'label_key' => $this->key($raw['label_key'] ?? $field['label_key'] ?? '', "columns.$index.label_key"),
                // Leave a locale blank when its key already exists in the project's language file.
                // ScaffoldGeneratorV2 resolves that value before it writes any file.
                'label_ar' => $this->translatedOptional($raw, 'label_ar', null, $field['label_ar'] ?? ''),
                'label_en' => $this->translatedOptional($raw, 'label_en', null, $field['label_en'] ?? ''),
                'type' => trim((string) ($raw['type'] ?? 'text')),
                'searchable' => $this->boolean($raw['searchable'] ?? false, "columns.$index.searchable"),
                'sortable' => $this->boolean($raw['sortable'] ?? false, "columns.$index.sortable"),
            ];
        }
        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function filters(mixed $rawFilters, array $fields): array
    {
        if ($rawFilters === [] || $rawFilters === null) {
            return array_values(array_map(function (array $field): array {
                $filter = $field;
                $filter['operator'] = in_array($field['type'], ['text', 'textarea', 'email'], true) ? 'contains' : 'equals';
                $filter['target'] = $field['column'];
                $filter['audience'] = 'all';
                return $filter;
            }, array_filter($fields, fn (array $field) => $field['filterable'])));
        }
        $this->assertList($rawFilters, 'filters');
        $result = [];
        foreach ($rawFilters as $index => $raw) {
            $filter = $this->field($raw, "filters.$index");
            $filter['operator'] = $this->enum($raw['operator'] ?? 'equals', ['equals', 'contains'], "filters.$index.operator");
            $filter['target'] = $this->path((string) ($raw['target'] ?? $filter['column']), "filters.$index.target");
            $filter['audience'] = $this->enum($raw['audience'] ?? 'all', ['admin', 'all'], "filters.$index.audience");
            $result[] = $filter;
        }
        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function actions(mixed $rawActions, array $definition): array
    {
        $this->assertList($rawActions, 'actions');
        $result = [];
        foreach ($rawActions as $index => $raw) {
            $conditions = [];
            $this->assertList($raw['if'] ?? [], "actions.$index.if");
            foreach ($raw['if'] ?? [] as $conditionIndex => $condition) {
                $conditions[] = ['key' => $this->path((string) ($condition['key'] ?? ''), "actions.$index.if.$conditionIndex.key"), 'value' => $condition['value'] ?? null];
            }
            $result[] = [
                'name_key' => $this->key($raw['name_key'] ?? ($raw['operation'] ?? 'action'), "actions.$index.name_key"),
                'name_ar' => $this->translated($raw, 'name_ar', 'name', "actions.$index.name_ar"),
                'name_en' => $this->translated($raw, 'name_en', null, "actions.$index.name_en"),
                'type' => $this->enum($raw['type'] ?? 'link', ['link', 'modal', 'delete', 'custom_modal'], "actions.$index.type"),
                'route' => $this->nullableRoute($raw['route'] ?? null, "actions.$index.route"),
                'link' => trim((string) ($raw['link'] ?? '')),
                'form_id' => trim((string) ($raw['form_id'] ?? '')),
                'icon' => trim((string) ($raw['icon'] ?? '')),
                'value' => trim((string) ($raw['value'] ?? 'id')),
                'blank' => $this->boolean($raw['blank'] ?? false, "actions.$index.blank"),
                'permission' => trim((string) ($raw['permission'] ?? '')),
                'onclick' => $this->enum($raw['onclick'] ?? '', ['', 'edit_item', 'delete_item', 'fill_form'], "actions.$index.onclick"),
                'if' => $conditions,
                'operation' => trim((string) ($raw['operation'] ?? '')),
                'pending' => $this->boolean($raw['pending'] ?? false, "actions.$index.pending"),
            ];
        }
        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function modals(mixed $rawModals): array
    {
        $this->assertList($rawModals, 'modals');
        $result = [];
        foreach ($rawModals as $index => $raw) {
            $result[] = [
                'id' => $this->identifier($raw['id'] ?? null, "modals.$index.id", '/^[a-z][a-z0-9_-]*$/', 64),
                'form_id' => $this->identifier($raw['form_id'] ?? null, "modals.$index.form_id", '/^[a-z][a-z0-9_-]*$/', 64),
                'title_key' => $this->key($raw['title_key'] ?? $raw['id'] ?? '', "modals.$index.title_key"),
                'title_ar' => $this->translated($raw, 'title_ar', 'title', "modals.$index.title_ar"),
                'title_en' => $this->translated($raw, 'title_en', null, "modals.$index.title_en"),
                'route' => $this->nullableRoute($raw['route'] ?? null, "modals.$index.route"),
                'method' => $this->enum(strtoupper((string) ($raw['method'] ?? 'POST')), ['POST', 'PUT', 'PATCH', 'DELETE'], "modals.$index.method"),
                'permission' => trim((string) ($raw['permission'] ?? '')),
                'operation' => trim((string) ($raw['operation'] ?? '')),
                'inputs' => $this->fields($raw['inputs'] ?? [], "modals.$index.inputs"),
            ];
        }
        return $result;
    }

    /** @return list<array{value:mixed,text_ar:string,text_en:string,selected:bool}> */
    private function items(mixed $items, string $path): array
    {
        $this->assertList($items, $path);
        $result = [];
        $selected = 0;
        foreach ($items as $index => $item) {
            if (!is_array($item) || !array_key_exists('value', $item)) {
                throw ValidationException::withMessages(["$path.$index" => 'كل اختيار يحتاج value.']);
            }
            $isSelected = $this->boolean($item['selected'] ?? false, "$path.$index.selected");
            $selected += $isSelected ? 1 : 0;
            $result[] = [
                'value' => $item['value'],
                'text_ar' => $this->translated($item, 'text_ar', 'text', "$path.$index.text_ar"),
                'text_en' => $this->translated($item, 'text_en', null, "$path.$index.text_en"),
                'selected' => $isSelected,
            ];
        }
        $encodedValues = array_map(fn (array $item) => json_encode($item['value'], JSON_THROW_ON_ERROR), $result);
        if (count($encodedValues) !== count(array_unique($encodedValues))) {
            throw ValidationException::withMessages([$path => 'قيم الاختيارات يجب ألا تتكرر.']);
        }
        if ($selected > 1) {
            throw ValidationException::withMessages([$path => 'يمكن تحديد قيمة افتراضية واحدة فقط.']);
        }
        return $result;
    }

    /** @return array<string, string>|null */
    private function relation(mixed $relation, string $path): ?array
    {
        if ($relation === null || $relation === [] || (is_array($relation)
            && trim((string) ($relation['name'] ?? '')) === ''
            && trim((string) ($relation['model'] ?? '')) === ''
            && trim((string) ($relation['foreign_key'] ?? '')) === '')) {
            return null;
        }
        if (!is_array($relation)) {
            throw ValidationException::withMessages([$path => 'تعريف العلاقة غير صالح.']);
        }
        $model = $this->identifier($relation['model'] ?? null, "$path.model", '/^[A-Z][A-Za-z0-9]*$/', 80);
        $foreign = $this->identifier($relation['foreign_key'] ?? null, "$path.foreign_key", '/^[a-z][a-z0-9_]*$/', 64);
        $name = $this->identifier($relation['name'] ?? null, "$path.name", '/^[a-z][A-Za-z0-9_]*$/', 64);
        $keyName = trim((string) ($relation['key_name'] ?? ''));
        if ($keyName === '' || $keyName === 'id') {
            $keyName = $name;
        }
        return [
            'name' => $name,
            'model' => $model,
            'foreign_key' => $foreign,
            'owner_key' => $this->identifier($relation['owner_key'] ?? 'id', "$path.owner_key", '/^[a-z][a-z0-9_]*$/', 64),
            'value_name' => $this->path((string) ($relation['value_name'] ?? 'name'), "$path.value_name"),
            'key_name' => $this->path($keyName, "$path.key_name"),
            'table' => $this->identifier($relation['table'] ?? Str::snake(Str::pluralStudly($model)), "$path.table", '/^[a-z][a-z0-9_]*$/', 64),
        ];
    }

    /** @return array<string, mixed> */
    private function storage(mixed $storage, ?array $relation, string $input, string $path): array
    {
        $storage = is_array($storage) ? $storage : [];
        $default = $relation && $input === 'single' ? 'belongsTo' : match ($input) {
            'multi_select' => 'pivot', 'multiform' => 'children',
            'image', 'file', 'upload_images', 'image_preview' => 'media',
            'map', 'boundary', 'html', 'permissions' => 'custom', default => 'scalar',
        };
        return [
            'strategy' => $this->enum($storage['strategy'] ?? $default, ['scalar', 'belongsTo', 'pivot', 'json', 'children', 'media', 'custom'], "$path.strategy"),
            'relation' => trim((string) ($storage['relation'] ?? $relation['name'] ?? '')) ?: null,
            'disk' => trim((string) ($storage['disk'] ?? '')) ?: null,
            'directory' => trim((string) ($storage['directory'] ?? '')) ?: null,
            'deleted_key' => trim((string) ($storage['deleted_key'] ?? '')) ?: null,
        ];
    }

    private function relatedExistsRule(array $field): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($field): void {
            if ($value === null || $value === '') {
                return;
            }
            $class = rtrim((string) config('dashboard.generator.model_namespace', 'App\\Models'), '\\').'\\'.$field['relation']['model'];
            if (!is_subclass_of($class, Model::class)
                || !$class::query()->where($field['relation']['owner_key'], $value)->exists()) {
                $fail('الاختيار المحدد في :attribute غير موجود أو غير متاح.');
            }
        };
    }

    private function completeBelongsTo(array $field): bool
    {
        return ($field['storage']['strategy'] ?? null) === 'belongsTo'
            && ($field['route'] ?? null) !== null && is_array($field['relation'] ?? null);
    }

    private function directTarget(array $definition, string $target): bool
    {
        if ($target === 'id') {
            return true;
        }
        foreach ([...($definition['fields'] ?? []), ...($definition['edit_fields'] ?? [])] as $field) {
            if (($field['input'] ?? null) === 'empty') {
                continue;
            }
            if ($target === $field['name'] || $target === $field['column']) {
                return true;
            }
            $relation = $field['relation'] ?? null;
            if (is_array($relation) && in_array($target, [
                $relation['name'].'.'.$relation['owner_key'],
                $relation['name'].'.'.$relation['value_name'],
            ], true)) {
                return true;
            }
        }
        return false;
    }

    private function directReadPath(array $definition, string $path): bool
    {
        if ($path === 'id') {
            return true;
        }
        foreach ([...($definition['fields'] ?? []), ...($definition['edit_fields'] ?? [])] as $field) {
            if (($field['input'] ?? null) === 'empty') {
                continue;
            }
            if ($path === $field['column']) {
                return true;
            }
            $relation = $field['relation'] ?? null;
            if (is_array($relation) && in_array($path, [
                $relation['name'].'.'.$relation['owner_key'],
                $relation['name'].'.'.$relation['value_name'],
            ], true)) {
                return true;
            }
        }
        return false;
    }

    private function assertUniqueNames(array $fields, string $path): void
    {
        $seen = [];
        foreach ($fields as $field) {
            if ($field['input'] === 'empty') {
                continue;
            }
            if (isset($seen[$field['name']])) {
                throw ValidationException::withMessages([$path => 'أسماء حقول الطلب يجب ألا تتكرر داخل النموذج.']);
            }
            $seen[$field['name']] = true;
        }
    }

    private function assertAcyclic(array $fields): void
    {
        $edges = [];
        foreach ($fields as $field) {
            if ($field['parent_id']) {
                $edges[$field['parent_id']][] = $field['name'];
            }
            if ($field['child_id']) {
                $edges[$field['name']][] = $field['child_id'];
            }
        }
        $visiting = [];
        $visited = [];
        $walk = function (string $node) use (&$walk, &$visiting, &$visited, $edges): void {
            if (isset($visiting[$node])) {
                throw ValidationException::withMessages(['fields' => 'العلاقات المتسلسلة تحتوي دورة غير مسموح بها.']);
            }
            if (isset($visited[$node])) {
                return;
            }
            $visiting[$node] = true;
            foreach ($edges[$node] ?? [] as $next) {
                $walk($next);
            }
            unset($visiting[$node]);
            $visited[$node] = true;
        };
        foreach (array_keys($edges) as $node) {
            $walk($node);
        }
    }

    private function inputForType(string $type): string
    {
        return match ($type) { 'textarea' => 'textarea', 'boolean' => 'switch', default => 'text' };
    }

    private function typeForInput(string $input): string
    {
        return match ($input) { 'textarea', 'html' => 'textarea', 'switch' => 'boolean',
            'multi_select', 'upload_images', 'boundary', 'map', 'multiform', 'permissions' => 'json', default => 'text' };
    }

    private function htmlType(array $raw, string $input): string
    {
        if ($input !== 'text') {
            return 'text';
        }
        return match ($raw['type'] ?? 'text') {
            'email' => 'email', 'integer', 'decimal' => 'number', 'date' => 'date',
            'datetime' => 'datetime-local', default => 'text',
        };
    }

    private function fieldForKey(array $fields, string $key): ?array
    {
        $first = explode('.', $key)[0];
        foreach ($fields as $field) {
            if ($field['name'] === $key || $field['column'] === $key || ($field['relation']['name'] ?? null) === $first) {
                return $field;
            }
        }
        return null;
    }

    private function translated(array $data, string $key, ?string $alias, string $path, ?string $default = null): string
    {
        $value = $data[$key] ?? ($alias !== null ? ($data[$alias] ?? null) : null) ?? $default;
        if (!is_string($value) || trim($value) === '') {
            throw ValidationException::withMessages([$path => 'الترجمة مطلوبة.']);
        }
        return mb_substr(trim($value), 0, 150);
    }

    /** Field/column values may be resolved from an existing locale file during preview. */
    private function translatedOptional(array $data, string $key, ?string $alias, string $default = ''): string
    {
        $value = $data[$key] ?? ($alias !== null ? ($data[$alias] ?? null) : null) ?? $default;
        return is_string($value) ? mb_substr(trim($value), 0, 150) : '';
    }

    private function isCanonicalAction(array $action, array $definition): bool
    {
        $permission = $definition['permission'];
        $resource = $definition['resource'];
        if (($action['operation'] ?? '') === 'show' && ($action['pending'] ?? false)) {
            $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
            return $action['type'] === 'link' && $action['route'] === ($prefix === '' ? '' : $prefix.'.').$resource.'.show'
                && $action['permission'] === 'view_'.$permission && $action['if'] === [];
        }
        if (($action['operation'] ?? '') === 'edit') {
            return $action['type'] === 'modal' && $action['link'] === '#edit_modal'
                && $action['form_id'] === '#edit_form' && $action['permission'] === 'update_'.$permission
                && $action['onclick'] === 'edit_item' && $action['if'] === [];
        }
        if (($action['operation'] ?? '') === 'delete') {
            $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
            return $action['type'] === 'delete' && $action['route'] === ($prefix === '' ? '' : $prefix.'.').$resource.'.delete'
                && $action['permission'] === 'delete_'.$permission && $action['onclick'] === 'delete_item'
                && $action['if'] === [];
        }
        return false;
    }

    private function key(mixed $value, string $path): string
    {
        if (!is_string($value) || !preg_match('/^[a-z][a-z0-9_.-]*$/', $value)) {
            throw ValidationException::withMessages([$path => 'مفتاح الترجمة غير صالح.']);
        }
        return $value;
    }

    private function identifier(mixed $value, string $path, string $regex, int $max): string
    {
        if (!is_string($value) || strlen($value) > $max || !preg_match($regex, $value)) {
            throw ValidationException::withMessages([$path => 'المعرف غير صالح.']);
        }
        return $value;
    }

    private function assertPhpClass(string $value, string $path): void
    {
        try {
            token_get_all('<?php class '.$value.' {}', TOKEN_PARSE);
        } catch (\ParseError) {
            throw ValidationException::withMessages([$path => 'الاسم يتعارض مع كلمة محجوزة في PHP.']);
        }
        if ($path === 'model' && in_array(strtolower($value), [
            'model', 'self', 'parent', 'static', 'int', 'float', 'bool', 'string', 'true', 'false',
            'null', 'void', 'iterable', 'object', 'resource', 'numeric', 'mixed', 'never',
        ], true)) {
            throw ValidationException::withMessages([$path => 'اسم الموديل يتعارض مع نوع PHP أو الكلاس الأساسي.']);
        }
    }

    private function nullableIdentifier(mixed $value, string $path): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->identifier($value, $path, '/^[a-z][a-z0-9_]*$/', 64);
    }

    private function path(string $value, string $path): string
    {
        if ($value === '' || !preg_match('/^[a-z][A-Za-z0-9_]*(?:\.[a-z][A-Za-z0-9_]*)*$/', $value)) {
            throw ValidationException::withMessages([$path => 'مسار القراءة غير صالح.']);
        }
        return $value;
    }

    private function nullableRoute(mixed $value, string $path): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) || !preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
            throw ValidationException::withMessages([$path => 'استخدم اسم Route فقط، وليس URL حرًا.']);
        }
        return $value;
    }

    private function enum(mixed $value, array $allowed, string $path): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([$path => 'القيمة غير مدعومة.']);
        }
        return $value;
    }

    private function boolean(mixed $value, string $path): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (in_array($value, [0, 1, '0', '1'], true)) {
            return (bool) $value;
        }
        throw ValidationException::withMessages([$path => 'القيمة يجب أن تكون منطقية.']);
    }

    private function nullableNumber(mixed $value, string $path): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw ValidationException::withMessages([$path => 'القيمة يجب أن تكون رقمية.']);
        }
        return $value + 0;
    }

    private function switchDefault(mixed $value, string $path): bool
    {
        if (in_array($value, [true, 1, '1', 'true'], true)) {
            return true;
        }
        if (in_array($value, [false, 0, '0', 'false'], true)) {
            return false;
        }
        throw ValidationException::withMessages([$path => 'القيمة الافتراضية لحقل switch يجب أن تكون true أو false أو 1 أو 0.']);
    }

    private function nullableInteger(mixed $value, string $path): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            throw ValidationException::withMessages([$path => 'القيمة يجب أن تكون عددًا صحيحًا موجبًا.']);
        }
        return (int) $value;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_map('strval', $value));
    }

    /** @return list<string> */
    private function identifierList(mixed $value, string $path): array
    {
        $items = $this->stringList($value);
        foreach ($items as $index => $item) {
            $this->identifier($item, "$path.$index", '/^[a-z][a-z0-9_]*$/', 64);
        }
        return array_values(array_unique($items));
    }

    private function assertList(mixed $value, string $path): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw ValidationException::withMessages([$path => 'القيمة يجب أن تكون قائمة.']);
        }
    }
}
