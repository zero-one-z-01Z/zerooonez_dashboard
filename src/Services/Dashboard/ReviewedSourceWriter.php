<?php

namespace ZeroOneZ\Dashboard\Services\Dashboard;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Applies reviewed changes only; never follows symlinks or silently replaces a fresh edit. */
class ReviewedSourceWriter
{
    public function assertPath(string $root, string $relative): void
    {
        if (!preg_match('#^[A-Za-z0-9_./-]+$#', $relative) || str_contains($relative, '..') || str_starts_with($relative, '/')) throw new RuntimeException('Invalid generated path.');
        $cursor = rtrim($root, '/');
        if (is_link($cursor)) throw new RuntimeException('Source root cannot be a symlink.');
        foreach (explode('/', $relative) as $part) {
            if (is_dir($cursor)) foreach (scandir($cursor) as $existing) {
                if (strcasecmp($part, $existing) === 0 && $part !== $existing) throw ValidationException::withMessages(['resource' => 'تعارض حالة اسم الملف: '.$relative]);
            }
            $cursor .= '/'.$part;
            if (is_link($cursor)) throw ValidationException::withMessages(['resource' => 'مسار رمزي غير مسموح: '.$relative]);
        }
    }

    public function apply(string $root, array $changes): array
    {
        $done = [];
        $backup = 'storage/app/dashboard-builder-backups/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        foreach ($changes as $c) $this->check($root, $c);
        try {
            foreach ($changes as $c) {
                $this->check($root, $c);
                if ($c['before'] !== null) {
                    $this->assertPath($root, $backup.'/'.$c['path']);
                    $this->putNew($root.'/'.$backup.'/'.$c['path'], $c['before']);
                }
                $path = $root.'/'.$c['path'];
                if ($c['before'] === null) $this->putNew($path, $c['after']);
                else $this->replace($path, $c['after']);
                $done[] = $c;
            }
        } catch (Throwable $error) {
            $failures = [];
            foreach (array_reverse($done) as $c) {
                try {
                    $path = $root.'/'.$c['path'];
                    if (!is_file($path) || is_link($path) || file_get_contents($path) !== $c['after']) {
                        $failures[] = $c['path'].' (changed externally; preserved)'; continue;
                    }
                    if ($c['before'] === null) {
                        if (!unlink($path)) $failures[] = $c['path'];
                    } else $this->replace($path, $c['before']);
                } catch (Throwable $restoreError) { $failures[] = $c['path']; }
            }
            if ($failures) throw new RuntimeException('فشلت الكتابة والاستعادة غير مكتملة: '.implode(', ', $failures).'. النسخ الأصلية في '.$backup, 0, $error);
            throw $error;
        }
        return ['paths' => array_column($changes, 'path'), 'backup_path' => $backup];
    }

    private function check(string $root, array $c): void
    {
        $this->assertPath($root, $c['path']);
        $path = $root.'/'.$c['path'];
        $current = is_file($path) ? file_get_contents($path) : null;
        if (is_dir($path) || $current !== $c['before']) throw ValidationException::withMessages(['fingerprint' => 'تغير الملف منذ المعاينة: '.$c['path']]);
    }

    protected function putNew(string $path, string $contents): void
    {
        $this->directory(dirname($path));
        $handle = @fopen($path, 'x');
        if (!$handle) throw new RuntimeException('تعذّر إنشاء ملف جديد: '.$path);
        try {
            if (fwrite($handle, $contents) !== strlen($contents) || !fflush($handle)) {
                fclose($handle); $handle = null; unlink($path);
                throw new RuntimeException('تعذّر إكمال كتابة الملف.');
            }
        } finally { if (is_resource($handle)) fclose($handle); }
    }

    protected function replace(string $path, string $contents): void
    {
        $temporary = tempnam(dirname($path), '.dashboard-');
        if ($temporary === false) throw new RuntimeException('تعذّر تجهيز الكتابة الذرية.');
        try {
            if (file_put_contents($temporary, $contents) !== strlen($contents)) throw new RuntimeException('تعذّر إكمال كتابة الملف.');
            chmod($temporary, fileperms($path) & 0777);
            if (!rename($temporary, $path)) throw new RuntimeException('تعذّر تثبيت الملف.');
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    private function directory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) throw new RuntimeException('تعذّر إنشاء مجلد المخرجات.');
    }
}
