<?php

namespace ZeroOneZ\Dashboard\Services\Dashboard;

use RuntimeException;

/** يبني نصوص الملفات من قيم موثّقة؛ لا ينفّذ PHP قادمًا من واجهة المستخدم. */
final class ScaffoldTemplates
{
    /**
     * يجمع مخرجات المورد في خريطة ملفات قابلة للمعاينة، دون أي كتابة على القرص.
     *
     * ينتج Controller وroutes وJSON وSeeder دائما، ويضيف Model وMigration عند create_model.
     * اسم Migration يستخدم تاريخ UTC وقت توليد النص، لذلك تغير اليوم قد يتطلب معاينة جديدة.
     *
     * @param array<string, mixed> $definition تعريف المورد المطبّع بواسطة ResourceDefinition.
     * @return array<string, string> المسار النسبي لكل ملف ونصه الكامل.
     */
    public function files(array $definition): array
    {
        $model = $definition['model'];
        $resource = $definition['resource'];
        $permission = $definition['permission'];
        $files = [
            trim((string) config('dashboard.generator.controller_path'), '/')."/{$model}Controller.php" => $this->controller($model, $resource),
            trim((string) config('dashboard.generator.routes_path'), '/')."/{$resource}.php" => $this->routes($model, $resource, $permission),
            trim((string) config('dashboard.generator.definition_path'), '/')."/{$resource}.json" => json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
            "database/seeders/{$model}DashboardPermissionsSeeder.php" => $this->permissions($model, $permission),
        ];
        if ($definition['create_model']) {
            $files[trim((string) config('dashboard.generator.model_path'), '/')."/{$model}.php"] = $this->model($definition);
            $files['database/migrations/'.gmdate('Y_m_d')."_000000_create_{$resource}_table.php"] = $this->migration($definition);
        }
        return $files;
    }

    /**
     * يملأ قالب الكنترولر الذي يقرأ JSON عبر loadDefinition ويرث عمليات المورد المشتركة.
     *
     * @param string $model اسم الموديل الموثّق لاشتقاق اسم كلاس الكنترولر.
     * @param string $resource اسم المورد وملف تعريفه؛ يحوّل إلى literal PHP بواسطة var_export.
     * @return string كود الكنترولر؛ اسم الكلاس الأب مؤهل بالكامل لتجنب تعارض أسماء الموديلات.
     */
    private function controller(string $model, string $resource): string
    {
        return $this->render('controller', [
            '{{ model }}' => $model,
            '{{ resource }}' => $resource,
            '{{ resource_literal }}' => var_export($resource, true),
            '{{ controller_namespace }}' => (string) config('dashboard.generator.controller_namespace'),
            '{{ base_controller }}' => ltrim((string) config('dashboard.generator.base_controller'), '\\'),
        ]);
    }

    /**
     * يبني نص مسارات CRUD الفعلية مع view على المجموعة وصلاحية إضافية لكل كتابة.
     *
     * @param string $model اسم كلاس المورد الموثّق الذي تستخدمه المسارات.
     * @param string $resource بادئة الرابط والاسم داخل مجموعة admin الخارجية.
     * @param string $permission لاحقة مفاتيح view وcreate وupdate وdelete.
     * @return string نص ملف routes مستقل؛ لا يسجل المسارات أثناء المعاينة.
     */
    private function routes(string $model, string $resource, string $permission): string
    {
        return $this->render('routes', [
            '{{ model }}' => $model,
            '{{ resource }}' => var_export($resource, true),
            '{{ route_name }}' => var_export($resource.'.', true),
            '{{ view_permission }}' => var_export('can:view_'.$permission, true),
            '{{ permission }}' => var_export($permission, true),
            '{{ create_permission }}' => var_export('can:create_'.$permission, true),
            '{{ update_permission }}' => var_export('can:update_'.$permission, true),
            '{{ delete_permission }}' => var_export('can:delete_'.$permission, true),
            '{{ controller_namespace }}' => (string) config('dashboard.generator.controller_namespace'),
        ]);
    }

