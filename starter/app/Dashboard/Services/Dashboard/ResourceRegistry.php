<?php

namespace App\Dashboard\Services\Dashboard;

use Throwable;

/** يقرأ موارد الداشبورد المولدة لعرضها في التنقل ومحرر صلاحيات الأدوار. */
final class ResourceRegistry
{
    /**
     * يستخدم نفس تطبيع المورد في القائمة الجانبية ومحرر الأدوار لتوحيد قراءة ملفات JSON.
     *
     * @param ResourceDefinition $definitions خدمة التحقق المشتركة؛ لا ينفذ constructor قراءة ملفات.
     */
    public function __construct(private ResourceDefinition $definitions)
    {
    }

    /**
     * يعيد التعريفات الصالحة بمفتاح اسم المورد؛ يتجاوز الملف التالف دون تعطيل الداشبورد.
     *
     * يشترط تطابق اسم الملف مع resource حتى يطابق المسار الذي يقرأه الكنترولر المولد.
     * لا يشغّل معاينة المولّد أو تحقق تعارض الملفات لأن الموارد هنا موجودة بالفعل.
     *
     * @return array<string, array> تعريفات JSON المطبعة والمرتبة باسم الملف.
     */
    public function all(): array
    {
        $resources = [];
        foreach (glob(base_path(trim((string) config('dashboard.generator.definition_path'), '/').'/*.json')) ?: [] as $path) {
            try {
                $input = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($input)) {
                    continue;
                }
                $definition = $this->definitions->normalize($input);
                if (basename($path, '.json') !== $definition['resource']) {
                    continue;
                }
                $resources[$definition['resource']] = $definition;
            } catch (Throwable $exception) {
                // تعطل تعريف واحد لا يمنع فتح صفحات الإدارة أو تعديل الأدوار الأخرى.
                continue;
            }
        }

        return $resources;
    }

    /**
     * يلحق صلاحيات الموارد المولدة بمجموعات المحرر الحالية دون تكرار معرف الصلاحية.
     *
     * يحافظ على ترتيب المجموعات القديمة وعناوينها. الموارد التي تشترك في permission
     * تستخدم نفس مجموعة الاختيارات؛ وتضاف الأفعال الناقصة فقط إلى المجموعة الموجودة.
     *
     * @param array<int, array> $existing مجموعات PermissionController القديمة بالترتيب الحالي.
     * @return array<int, array> المجموعات القديمة ثم الجديدة بعناوينها الصريحة.
     */
    public function permissionGroups(array $existing): array
    {
        $groups = $existing;
        $indexes = [];
        foreach ($groups as $index => $group) {
            $indexes[$group['id']] = $index;
        }
        $actions = ['view' => 'view', 'create' => 'create', 'update' => 'update', 'delete' => 'delete'];
        foreach ($this->all() as $definition) {
            $id = $definition['permission'];
            $resourceActions = $actions;
            $label = $definition['title'] ?? '';
            if (($definition['schema_version'] ?? 1) === 2) {
                $resourceActions = [];
                foreach (app(ResourceDefinitionV2::class)->permissions($definition) as $key) {
                    $suffix = '_'.$id;
                    if (str_ends_with($key, $suffix)) {
                        $action = substr($key, 0, -strlen($suffix));
                        $resourceActions[$action] = $action;
                    }
                }
                $label = __('admin.'.preg_replace('/^admin\./', '', $definition['title_key']));
            }
            if (isset($indexes[$id])) {
                $index = $indexes[$id];
                $groups[$index]['permissions'] = $groups[$index]['permissions'] + $resourceActions;
                continue;
            }
            $indexes[$id] = count($groups);
            $groups[] = [
                'id' => $id,
                'name' => $definition['resource'],
                'label' => $label,
                'permissions' => $resourceActions,
            ];
        }

        return $groups;
    }

    /** Only registered and authorized generated children enter navigation. */
    public function navigation(?string $group = null): array
    {
        $items = [];
        foreach ($this->all() as $d) {
            $sidebar = $d['sidebar'] ?? ['mode' => 'root', 'group' => '', 'order' => 0];
            if ($sidebar['mode'] === 'hidden') continue;
            if ($group === null && $sidebar['mode'] !== 'root') continue;
            if ($group !== null && ($sidebar['mode'] !== 'sub' || $sidebar['group'] !== $group)) continue;
            if (!\Illuminate\Support\Facades\Route::has($this->routePrefix($d['resource']).'index') || !\Illuminate\Support\Facades\Gate::allows('view_'.$d['permission'])) continue;
            $items[] = $d;
        }
        usort($items, fn ($a, $b) => ($a['sidebar']['order'] ?? 0) <=> ($b['sidebar']['order'] ?? 0));
        return $items;
    }

    public function hasVisibleGroup(string $group): bool
    {
        return (bool) $this->navigation($group) || ($group === 'scan' && (bool) $this->navigation('scan_times'));
    }

    public function groupActive(string $group): bool
    {
        if ($group === 'scan' && $this->groupActive('scan_times')) return true;
        foreach ($this->navigation($group) as $d) if (\Illuminate\Support\Facades\Route::is($this->routePrefix($d['resource']).'*')) return true;
        return false;
    }

    private function routePrefix(string $resource): string
    {
        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        return ($prefix === '' ? '' : $prefix.'.').$resource.'.';
    }

    public function newGroups(): array
    {
        $groups = [];
        foreach ($this->all() as $d) {
            $s = $d['sidebar'] ?? [];
            if (($s['mode'] ?? '') !== 'sub' || in_array($s['group'], BuilderCatalog::GROUPS, true) || !$this->hasVisibleGroup($s['group'])) continue;
            $groups[$s['group']] = $s;
        }
        uasort($groups, fn ($a, $b) => $a['order'] <=> $b['order']);
        return $groups;
    }
}
