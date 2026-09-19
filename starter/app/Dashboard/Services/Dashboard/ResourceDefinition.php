<?php

namespace App\Dashboard\Services\Dashboard;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** يحوّل بيانات المولّد إلى وصف موحّد، ويرفض أسماء الملفات والحقول غير الصالحة. */
final class ResourceDefinition
{
    public const TYPES = ['text', 'textarea', 'email', 'integer', 'decimal', 'boolean', 'date', 'datetime'];

    /**
     * يتحقق من أسماء الموديل والمورد والصلاحية ومن 1 إلى 40 حقلا، ثم يعيد وصفا موحدا.
     *
     * يرفض الحقول المحجوزة والمعرفات غير الصالحة؛ يحول الخيارات المنطقية إلى bool
     * ويعيد المفاتيح المعروفة فقط. لا يفحص وجود أعمدة في قاعدة بيانات أو يعدل مخططها.
     *
     * @param array<string, mixed> $input قيم واجهة المولّد أو ملف JSON قبل التطبيع.
     * @return array<string, mixed> model وresource وpermission وtitle وcreate_model وtimestamps وfields.
     * @throws ValidationException عند اسم أو نوع أو قيمة غير مقبولة.
     */
    public function normalize(array $input): array
    {
        if ((int) ($input['schema_version'] ?? 1) === 2) {
            return app(ResourceDefinitionV2::class)->normalize($input);
        }

        $data = Validator::make($input, [
            'model' => ['required', 'string', 'max:80', 'regex:/^[A-Z][A-Za-z0-9]*$/'],
            'resource' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'permission' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'title' => ['required', 'string', 'max:150'],
            'create_model' => ['required', 'boolean'],
            'timestamps' => ['required', 'boolean'],
            'fields' => ['required', 'array', 'min:1', 'max:40'],
            'fields.*.name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
            'fields.*.label' => ['required', 'string', 'max:150'],
            'fields.*.type' => ['required', Rule::in(self::TYPES)],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.in_table' => ['required', 'boolean'],
            'fields.*.filterable' => ['required', 'boolean'],
        ])->validate();

        try {
            token_get_all('<?php class '.$data['model'].' {}', TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw ValidationException::withMessages(['model' => 'اختر اسم Model لا يكون كلمة محجوزة في PHP.']);
        }
        // هذه أسماء أنواع أو aliases قد تقبلها مرحلة parsing لكن يرفضها تعريف الكلاس.
        if (in_array(strtolower($data['model']), ['model', 'self', 'parent', 'static', 'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void', 'iterable', 'object', 'resource', 'numeric', 'mixed', 'never'], true)) {
            throw ValidationException::withMessages(['model' => 'اسم الموديل يتعارض مع نوع PHP أو الكلاس الأساسي.']);
        }
        $fields = [];
        foreach ($data['fields'] as $index => $field) {
            if (in_array($field['name'], ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                throw ValidationException::withMessages(["fields.$index.name" => 'هذا الحقل تديره قاعدة البيانات ولا يضاف إلى النموذج.']);
            }
            $fields[] = [
                'name' => $field['name'], 'label' => $field['label'], 'type' => $field['type'],
                'required' => (bool) $field['required'], 'in_table' => (bool) $field['in_table'],
                'filterable' => (bool) $field['filterable'],
            ];
        }

        return [
            'model' => $data['model'], 'resource' => $data['resource'],
            'permission' => $data['permission'], 'title' => $data['title'],
            'create_model' => (bool) $data['create_model'], 'timestamps' => (bool) $data['timestamps'],
            'fields' => $fields,
        ];
    }

    /**
     * يحوّل نوع كل حقل وحالة required إلى قواعد Laravel التي تستخدمها عمليات الحفظ.
     *
     * النص القصير والبريد حتى 255 حرفا، textarea حتى 10000، وdecimal في نطاق decimal(12,2).
     * الدالة تبني القواعد فقط؛ Request::validate في الكنترولر ينفذها ويستبعد الحقول الإضافية.
     *
     * @param array<int, array<string, mixed>> $fields الحقول بعد normalize.
     * @return array<string, array<int, string>> اسم كل حقل وقواعد required أو nullable والنوع والحدود.
     */
    public function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $typeRules = match ($field['type']) {
                'text' => ['string', 'max:255'],
                'textarea' => ['string', 'max:10000'],
                'email' => ['email', 'max:255'],
                'integer' => ['integer'],
                'decimal' => ['numeric', 'between:-9999999999.99,9999999999.99'],
                'boolean' => ['boolean'],
                'date' => ['date_format:Y-m-d'],
                'datetime' => ['date'],
            };
            $rules[$field['name']] = [$field['required'] ? 'required' : 'nullable', ...$typeRules];
        }
        return $rules;
    }
}
