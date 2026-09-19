<?php

namespace App\Dashboard\Services\Dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Throwable;

/** Metadata only: never reads application records or invokes arbitrary relation methods. */
final class BuilderCatalog
{
    public const GROUPS = ['users', 'scan', 'scan_times', 'customer_service', 'zones', 'cars', 'faqs', 'coupons', 'tickets'];

    public function data(?string $model = null): array
    {
        $modelPath = trim((string) config('dashboard.generator.model_path', 'app/Models'), '/');
        $models = array_map(fn ($path) => basename($path, '.php'), glob(base_path($modelPath.'/*.php')) ?: []);
        sort($models);
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->getName() ?? '', (string) config('dashboard.route_name_prefix', 'admin.'))) $routes[] = $route->getName();
        }
        sort($routes);
        return ['models' => $models, 'routes' => array_values(array_unique($routes)),
            'groups' => array_map(fn ($id) => ['id' => $id, 'label' => __('admin.'.$id)], self::GROUPS),
            'translations' => ['admin' => ['ar' => trans('admin', [], 'ar'), 'en' => trans('admin', [], 'en')], 'inputs' => ['ar' => trans('inputs', [], 'ar'), 'en' => trans('inputs', [], 'en')]],
            'model' => $model ? $this->inspect($model) : null];
    }

    public function inspect(string $name): array
    {
        $result = ['name' => $name, 'table' => null, 'columns' => [], 'fillable' => [], 'casts' => [], 'relations' => [], 'warnings' => []];
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            $result['warnings'][] = 'اسم Model غير صالح.';
            return $result;
        }
        $class = $this->modelClass($name);
        if (!is_subclass_of($class, Model::class)) {
            $result['warnings'][] = 'الموديل غير موجود أو ليس Eloquent.';
            return $result;
        }
        try {
            $model = new $class;
            $result['table'] = $model->getTable();
            $result['fillable'] = $model->getFillable();
            $result['casts'] = $model->getCasts();
            $result['key'] = $model->getKeyName();
            $result['incrementing'] = $model->getIncrementing();
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || !$method->getFileName()) continue;
                $source = implode('', array_slice(file($method->getFileName()), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
                if (preg_match('/\$this->(belongsToMany|belongsTo|hasMany|hasOne)\s*\(/', $source, $match)) {
                    $result['relations'][] = ['name' => $method->getName(), 'type' => $match[1], 'verified' => false];
                }
            }
            $result['columns'] = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
            if (!$result['columns']) $result['warnings'][] = 'لم يمكن التحقق من أعمدة الجدول؛ أكمل الوصف وسلّمه إلى Agent.';
        } catch (Throwable $error) {
            // Do not expose database credentials, hosts, SQL or exception messages to the browser.
            $result['warnings'][] = 'قراءة Schema غير متاحة؛ لا يغيّر الاكتشاف قاعدة البيانات.';
        }
        return $result;
    }

    /** Checks are read-only, and incomplete discovery never produces a partially working page. */
    public function reasons(array $definition): array
    {
        $reasons = [];
        if (!$definition['create_model']) {
            $info = $this->inspect($definition['model']);
            foreach ($info['warnings'] as $warning) $reasons[] = $warning;
            if (($info['key'] ?? null) !== 'id' || !($info['incrementing'] ?? false)) $reasons[] = 'الموديل يحتاج مراجعة المفتاح الأساسي.';
            $class = $this->modelClass($definition['model']);
            $model = is_subclass_of($class, Model::class) ? new $class : null;
            foreach (array_merge($definition['fields'], $definition['edit_fields'] ?? []) as $field) {
                if (($field['input'] ?? '') === 'empty' || ($field['name'] ?? '') === 'id') continue;
                $column = $field['column'] ?? $field['name'];
                if (!in_array($column, $info['columns'], true)) $reasons[] = "العمود {$column} غير مثبت في Schema الموديل.";
                if ($model && !($field['read_only'] ?? false) && !$model->isFillable($column)) $reasons[] = "العمود {$column} يحتاج مراجعة fillable.";
            }
        }
        foreach (array_merge($definition['fields'], $definition['edit_fields'] ?? [], $definition['filters'] ?? []) as $field) {
            if (!($field['select2'] ?? false)) continue;
            $routeName = $field['route'] ?? '';
            if (!$routeName || !Route::has($routeName)) {
                $reasons[] = 'مسار قائمة العلاقة غير متاح: '.$routeName;
            } else {
                $route = Route::getRoutes()->getByName($routeName);
                if (!in_array('GET', $route->methods(), true) || preg_match('/\{[^}]+(?<!\?)\}/', $route->uri())) $reasons[] = 'مسار القائمة يحتاج adapter GET دون parameters إلزامية: '.$routeName;
            }
            $relation = $field['relation'] ?? [];
            if (($relation['owner_key'] ?? 'id') !== 'id') $reasons[] = 'مفتاح العلاقة غير القياسي يحتاج Agent للتحقق من نوع التخزين وعقد قائمة الاختيارات: '.$field['name'];
            if (!($relation['model'] ?? null)) { $reasons[] = 'حدد Model علاقة '.$field['name']; continue; }
            $info = $this->inspect($relation['model']);
            if (($info['key'] ?? null) !== 'id' || !($info['incrementing'] ?? false)) $reasons[] = 'لم يثبت أن مفتاح العلاقة id رقمي تلقائي: '.$field['name'];
            if ($info['warnings']) $reasons[] = 'يلزم التحقق من Schema علاقة '.$field['name'];
            foreach ([$relation['owner_key'] ?? 'id', $relation['value_name'] ?? 'name'] as $column) {
                // Accessor labels are valid only after an agent reviews their dependencies.
                if (!in_array($column, $info['columns'], true)) $reasons[] = "حقل العلاقة {$column} يحتاج تحققًا أو accessor مخصصًا.";
            }
            if (!$definition['create_model']) {
                $primary = $this->inspect($definition['model']);
                $matches = array_filter($primary['relations'], fn ($item) => $item['name'] === ($relation['name'] ?? '') && $item['type'] === 'belongsTo');
                // Static discovery is informational, not a proof of target/FK/scopes. Existing
                // relationship methods can contain custom code; require an agent to verify them.
                $reasons[] = 'تحقق Agent من Model ومفاتيح ونطاق العلاقة الموجودة: '.($relation['name'] ?? '');
            }
        }
        return array_values(array_unique($reasons));
    }

    private function modelClass(string $name): string
    {
        return rtrim((string) config('dashboard.generator.model_namespace', 'App\\Models'), '\\').'\\'.$name;
    }
}
