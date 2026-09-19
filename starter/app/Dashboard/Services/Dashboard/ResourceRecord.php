<?php

namespace App\Dashboard\Services\Dashboard;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** يعرض الحقول المصرّح بها فقط، حتى إذا كان الموديل يخفيها عن استجابات API العامة. */
final class ResourceRecord
{
    /**
     * يقرأ id والحقول المصرّح بها مع casts/accessors الموديل ويضبط التواريخ للنماذج.
     *
     * استخدام getAttribute مقصود: الحقل المعرّف قد يكون hidden في API، بينما بقية الحقول
     * المخفية وappends غير المعرّفة لا تدخل الرد. يحفظ null كما هو دون تحويله إلى تاريخ.
     *
     * @param Model $record سجل Eloquent الجاري عرضه فقط؛ لا يغيره أو يحفظه.
     * @param array<int, array<string, mixed>> $fields قائمة حقول المورد وأنواعها.
     * @return array<string, mixed> id والحقول؛ date بصيغة Y-m-d وdatetime بصيغة Y-m-d\TH:i.
     */
    public function data(Model $record, array $fields): array
    {
        $data = ['id' => $record->getKey()];
        foreach ($fields as $field) {
            $value = $record->getAttribute($field['name']);
            if ($value !== null && in_array($field['type'], ['date', 'datetime'], true)) {
                $date = $value instanceof DateTimeInterface ? $value : Carbon::parse($value);
                $value = $date->format($field['type'] === 'date' ? 'Y-m-d' : 'Y-m-d\\TH:i');
            }
            $data[$field['name']] = $value;
        }
        return $data;
    }
}
