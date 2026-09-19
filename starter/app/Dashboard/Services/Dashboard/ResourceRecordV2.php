<?php

namespace App\Dashboard\Services\Dashboard;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/** Projects only explicitly declared v2 fields, columns and relationship labels. */
final class ResourceRecordV2
{
    /** @return array<string, mixed> */
    public function data($record, array $definition): array
    {
        if (!$record instanceof Model) {
            throw new \InvalidArgumentException('ResourceRecordV2 expects an Eloquent model.');
        }

        $data = ['id' => $record->getKey()];
        $fields = $definition['edit_mode'] === 'custom'
            ? $this->mergeFields($definition['fields'], $definition['edit_fields'])
            : $definition['fields'];

        foreach ($fields as $field) {
            if (($field['input'] ?? null) === 'empty' || $field['name'] === null) {
                continue;
            }
            $value = $this->fieldValue($record, $field);
            $data[$field['name']] = $this->format($value, $field['type']);
            $this->addRelationProjection($data, $record, $field);
        }
        foreach ($definition['columns'] as $column) {
            if (!array_key_exists($column['key'], $data)) {
                Arr::set($data, $column['key'], $this->pathValue($record, $column['key'], $fields));
            }
        }
        return $data;
    }

    private function fieldValue(Model $record, array $field): mixed
    {
        $readPath = $field['read_path'] ?: $field['column'];
        if (!str_contains($readPath, '.')) {
            return $record->getAttribute($readPath);
        }
        return data_get($record, $readPath);
    }

    private function pathValue(Model $record, string $path, array $fields): mixed
    {
        foreach ($fields as $field) {
            if ($field['name'] === $path || $field['column'] === $path) {
                return $this->format($this->fieldValue($record, $field), $field['type']);
            }
        }
        return data_get($record, $path);
    }

    private function addRelationProjection(array &$data, Model $record, array $field): void
    {
        $relation = $field['relation'] ?? null;
        if (!is_array($relation) || ($field['storage']['strategy'] ?? null) !== 'belongsTo') {
            return;
        }
        $related = $record->getRelationValue($relation['name']);
        if (!$related instanceof Model) {
            return;
        }
        $projection = [
            'id' => $related->getAttribute($relation['owner_key']),
            $relation['owner_key'] => $related->getAttribute($relation['owner_key']),
        ];
        Arr::set($projection, $relation['value_name'], data_get($related, $relation['value_name']));
        Arr::set($data, $relation['key_name'], $projection);
    }

    private function format(mixed $value, string $type): mixed
    {
        if ($value === null || !in_array($type, ['date', 'datetime'], true)) {
            return $value;
        }
        $date = $value instanceof DateTimeInterface ? $value : Carbon::parse($value);
        return $date->format($type === 'date' ? 'Y-m-d' : 'Y-m-d\\TH:i');
    }

    /** @return list<array<string, mixed>> */
    private function mergeFields(array $create, array $edit): array
    {
        $fields = [];
        foreach ([...$create, ...$edit] as $field) {
            $key = ($field['input'] ?? null) === 'empty' ? 'empty:'.count($fields) : $field['name'];
            $fields[$key] = $field;
        }
        return array_values($fields);
    }
}
