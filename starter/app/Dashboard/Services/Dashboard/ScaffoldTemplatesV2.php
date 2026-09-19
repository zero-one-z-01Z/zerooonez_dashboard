<?php

namespace App\Dashboard\Services\Dashboard;

/** Pure source generation for the deliberately small automatic capability set. */
final class ScaffoldTemplatesV2
{
    public function files(array $d): array
    {
        $resource = var_export($d['resource'], true);
        $controller = $d['controller'];
        $routeCapabilities = var_export($d['capabilities'], true);
        $controllerPath = trim((string) config('dashboard.generator.controller_path'), '/');
        $controllerNamespace = (string) config('dashboard.generator.controller_namespace');
        $baseController = '\\'.ltrim((string) config('dashboard.generator.base_controller_v2'), '\\');
        $definitionPath = trim((string) config('dashboard.generator.definition_path'), '/');
        $modelPath = trim((string) config('dashboard.generator.model_path'), '/');
        $modelNamespace = (string) config('dashboard.generator.model_namespace');
        $files = [
            "{$controllerPath}/{$controller}.php" => "<?php\n\nnamespace {$controllerNamespace};\n\nuse Illuminate\\Http\\Request;\n\n/** Generated once; keep custom changes when updating this resource. */\nclass {$controller} extends {$baseController}\n{\n    /** @var array<string, bool> */\n    public const ROUTE_CAPABILITIES = {$routeCapabilities};\n\n    protected function definition(): array\n    {\n        return \$this->loadDefinition({$resource});\n    }\n\n    public function inputs_list(): array\n    {\n        return parent::inputs_list();\n    }\n\n    public function update_inputs_list(): array\n    {\n        return parent::update_inputs_list();\n    }\n\n    public function filters(): array\n    {\n        return parent::filters();\n    }\n\n    public function show_list(): array\n    {\n        return parent::show_list();\n    }\n\n    public function datatable_actions(): array\n    {\n        return parent::datatable_actions();\n    }\n\n    public function modals(): array\n    {\n        return parent::modals();\n    }\n\n    public function get_single_item(\$id)\n    {\n        return parent::get_single_item(\$id);\n    }\n\n    public function store(Request \$request)\n    {\n        return parent::store(\$request);\n    }\n\n    public function update(Request \$request, \$id)\n    {\n        return parent::update(\$request, \$id);\n    }\n\n    public function delete(\$id)\n    {\n        return parent::delete(\$id);\n    }\n}\n",
            "{$definitionPath}/{$d['resource']}.json" => json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
        ];
        if ($d['create_model']) {
            $fields = $this->storageFields($d);
            $fillable = var_export(array_values(array_map(fn ($f) => $f['column'] ?? $f['name'], array_filter($fields, fn ($f) => $f['_writable']))), true);
            $casts = [];
            $columns = ["            \$table->id();"];
            $relations = [];
            foreach ($fields as $f) {
                $column = $f['column'] ?? $f['name'];
                $type = $f['type'];
                $isRelation = ($f['select2'] ?? false) && ($f['input'] ?? '') === 'single';
                $method = $isRelation ? 'unsignedBigInteger' : match ($type) {
                    'textarea' => 'text', 'integer' => 'bigInteger', 'decimal' => 'decimal', 'boolean' => 'boolean', 'date' => 'date', 'datetime' => 'dateTime', default => 'string',
                };
                $args = var_export($column, true).($type === 'decimal' ? ', 12, 2' : '');
                $nullable = !$f['_required_on_insert'];
                $columns[] = "            \$table->{$method}({$args})".($nullable ? '->nullable()' : '').';';
                $cast = $isRelation ? 'integer' : match ($type) {'integer' => 'integer', 'decimal' => 'decimal:2', 'boolean' => 'boolean', 'date' => 'date:Y-m-d', 'datetime' => 'datetime:Y-m-d\\TH:i', default => null};
                if ($cast) $casts[$column] = $cast;
                if ($isRelation) {
                    $r = $f['relation'];
                    $name = $r['name'];
                    $foreign = var_export($column, true);
                    $owner = var_export($r['owner_key'] ?? 'id', true);
                    $relations[$name] = "    public function {$name}(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n    {\n        return \$this->belongsTo(\\{$modelNamespace}\\{$r['model']}::class, {$foreign}, {$owner});\n    }\n";
                }
            }
            if ($d['timestamps']) $columns[] = '            $table->timestamps();';
            $castCode = var_export($casts, true);
            $timestamps = $d['timestamps'] ? 'true' : 'false';
            $model = $d['model'];
            $files["{$modelPath}/{$model}.php"] = "<?php\n\nnamespace {$modelNamespace};\n\nclass {$model} extends \\Illuminate\\Database\\Eloquent\\Model\n{\n    protected \$table = {$resource};\n    protected \$fillable = {$fillable};\n    public \$timestamps = {$timestamps};\n\n    protected function casts(): array\n    {\n        return {$castCode};\n    }\n\n".implode("\n", $relations)."}\n";
            $files['database/migrations/'.gmdate('Y_m_d').'_000000_create_'.$d['resource'].'_table.php'] = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n    public function up(): void\n    {\n        Schema::create({$resource}, function (Blueprint \$table) {\n".implode("\n", $columns)."\n        });\n    }\n    public function down(): void\n    {\n        Schema::dropIfExists({$resource});\n    }\n};\n";
        }
        return $files;
    }

    public function routeBlock(array $d, ?string $controllerReference = null): string
    {
        $prefix = var_export($d['resource'], true);
        $name = var_export($d['resource'].'.', true);
        $permission = $d['permission'];
        $controllerReference ??= '\\'.trim((string) config('dashboard.generator.controller_namespace'), '\\').'\\'.$d['controller'];
        return "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n"
            ."Route::prefix({$prefix})->name({$name})->controller({$controllerReference}::class)\n"
            ."    ->middleware('can:view_{$permission}')->group(function (): void {\n"
            ."        all_routes('{$permission}');\n"
            ."    });\n";
    }

    private function storageFields(array $d): array
    {
        $fields = [];
        foreach ($d['fields'] as $f) {
            if (($f['input'] ?? '') === 'empty' || ($f['name'] ?? '') === 'id') continue;
            $f['_writable'] = !($f['read_only'] ?? false);
            $f['_required_on_insert'] = $f['_writable'] && ($f['validation']['required'] ?? false);
            $fields[$f['column'] ?? $f['name']] = $f;
        }
        if ($d['edit_mode'] === 'custom') foreach ($d['edit_fields'] as $f) {
            if (($f['input'] ?? '') === 'empty' || ($f['name'] ?? '') === 'id') continue;
            $column = $f['column'] ?? $f['name'];
            if (isset($fields[$column])) {
                $original = $fields[$column];
                if ($original['type'] !== $f['type'] || ($original['relation'] ?? []) !== ($f['relation'] ?? [])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['edit_fields' => 'نوع التخزين والعلاقة يجب أن يتطابقا في الإضافة والتعديل: '.$column]);
                }
                $fields[$column]['_writable'] = $original['_writable'] || !($f['read_only'] ?? false);
            } else {
                $f['_writable'] = !($f['read_only'] ?? false);
                $f['_required_on_insert'] = false;
                $fields[$column] = $f;
            }
        }
        return array_values($fields);
    }
}
