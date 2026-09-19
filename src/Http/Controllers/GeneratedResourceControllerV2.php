<?php

namespace ZeroOneZ\Dashboard\Http\Controllers;

use Illuminate\Routing\Controller;
use ZeroOneZ\Dashboard\Http\Traits\AjaxResponseTrait;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourceDefinition;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourceDefinitionV2;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourcePageV2;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourceRecordV2;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourceTableV2;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LogicException;

/** Safe direct runtime for normalized v2 scalar, static-select and simple belongsTo resources. */
abstract class GeneratedResourceControllerV2 extends Controller
{
    use AjaxResponseTrait;

    /**
     * The route helper only reads this declared, generated capability allow-list.
     * Subclasses emitted by the builder override it; hand-written subclasses safely
     * inherit an empty list and therefore receive only the read routes.
     *
     * @var array<string, bool>
     */
    public const ROUTE_CAPABILITIES = [];

    private array $loadedDefinitions = [];
    private ?array $directDefinitionCache = null;

    /** @return array<string, mixed> */
    abstract protected function definition(): array;

    /** @return array<string, bool> */
    public static function routeCapabilities(): array
    {
        return static::ROUTE_CAPABILITIES;
    }

    /** @return array<string, mixed> */
    protected function loadDefinition(string $resource): array
    {
        return $this->loadedDefinitions[$resource] ??= app(ResourceDefinition::class)->normalize(json_decode(
            file_get_contents(base_path(trim((string) config('dashboard.generator.definition_path'), '/').'/'.$resource.'.json')),
            true,
            128,
            JSON_THROW_ON_ERROR
        ));
    }

    protected function model(): Builder
    {
        $definition = $this->directDefinition();
        $class = rtrim((string) config('dashboard.generator.model_namespace', 'App\\Models'), '\\').'\\'.$definition['model'];
        $query = $class::query();
        $relations = [];
        foreach ([...$definition['fields'], ...$definition['edit_fields']] as $field) {
            if (($field['storage']['strategy'] ?? null) === 'belongsTo' && isset($field['relation']['name'])) {
                $relations[] = $field['relation']['name'];
            }
        }
        return $relations === [] ? $query : $query->with(array_values(array_unique($relations)));
    }

    public function index()
    {
        return view(config('dashboard.view_namespace', 'zerooonez-dashboard').'::screen.table_view', ['data' => app(ResourcePageV2::class)->data($this->directDefinition())]);
    }

    public function inputs_list(): array
    {
        return app(ResourcePageV2::class)->inputs($this->directDefinition()['fields']);
    }

    public function update_inputs_list(): array
    {
        return app(ResourcePageV2::class)->updateInputs($this->directDefinition());
    }

    public function filters(): array
    {
        return app(ResourcePageV2::class)->filterList($this->directDefinition());
    }

    public function show_list(): array
    {
        return app(ResourcePageV2::class)->showList($this->directDefinition());
    }

    public function datatable_actions(): array
    {
        return app(ResourcePageV2::class)->actions($this->directDefinition());
    }

    public function modals(): array
    {
        return app(ResourcePageV2::class)->modals($this->directDefinition());
    }

    public function get_datatable(Request $request)
    {
        return response()->json(app(ResourceTableV2::class)->response($request, $this->model(), $this->directDefinition()));
    }

    public function get_single_item($id)
    {
        $this->requireCapability('update');
        $record = $this->model()->findOrFail($id);
        return $this->successResponse(app(ResourceRecordV2::class)->data($record, $this->directDefinition()));
    }

    public function store(Request $request)
    {
        $this->requireCapability('create');
        $definition = $this->directDefinition();
        $record = $this->model()->create($this->validatedStorageData($request, $definition['fields']));
        $record = $this->model()->findOrFail($record->getKey());
        return $this->successResponse(app(ResourceRecordV2::class)->data($record, $definition), 'success');
    }

    public function update(Request $request, $id)
    {
        $this->requireCapability('update');
        $definition = $this->directDefinition();
        $fields = $definition['edit_mode'] === 'custom' ? $definition['edit_fields'] : $definition['fields'];
        $record = $this->model()->findOrFail($id);
        $record->update($this->validatedStorageData($request, $fields, $record->getKeyName()));
        $record = $this->model()->findOrFail($id);
        return $this->successResponse(app(ResourceRecordV2::class)->data($record, $definition), 'success');
    }

    public function delete($id)
    {
        $this->requireCapability('delete');
        $this->model()->findOrFail($id)->delete();
        return $this->successResponse('success', 'success');
    }

    public function delete_all(Request $request)
    {
        $this->requireCapability('delete_all');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $query = $this->model();
        $query->getConnection()->transaction(function () use ($data): void {
            foreach ($data['ids'] as $id) {
                $this->model()->findOrFail($id)->delete();
            }
        });
        return $this->successResponse($data['ids'], 'success');
    }

    public function get_list(Request $request)
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $definition = $this->directDefinition();
        $label = collect($definition['fields'])->first(fn (array $field) =>
            $field['input'] !== 'empty' && in_array($field['type'], ['text', 'email'], true)
        );
        $column = $label['column'] ?? 'id';
        $query = $this->model();
        if (($data['search'] ?? '') !== '') {
            $query->where($query->getModel()->qualifyColumn($column), 'like', '%'.$data['search'].'%');
        }
        $records = $query->orderBy($query->getModel()->qualifyColumn($query->getModel()->getKeyName()))
            ->skip((($data['page'] ?? 1) - 1) * 10)->take(10)->get();
        return $this->successResponse($records->map(fn ($record) => [
            'id' => $record->getKey(), 'text' => (string) $record->getAttribute($column),
        ]), 'success');
    }

    /** @return array<string, mixed> */
    protected function validatedStorageData(Request $request, array $fields, ?string $keyName = 'id'): array
    {
        $validated = $request->validate(app(ResourceDefinitionV2::class)->rules($fields));
        $storage = [];
        foreach ($fields as $field) {
            if ($field['input'] === 'empty' || $field['read_only'] || $field['name'] === null
                || !array_key_exists($field['name'], $validated)) {
                continue;
            }
            $column = $field['column'];
            if ($column === $keyName || $field['name'] === 'id') {
                continue;
            }
            $storage[$column] = $validated[$field['name']];
        }
        return $storage;
    }

    /** @return array<string, mixed> */
    private function directDefinition(): array
    {
        if ($this->directDefinitionCache !== null) {
            return $this->directDefinitionCache;
        }
        $definition = app(ResourceDefinition::class)->normalize($this->definition());
        $reasons = app(ResourceDefinitionV2::class)->reasons($definition);
        if ($reasons !== []) {
            throw new LogicException('هذا التعريف يحتاج تنفيذ Agent ولا يمكن تشغيله عبر runtime المباشر: '.implode(' ', $reasons));
        }
        return $this->directDefinitionCache = $definition;
    }

    private function requireCapability(string $capability): void
    {
        abort_unless(($this->directDefinition()['capabilities'][$capability] ?? false) === true, 404);
    }
}
