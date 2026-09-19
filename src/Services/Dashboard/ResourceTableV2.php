<?php

namespace ZeroOneZ\Dashboard\Services\Dashboard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Applies v2 DataTables search/filter/order only through definition-owned columns and relations. */
final class ResourceTableV2
{
    /** @return array<string, mixed> */
    public function response(Request $request, Builder $query, array $definition): array
    {
        $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:500'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'filters' => ['sometimes', 'array'],
            'filters.*' => ['nullable', 'string', 'max:1000'],
            'order' => ['sometimes', 'array'],
            'order.0.column' => ['sometimes', 'integer', 'min:0'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
        ]);

        $total = (clone $query)->count();
        $search = $request->input('search.value');
        $searchable = array_values(array_filter($definition['columns'], fn (array $column) => $column['searchable']));
        if ($search !== null && $search !== '' && $searchable !== []) {
            $query->where(function (Builder $nested) use ($definition, $searchable, $search): void {
                foreach ($searchable as $column) {
                    $this->applyConstraint($nested, $definition, $column['key'], 'contains', $search, 'or');
                }
            });
        }

        if ($definition['capabilities']['filters']) {
            foreach ($definition['filters'] as $filter) {
                $value = $request->input('filters.'.$filter['name']);
                if ($value !== null && $value !== '') {
                    $this->applyConstraint($query, $definition, $filter['target'], $filter['operator'], $value, 'and');
                }
            }
        }

        $filtered = (clone $query)->count();
        $display = [null, null, ['key' => 'id', 'sortable' => true], ...$definition['columns']];
        $selected = $display[(int) $request->input('order.0.column', 2)] ?? $display[2];
        $direction = $request->input('order.0.dir', 'desc');
        if (!is_array($selected) || !$selected['sortable'] || !$this->applyOrder($query, $definition, $selected['key'], $direction)) {
            $query->orderBy($query->getModel()->qualifyColumn($query->getModel()->getKeyName()), $direction);
        }

        $rows = $query->skip((int) $request->start)->take((int) $request->length)->get();
        return [
            'draw' => (int) $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn ($record) => app(ResourceRecordV2::class)->data($record, $definition))->all(),
        ];
    }

    private function applyConstraint(Builder $query, array $definition, string $target, string $operator, mixed $value, string $boolean): bool
    {
        $resolved = $this->resolve($definition, $target);
        if ($resolved === null) {
            return false;
        }
        $method = $boolean === 'or' ? 'orWhere' : 'where';
        $comparison = $operator === 'contains' ? 'like' : '=';
        $bound = $operator === 'contains' ? '%'.$value.'%' : $value;
        if ($resolved['relation'] === null) {
            $query->{$method}($query->getModel()->qualifyColumn($resolved['column']), $comparison, $bound);
            return true;
        }
        $relationMethod = $boolean === 'or' ? 'orWhereHas' : 'whereHas';
        $query->{$relationMethod}($resolved['relation']['name'], function (Builder $related) use ($resolved, $comparison, $bound): void {
            $related->where($resolved['column'], $comparison, $bound);
        });
        return true;
    }

    private function applyOrder(Builder $query, array $definition, string $target, string $direction): bool
    {
        if ($target === 'id') {
            $query->orderBy($query->getModel()->qualifyColumn($query->getModel()->getKeyName()), $direction);
            return true;
        }
        $resolved = $this->resolve($definition, $target);
        if ($resolved === null) {
            return false;
        }
        if ($resolved['relation'] === null) {
            $query->orderBy($query->getModel()->qualifyColumn($resolved['column']), $direction);
            return true;
        }

        $relation = $resolved['relation'];
        $class = rtrim((string) config('dashboard.generator.model_namespace', 'App\\Models'), '\\').'\\'.$relation['model'];
        if (!is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }
        $related = $class::query()
            ->select($resolved['column'])
            ->whereColumn($relation['owner_key'], $query->getModel()->qualifyColumn($relation['foreign_key']))
            ->limit(1);
        $query->orderBy($related, $direction);
        return true;
    }

    /** @return array{column:string,relation:?array<string,string>}|null */
    private function resolve(array $definition, string $target): ?array
    {
        foreach ([...$definition['fields'], ...$definition['edit_fields']] as $field) {
            if ($field['input'] === 'empty') {
                continue;
            }
            if ($target === $field['name'] || $target === $field['column']) {
                return ['column' => $field['column'], 'relation' => null];
            }
            $relation = $field['relation'] ?? null;
            if (is_array($relation) && str_starts_with($target, $relation['name'].'.')) {
                $column = substr($target, strlen($relation['name']) + 1);
                if (!str_contains($column, '.') && in_array($column, [$relation['value_name'], $relation['owner_key']], true)) {
                    return ['column' => $column, 'relation' => $relation];
                }
            }
        }
        return null;
    }
}
