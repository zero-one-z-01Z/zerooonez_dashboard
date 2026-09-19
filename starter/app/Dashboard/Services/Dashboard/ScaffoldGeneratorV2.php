<?php

namespace App\Dashboard\Services\Dashboard;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** v2 can patch reviewed shared files; v1 remains the original new-files-only generator. */
final class ScaffoldGeneratorV2
{
    public function __construct(private ResourceDefinitionV2 $definitions, private ScaffoldTemplatesV2 $templates, private ReviewedSourceWriter $writer, private BuilderCatalog $catalog, private BuilderHandoff $handoff) {}

    public function preview(array $input, ?string $root = null): array
    {
        $root ??= base_path();
        $d = $this->definitions->normalize($input);
        $reasons = array_values(array_unique([...$this->definitions->reasons($d), ...$this->catalog->reasons($d)]));
        $routeName = $this->routeName($d['resource'].'.index');
        $uri = $this->routeUri($d['resource']);
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() === $routeName || $route->uri() === $uri || str_starts_with($route->uri(), $uri.'/')) throw ValidationException::withMessages(['resource' => 'مسار الصفحة موجود بالفعل. اختر اسمًا جديدًا أو سلّم التعديل إلى Agent.']);
        }
        $resourcePath = trim((string) config('dashboard.generator.definition_path'), '/').'/'.$d['resource'].'.json';
        $this->writer->assertPath($root, $resourcePath);
        if (file_exists($root.'/'.$resourcePath)) throw ValidationException::withMessages(['resource' => 'تعريف الصفحة موجود بالفعل ولن يُستبدل.']);
        $translationChanges = $this->translations($root, $d);
        $changes = [];
        $files = [];
        $steps = [];
        if (!$reasons) {
            if ($d['create_model'] && glob($root.'/database/migrations/*_create_'.$d['resource'].'_table.php')) throw ValidationException::withMessages(['resource' => 'Migration إنشاء هذا الجدول موجودة بالفعل.']);
            foreach ($this->templates->files($d) as $path => $contents) {
                $this->writer->assertPath($root, $path);
                if (file_exists($root.'/'.$path)) throw ValidationException::withMessages(['resource' => 'الملف موجود ولن يُستبدل: '.$path]);
                $changes[] = $this->change($path, null, $contents);
                if (str_starts_with($path, 'database/migrations/')) $steps[] = 'php artisan migrate --path='.$path;
            }
            $path = trim((string) config('dashboard.generator.routes_path'), '/').'/'.$d['resource'].'.php';
            $this->writer->assertPath($root, $path);
            if (file_exists($root.'/'.$path)) throw ValidationException::withMessages(['resource' => 'ملف المسارات موجود ولن يستبدل: '.$path]);
            $changes[] = $this->change($path, null, $this->templates->routeBlock($d));
            $changes = [...$changes, ...$translationChanges];
            foreach ($changes as $change) {
                if (str_ends_with($change['path'], '.php')) token_get_all($change['after'], TOKEN_PARSE);
                $files[$change['path']] = $change['after'];
            }
            $steps[] = 'php artisan dashboard:sync-permissions '.$d['resource'];
            $steps[] = 'php artisan route:clear';
            $steps[] = 'امنح الدور الصلاحيات المطلوبة من إدارة الصلاحيات ثم افتح /'.$uri;
        } else {
            $steps[] = 'نزّل JSON وحزمة Markdown وقدّمهما إلى Agent لتنفيذ الصفحة واختبارها. لم تُكتب ملفات التطبيق.';
        }
        $summary = ['route' => $routeName, 'url' => '/'.$uri, 'controller' => $d['controller'], 'model' => $d['model'], 'permissions' => $this->definitions->permissions($d), 'capabilities' => $d['capabilities'], 'sidebar' => $d['sidebar']];
        return ['definition' => $d, 'mode' => $reasons ? 'agent_required' : 'direct', 'reasons' => $reasons,
            'files' => $files, 'changes' => $changes, 'translation_changes' => $translationChanges,
            'fingerprint' => hash('sha256', json_encode([$d, $changes, $reasons], JSON_THROW_ON_ERROR)),
            'next_steps' => $steps, 'summary' => $summary, 'handoff' => $this->handoff->package($d, $reasons),
            'form_preview' => ['create' => $d['fields'], 'edit' => [['name' => 'id', 'input' => 'hidden'], ...($d['edit_mode'] === 'custom' ? $d['edit_fields'] : $d['fields'])]]];
    }

    public function generate(array $input, string $fingerprint, ?string $root = null): array
    {
        $root ??= base_path();
        $this->writer->assertPath($root, 'storage/app/dashboard-generator.lock');
        if (!is_dir($root.'/storage/app') && !mkdir($root.'/storage/app', 0755, true)) throw new RuntimeException('تعذّر تجهيز قفل الكتابة.');
        $lock = fopen($root.'/storage/app/dashboard-generator.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('تعذّر قفل التوليد.');
        try {
            $preview = $this->preview($input, $root);
            if ($preview['mode'] !== 'direct') throw ValidationException::withMessages(['resource' => 'هذه الصفحة تحتاج Agent؛ لم تُكتب ملفات جزئية.']);
            if (!hash_equals($preview['fingerprint'], $fingerprint)) throw ValidationException::withMessages(['fingerprint' => 'تغيّر التعريف أو ملف مشترك؛ اعرض المعاينة مجددًا.']);
            return $this->writer->apply($root, $preview['changes']) + ['url' => $preview['summary']['url'], 'next_steps' => $preview['next_steps']];
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private function translations(string $root, array $d): array
    {
        $entries = ['admin' => [], 'inputs' => []];
        $add = function (string $group, string $key, string $ar, string $en) use (&$entries): void {
            $key = preg_replace('/^'.preg_quote($group, '/').'\./', '', $key);
            if (!preg_match('/^[a-z][a-z0-9_]*$/i', $key)) throw ValidationException::withMessages(['translations' => 'مفتاح ترجمة غير صالح: '.$key]);
            $value = ['ar' => $ar, 'en' => $en];
            if (isset($entries[$group][$key]) && $entries[$group][$key] !== $value) throw ValidationException::withMessages(['translations' => 'تعريفان مختلفان لنفس الترجمة: '.$key]);
            $entries[$group][$key] = $value;
        };
        $add('admin', $d['title_key'], $d['title_ar'], $d['title_en']);
        $walk = function (array $fields) use (&$walk, $add): void {
            foreach ($fields as $f) {
                if (($f['input'] ?? '') === 'empty' || ($f['name'] ?? '') === 'id') continue;
                $add('inputs', $f['label_key'], $f['label_ar'], $f['label_en']);
                if (!empty($f['inputs'])) $walk($f['inputs']);
            }
        };
        $walk($d['fields']); $walk($d['edit_fields'] ?? []);
        foreach (array_merge($d['columns'], $d['filters']) as $item) {
            if (!empty($item['label_key'])) $add('inputs', $item['label_key'], $item['label_ar'], $item['label_en']);
        }
        foreach ($d['actions'] as $a) if (!empty($a['name_key']) && !str_starts_with($a['name_key'], 'buttons.')) $add('admin', $a['name_key'], $a['name_ar'], $a['name_en']);
        foreach ($d['modals'] as $m) {
            $add('admin', $m['title_key'], $m['title_ar'], $m['title_en']); $walk($m['inputs']);
        }
        $sidebar = $d['sidebar'];
        if ($sidebar['mode'] === 'sub' && !in_array($sidebar['group'], BuilderCatalog::GROUPS, true)) $add('admin', $sidebar['group'], $sidebar['group_ar'], $sidebar['group_en']);
        $changes = [];
        foreach ($entries as $group => $keys) foreach (['ar', 'en'] as $locale) {
            $path = trim((string) config('dashboard.generator.translations_path'), '/').'/'.$locale.'/'.$group.'.php';
            $this->writer->assertPath($root, $path);
            $before = is_file($root.'/'.$path) ? file_get_contents($root.'/'.$path) : null;
            $existing = $before === null ? [] : (static fn ($file) => require $file)($root.'/'.$path);
            if (!is_array($existing)) throw ValidationException::withMessages(['translations' => 'ملف ترجمة غير قياسي: '.$path]);
            $lines = [];
            foreach ($keys as $key => $pair) {
                $value = $pair[$locale];
                if (array_key_exists($key, $existing)) {
                    if ($value !== '' && $existing[$key] !== $value) throw ValidationException::withMessages(['translations' => "الترجمة {$locale}/{$group}.{$key} موجودة بقيمة أخرى؛ استخدم القيمة الحالية أو مفتاحًا جديدًا."]);
                    continue;
                }
                if (trim($value) === '') throw ValidationException::withMessages(['translations' => "اكتب ترجمة {$locale}/{$group}.{$key}."]);
                $lines[] = '    '.var_export($key, true).' => '.var_export($value, true).',';
            }
            if (!$lines) continue;
            $source = $before ?? "<?php\n\nreturn [\n];\n";
            $end = strrpos($source, '];');
            if ($end === false || trim(substr($source, $end + 2)) !== '') throw ValidationException::withMessages(['translations' => 'ملف الترجمة يحتاج دمجًا يدويًا: '.$path]);
            $head = substr($source, 0, $end);
            $tokens = token_get_all($head);
            $meaningful = array_values(array_filter($tokens, fn ($t) => !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true)));
            $last = end($meaningful);
            if ($last !== '[' && $last !== ',') $head .= ',';
            $after = rtrim($head)."\n".implode("\n", $lines)."\n".substr($source, $end);
            $changes[] = $this->change($path, $before, $after);
        }
        return $changes;
    }

    private function routeName(string $suffix): string
    {
        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        return ($prefix === '' ? '' : $prefix.'.').ltrim($suffix, '.');
    }

    private function routeUri(string $suffix): string
    {
        $prefix = trim((string) config('dashboard.route_prefix', 'admin'), '/');
        return ($prefix === '' ? '' : $prefix.'/').ltrim($suffix, '/');
    }

    private function change(string $path, ?string $before, string $after): array
    {
        $old = explode("\n", rtrim($before ?? '', "\n")); $new = explode("\n", rtrim($after, "\n"));
        $start = 0; while (isset($old[$start], $new[$start]) && $old[$start] === $new[$start]) $start++;
        $oldEnd = count($old); $newEnd = count($new);
        while ($oldEnd > $start && $newEnd > $start && $old[$oldEnd - 1] === $new[$newEnd - 1]) { $oldEnd--; $newEnd--; }
        $diff = '--- '.($before === null ? '/dev/null' : 'a/'.$path)."\n+++ b/{$path}\n@@ -".($start + 1).','.($oldEnd - $start).' +'.($start + 1).','.($newEnd - $start)." @@\n";
        foreach (array_slice($old, $start, $oldEnd - $start) as $line) $diff .= '-'.$line."\n";
        foreach (array_slice($new, $start, $newEnd - $start) as $line) $diff .= '+'.$line."\n";
        return ['path' => $path, 'operation' => $before === null ? 'create' : 'update', 'before' => $before, 'after' => $after, 'diff' => $diff];
    }

    /**
     * Adds a top-level controller import without borrowing a name already owned by
     * another import. Returning the usable short name keeps the generated route
     * block compact while preserving the source owner's import style.
     *
     * @return array{0: string, 1: string}
     */
    private function withGeneratedControllerImport(string $source, string $controller): array
    {
        $target = trim((string) config('dashboard.generator.controller_namespace'), '\\').'\\'.$controller;
        $imports = $this->topLevelImports($source);
        $aliases = [];
        foreach ($imports as $import) {
            $statement = trim($import['statement']);
            foreach ($this->classesFromImport($statement) as [$class, $alias]) {
                if ($class === $target) {
                    return [$source, $alias];
                }
                $aliases[strtolower($alias)] = true;
            }
        }

        $alias = $controller;
        if (isset($aliases[strtolower($alias)])) {
            $alias = 'Generated'.$controller;
            $suffix = 2;
            while (isset($aliases[strtolower($alias)])) {
                $alias = 'Generated'.$controller.$suffix++;
            }
        }
        $import = 'use '.$target.($alias === $controller ? '' : ' as '.$alias).';';
        $last = end($imports);
        if ($last !== false) {
            $offset = $last['end'];
            return [substr($source, 0, $offset)."\n".$import.substr($source, $offset), $alias];
        }

        if (preg_match('/<\?php(?:\s+declare\s*\([^;]+;)?/i', $source, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw ValidationException::withMessages(['resource' => 'ملف routes/admin.php لا يحتوي بداية PHP صالحة لإضافة use للكنترولر.']);
        }
        $offset = $match[0][1] + strlen($match[0][0]);
        return [substr($source, 0, $offset)."\n\n".$import.substr($source, $offset), $alias];
    }

    /** @return list<array{0: string, 1: string}> */
    private function classesFromImport(string $statement): array
    {
        if (str_starts_with($statement, 'function ') || str_starts_with($statement, 'const ')) {
            return [];
        }
        if (!str_contains($statement, '{')) {
            $parts = preg_split('/\s+as\s+/i', $statement, 2);
            $class = trim($parts[0]);
            return [[$class, trim($parts[1] ?? substr($class, strrpos($class, '\\') + 1))]];
        }

        [$prefix, $members] = explode('{', $statement, 2);
        $prefix = rtrim(trim($prefix), '\\');
        $members = rtrim(trim($members), '}');
        $classes = [];
        foreach (explode(',', $members) as $member) {
            $parts = preg_split('/\s+as\s+/i', trim($member), 2);
            $class = $prefix.'\\'.trim($parts[0]);
            $classes[] = [$class, trim($parts[1] ?? substr($class, strrpos($class, '\\') + 1))];
        }
        return $classes;
    }

    /** @return list<array{statement: string, end: int}> */
    private function topLevelImports(string $source): array
    {
        $imports = [];
        $offset = 0;
        $depth = 0;
        $tokens = token_get_all($source);
        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_USE && $depth === 0) {
                $start = $offset;
                $statement = '';
                $end = $offset + strlen($text);
                for ($j = $i + 1; $j < $count; $j++) {
                    $part = $tokens[$j];
                    $partText = is_array($part) ? $part[1] : $part;
                    $statement .= $partText;
                    $end += strlen($partText);
                    if ($partText === ';') {
                        $i = $j;
                        break;
                    }
                }
                $imports[] = ['statement' => rtrim($statement, ';'), 'end' => $end];
                $offset = $end;
                continue;
            }
            if ($text === '{') $depth++;
            if ($text === '}') $depth--;
            $offset += strlen($text);
        }
        return $imports;
    }
}