    /**
     * يبني Model بجدول صريح وfillable محصورة في الحقول وcasts للأرقام والمفاتيح والتواريخ.
     *
     * @param array<string, mixed> $definition اسم الموديل والمورد والحقول وخيار timestamps.
     * @return string نص Model الجديد؛ تعديل JSON لاحقا لا يعيد كتابة fillable أو casts فيه.
     */
    private function model(array $definition): string
    {
        $model = $definition['model'];
        $table = var_export($definition['resource'], true);
        $fields = var_export(array_column($definition['fields'], 'name'), true);
        $casts = [];
        foreach ($definition['fields'] as $field) {
            $type = match ($field['type']) {
                'integer' => 'integer', 'decimal' => 'decimal:2', 'boolean' => 'boolean',
                'date' => 'date:Y-m-d', 'datetime' => 'datetime:Y-m-d\\TH:i', default => null,
            };
            if ($type !== null) {
                $casts[$field['name']] = $type;
            }
        }
        $castCode = var_export($casts, true);
        $timestamps = $definition['timestamps'] ? 'true' : 'false';
        return $this->render('model', [
            '{{ model }}' => $model,
            '{{ table }}' => $table,
            '{{ fillable }}' => $fields,
            '{{ timestamps }}' => $timestamps,
            '{{ casts }}' => $castCode,
            '{{ model_namespace }}' => (string) config('dashboard.generator.model_namespace'),
        ]);
    }

    /**
     * يبني Migration لإنشاء جدول المورد الجديد مع id رقمي وحقوله والتواريخ الاختيارية.
     *
     * required=false يضيف nullable للعمود؛ decimal يستخدم دقة 12 ومنزلتين عشريتين.
     *
     * @param array<string, mixed> $definition المورد والحقول وأنواعها وخيار timestamps.
     * @return string كود up وdown؛ تشغيل الإنشاء أو التراجع يتم بأمر Artisan صريح.
     */
    private function migration(array $definition): string
    {
        $lines = ["            \$table->id();"];
        foreach ($definition['fields'] as $field) {
            $method = match ($field['type']) {
                'textarea' => 'text', 'integer' => 'bigInteger', 'decimal' => 'decimal',
                'boolean' => 'boolean', 'date' => 'date', 'datetime' => 'dateTime', default => 'string',
            };
            $args = var_export($field['name'], true).($method === 'decimal' ? ', 12, 2' : '');
            $nullable = $field['required'] ? '' : '->nullable()';
            $lines[] = "            \$table->{$method}({$args}){$nullable};";
        }
        if ($definition['timestamps']) {
            $lines[] = "            \$table->timestamps();";
        }
        $columns = implode("\n", $lines);
        $table = var_export($definition['resource'], true);
        return $this->render('migration', [
            '{{ table }}' => $table,
            '{{ columns }}' => $columns,
        ]);
    }

    /**
     * يبني Seeder يضيف مفاتيح CRUD المفقودة باستخدام firstOrCreate ولا يمنحها لحساب.
     *
     * @param string $model اسم الموديل الذي يشتق منه اسم كلاس Seeder.
     * @param string $permission لاحقة مفاتيح الصلاحيات الأربعة.
     * @return string نص Seeder؛ تنفيذه خطوة منفصلة ثم تختار الدور من محرر الصلاحيات.
     */
    private function permissions(string $model, string $permission): string
    {
        return $this->render('permissions', [
            '{{ model }}' => $model,
            '{{ permission_suffix }}' => var_export('_'.$permission, true),
        ]);
    }

    /**
     * يقرأ قالبا قابلا للتعديل من stubs/dashboard ويستبدل مواضعه نصيا دون تنفيذ الكود.
     *
     * يثبت مسار القوالب على جذر المصدر حتى تعمل المعاينة عند تغيير base_path للاختبار.
     * القيم النصية الخاصة بـPHP تمر عبر var_export قبل الوصول هنا؛ اسم الكلاس موثّق.
     *
     * @param string $stub اسم قالب داخلي ثابت تختاره دوال هذا الكلاس.
     * @param array<string, string> $replacements المواضع والقيم الآمنة التي تحل محلها.
     * @return string نص الملف المولد؛ لا يكتب ملفات ولا يشغل eval.
     */
    private function render(string $stub, array $replacements): string
    {
        $path = rtrim((string) config('dashboard.generator.stubs_path', dirname(__DIR__, 3).'/stubs'), '/').'/'.$stub.'.stub';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('تعذّر قراءة قالب الداشبورد: '.$stub);
        }

        return strtr($contents, $replacements);
    }
}
