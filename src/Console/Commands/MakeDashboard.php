<?php

namespace ZeroOneZ\Dashboard\Console\Commands;

use ZeroOneZ\Dashboard\Services\Dashboard\ScaffoldGenerator;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

/** بديل واجهة المتصفح؛ نفس المولّد يعمل من ملف JSON قابل لإعادة الاستخدام. */
class MakeDashboard extends Command
{
    protected $signature = 'dashboard:make {schema : Path to a dashboard JSON definition} {--write : Create the previewed files}';
    protected $description = 'Preview or create an admin dashboard resource from JSON (local environment only)';

    /**
     * يقرأ ملف JSON ويطبع المعاينة؛ خيار --write ينشئ الملفات بعدها عبر نفس خدمة واجهة المتصفح.
     *
     * يعمل في local أو testing فقط، ولا يحتاج مفتاح تفعيل واجهة المتصفح أو جلسة أدمن.
     * يعرض أوامر التهيئة ولا ينفذ Migration أو Seeder، ويطبع سبب التحقق أو فشل الملفات.
     *
     * @param ScaffoldGenerator $generator خدمة المعاينة والكتابة التي يحقنها Laravel.
     * @return int SUCCESS عند اكتمال المعاينة أو الإنشاء، وFAILURE عند رفض البيئة أو فشل العملية.
     */
    public function handle(ScaffoldGenerator $generator): int
    {
        if (!app()->environment('local', 'testing')) {
            $this->error('أداة إنشاء المصدر تعمل في بيئة local فقط.');
            return self::FAILURE;
        }
        try {
            $path = $this->argument('schema');
            if (!is_file($path)) {
                throw new \RuntimeException('ملف التعريف غير موجود: '.$path);
            }
            $schema = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            $preview = $generator->preview($schema);
            if (($preview['mode'] ?? '') === 'agent_required') {
                $this->line($preview['handoff']['markdown']);
                if ($this->option('write')) {
                    $this->error('هذه حزمة Agent وليست صفحة قابلة للتوليد المباشر. لم تُكتب ملفات.');
                    return self::FAILURE;
                }
                return self::SUCCESS;
            }
            foreach ($preview['files'] as $file => $contents) {
                $this->line("\n--- {$file} ---\n{$contents}");
            }
            if ($this->option('write')) {
                $result = $generator->generate($schema, $preview['fingerprint']);
                $this->info('تم إنشاء '.count($result['paths']).' ملفات.');
            } else {
                $this->info('معاينة فقط. أضف --write لإنشاء الملفات.');
            }
            foreach ($preview['next_steps'] as $step) {
                $this->line($step);
            }
            return self::SUCCESS;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                $this->error(implode(' ', $messages));
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
        }
        return self::FAILURE;
    }
}
