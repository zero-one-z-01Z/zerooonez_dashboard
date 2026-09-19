<?php

namespace App\Dashboard\Services\Dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** معاينة وكتابة الملفات الجديدة فقط؛ لا يستبدل ملفات موجودة ولا يشغّل migrations. */
final class ScaffoldGenerator
{
    /**
     * يربط تحقق تعريف المورد وقوالب الملفات لكي تستخدم الواجهة وArtisan نفس مسار التوليد.
     *
     * @param ResourceDefinition $definitions خدمة التطبيع وقواعد أسماء الموارد والحقول.
     * @param ScaffoldTemplates $templates منتج نصوص الملفات قبل كتابتها.
     */
    public function __construct(private ResourceDefinition $definitions, private ScaffoldTemplates $templates)
    {
    }

    /**
     * يبني نصوص الملفات ويكشف تعارض الأسماء والمسارات قبل أي كتابة.
     *
     * عند إعادة استخدام موديل، يشترط Eloquent ومعرف id رقمي تلقائي. يفحص ملفات PHP
     * بواسطة tokenizer ويحسب البصمة من نصوص الملفات كلها؛ المعاينة لا تشغّل migrations.
     *
     * @param array<string, mixed> $input تعريف المورد قبل التطبيع.
     * @param string|null $root جذر المشروع المراد الكتابة فيه؛ null يستخدم base_path، والاختبارات تمرر مجلدا معزولا.
     * @return array{definition: array, files: array, fingerprint: string, next_steps: array} خطة المراجعة كاملة.
     * @throws ValidationException عند تعارض مورد أو ملف أو عدم صلاحية التعريف.
     */
    public function preview(array $input, ?string $root = null): array
    {
        if ((int) ($input['schema_version'] ?? 1) === 2) {
            return app(ScaffoldGeneratorV2::class)->preview($input, $root);
        }
        $root ??= base_path();
        $definition = $this->definitions->normalize($input);
        $modelClass = rtrim((string) config('dashboard.generator.model_namespace'), '\\').'\\'.$definition['model'];
        $modelPath = $root.'/'.trim((string) config('dashboard.generator.model_path'), '/').'/'.$definition['model'].'.php';
        if (!$definition['create_model'] && (!is_file($modelPath) || !is_subclass_of($modelClass, Model::class))) {
            throw ValidationException::withMessages(['model' => 'الموديل الموجود غير متاح؛ اختر Model فعليًا أو فعّل إنشاء موديل جديد.']);
        }
        if (!$definition['create_model']) {
            $existingModel = new $modelClass;
            if ($existingModel->getKeyName() !== 'id' || $existingModel->getKeyType() !== 'int' || !$existingModel->getIncrementing()) {
                throw ValidationException::withMessages(['model' => 'المولّد يتطلب مفتاح id رقميًا تلقائيًا؛ الموديل ذو المفتاح المخصص يحتاج كنترولرًا مخصصًا.']);
            }
        }
        if (Route::has('admin.'.$definition['resource'].'.index')) {
            throw ValidationException::withMessages(['resource' => 'يوجد مسار أدمن بهذا الاسم بالفعل.']);
        }
        $uri = 'admin/'.$definition['resource'];
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri || str_starts_with($route->uri(), $uri.'/')) {
                throw ValidationException::withMessages(['resource' => 'المسار مستخدم بالفعل: /'.$uri]);
            }
        }
        if ($definition['create_model'] && glob($root.'/database/migrations/*_create_'.$definition['resource'].'_table.php')) {
            throw ValidationException::withMessages(['resource' => 'يوجد ملف Migration لإنشاء هذا الجدول بالفعل.']);
        }
        $files = $this->templates->files($definition);
        foreach ($files as $path => $contents) {
            $this->assertNewPath($root, $path);
            if (str_ends_with($path, '.php')) {
                token_get_all($contents, TOKEN_PARSE);
            }
        }
        return ['definition' => $definition, 'files' => $files,
            'fingerprint' => hash('sha256', json_encode($files, JSON_THROW_ON_ERROR)),
            'next_steps' => $this->nextSteps($definition, $files)];
    }

    /**
     * يقفل المولّد ثم يعيد المعاينة ويكتب ملفات جديدة فقط بعد مطابقة البصمة.
     *
     * يفتح كل ملف بالوضع x حتى يرفض الملف الموجود. عند فشل العملية، يحذف فقط الملفات
     * التي أنشأتها هذه المحاولة؛ قد تبقى مجلدات فارغة وملف القفل. لا يغير قاعدة البيانات.
     *
     * @param array<string, mixed> $input نفس تعريف المعاينة التي راجعها المستخدم.
     * @param string $fingerprint بصمة SHA-256 التي أعادتها preview لنصوص الملفات.
     * @param string|null $root جذر المشروع أو مجلد اختبار معزول؛ الافتراضي base_path.
     * @return array{paths: array, url: string, next_steps: array} الملفات المنشأة ورابط الصفحة وخطوات التهيئة.
     * @throws ValidationException إذا اختلفت البصمة أو ظهر تعارض منذ المعاينة.
     * @throws RuntimeException إذا تعذر القفل أو إنشاء ملف أو إكمال كتابته.
     */
    public function generate(array $input, string $fingerprint, ?string $root = null): array
    {
        if ((int) ($input['schema_version'] ?? 1) === 2) {
            return app(ScaffoldGeneratorV2::class)->generate($input, $fingerprint, $root);
        }
        $root ??= base_path();
        $lockDir = $root.'/storage/app';
        $this->directory($lockDir);
        $lock = fopen($lockDir.'/dashboard-generator.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('تعذّر قفل عملية التوليد؛ تأكد من صلاحيات storage/app.');
        }
        $created = [];
        try {
            $preview = $this->preview($input, $root);
            if (!hash_equals($preview['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['fingerprint' => 'تغيّرت البيانات أو الملفات؛ اعرض المعاينة مجددًا قبل الإنشاء.']);
            }
            foreach ($preview['files'] as $relative => $contents) {
                $path = $root.'/'.$relative;
                $this->directory(dirname($path));
                $this->assertNewPath($root, $relative);
                $handle = fopen($path, 'x');
                if (!$handle) {
                    throw new RuntimeException('تعذّر إنشاء '.$relative);
                }
                $created[] = $path;
                try {
                    if (fwrite($handle, $contents) !== strlen($contents) || !fflush($handle)) {
                        throw new RuntimeException('تعذّرت كتابة '.$relative.' بالكامل.');
                    }
                } finally {
                    fclose($handle);
                }
            }
            return ['paths' => array_keys($preview['files']), 'url' => '/admin/'.$preview['definition']['resource'],
                'next_steps' => $preview['next_steps']];
        } catch (Throwable $exception) {
            foreach (array_reverse($created) as $path) {
                unlink($path);
            }
            throw $exception;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * يفحص أجزاء مسار الملف الناتج ويرفض الملف الموجود أو الرابط الرمزي أو اختلاف حالة اسم موجود.
     *
     * @param string $root جذر المشروع الذي يبدأ منه الفحص.
     * @param string $relative مسار نسبي أنشأته ScaffoldTemplates؛ ليس مسارا حرا من المستخدم.
     * @return void لا ينشئ ملفات أو مجلدات.
     * @throws ValidationException عند اكتشاف تعارض في أي جزء من المسار.
     */
    private function assertNewPath(string $root, string $relative): void
    {
        $cursor = rtrim($root, '/');
        foreach (explode('/', $relative) as $part) {
            if (is_dir($cursor)) {
                foreach (scandir($cursor) as $existing) {
                    if (strcasecmp($part, $existing) === 0 && $part !== $existing) {
                        throw ValidationException::withMessages(['resource' => 'تعارض اسم ملف: '.$relative]);
                    }
                }
            }
            $cursor .= '/'.$part;
            if (is_link($cursor)) {
                throw ValidationException::withMessages(['resource' => 'لا يكتب المولّد داخل روابط رمزية: '.$relative]);
            }
        }
        if (file_exists($cursor)) {
            throw ValidationException::withMessages(['resource' => 'الملف موجود بالفعل ولن يُستبدل: '.$relative]);
        }
    }

    /**
     * ينشئ المجلدات الناقصة بالوضع 0755 قبل كتابة الملف أو فتح قفل المولّد.
     *
     * @param string $path المسار الكامل للمجلد المطلوب؛ يترك المجلد الموجود كما هو.
     * @return void
     * @throws RuntimeException إذا تعذر الإنشاء وظل المجلد غير موجود.
     */
    private function directory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('تعذّر إنشاء المجلد '.$path);
        }
    }

    /**
     * يجهز تعليمات ما بعد التوليد من الملفات الفعلية، دون تشغيل أي أمر منها.
     *
     * يعرض migrate لمسار الملف المنشأ فقط عند وجوده، ثم Seeder الصلاحيات ومسح route cache
     * ومنح الدور صلاحياته. استخدام موديل موجود لا ينتج خطوة migration جديدة.
     *
     * @param array<string, mixed> $definition المورد المطبّع الذي يحدد اسم Seeder ورابط الصفحة.
     * @param array<string, string> $files المسارات النسبية ونصوص الملفات الناتجة من المعاينة.
     * @return array<int, string> أوامر وتعليمات تعرضها واجهة المولّد وأمر Artisan.
     */
    private function nextSteps(array $definition, array $files): array
    {
        $steps = [];
        foreach (array_keys($files) as $path) {
            if (str_starts_with($path, 'database/migrations/')) {
                $steps[] = 'php artisan migrate --path='.$path;
            }
        }
        $steps[] = 'php artisan db:seed --class='.$definition['model'].'DashboardPermissionsSeeder';
        $steps[] = 'php artisan route:clear';
        $steps[] = 'اربط الصلاحيات الجديدة بالدور المناسب من إدارة الصلاحيات، ثم افتح /admin/'.$definition['resource'];
        return $steps;
    }
}
