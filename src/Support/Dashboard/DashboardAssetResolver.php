<?php

namespace ZeroOneZ\Dashboard\Support\Dashboard;

/** يستنتج مكتبات الواجهة المطلوبة من تعريف الحقول الحالي دون نقل منطق العمل إلى JSON. */
class DashboardAssetResolver
{
    /** @return array<int, string> */
    public static function resolve(array $data): array
    {
        $assets = [];
        if (($data['datatable'] ?? false) === true) $assets[] = 'datatable';
        if (($data['daterange_filter'] ?? false) === true) $assets[] = 'daterangepicker';

        $fields = array_merge(
            self::fieldList($data['filters'] ?? []),
            self::fieldList($data['inputs'] ?? []),
            self::fieldList($data['update_inputs'] ?? []),
            self::modalFields($data['modals'] ?? [])
        );
        self::scanFields($fields, $assets);
        return array_values(array_unique($assets));
    }

    /** @param array<int, mixed> $fields @param array<int, string> $assets */
    private static function scanFields(array $fields, array &$assets): void
    {
        foreach ($fields as $field) {
            if (!is_array($field)) continue;
            if (($field['select2'] ?? false) === true) $assets[] = 'select2';
            $type = $field['input'] ?? null;
            if ($type === 'upload_images') $assets[] = 'dropzone';
            elseif ($type === 'html') $assets[] = 'quill';
            elseif ($type === 'map') $assets[] = 'googleMaps';
            elseif ($type === 'boundary') $assets[] = 'leaflet';
            if ($type === 'multiform') self::scanFields($field['inputs'] ?? [], $assets);
        }
    }

    /** يحول القيمة إلى قائمة حقول صالحة ويتجاهل stubs أو metadata غير المصفوفية. */
    private static function fieldList(mixed $fields): array
    {
        return is_array($fields) ? $fields : [];
    }

    /** يجمع حقول النوافذ سواء أُرسلت كقائمة نوافذ أو نافذة واحدة. */
    private static function modalFields(mixed $modals): array
    {
        if (!is_array($modals)) return [];
        if (array_key_exists('inputs', $modals)) return self::fieldList($modals['inputs']);

        $fields = [];
        foreach ($modals as $modal) {
            if (!is_array($modal)) continue;
            $fields = array_merge($fields, self::fieldList($modal['inputs'] ?? []));
        }
        return $fields;
    }

    /** يضيف أعلام Blade القديمة بجانب قائمة assets لضمان التوافق مع الصفحات الحالية. */
    public static function withCompatibilityFlags(array $data): array
    {
        $data['assets'] = self::resolve($data);
        foreach (['dropzone' => 'dropzone', 'quill' => 'html_editor', 'googleMaps' => 'map', 'leaflet' => 'boundary'] as $asset => $flag) {
            if (in_array($asset, $data['assets'], true)) $data[$flag] = true;
        }
        return $data;
    }
}
