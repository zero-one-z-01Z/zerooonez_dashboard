<?php

namespace App\Dashboard\Services\Dashboard;

/** Portable, non-executable handoff. Exporting this is not a completed application page. */
final class BuilderHandoff
{
    public function package(array $d, array $reasons): array
    {
        $controllerPath = trim((string) config('dashboard.generator.controller_path', 'app/Http/Controllers/Admin/Generated'), '/');
        $definitionPath = trim((string) config('dashboard.generator.definition_path', 'dashboard/resources'), '/');
        $modelPath = trim((string) config('dashboard.generator.model_path', 'app/Models'), '/');
        $routesPath = trim((string) config('dashboard.generator.routes_path', 'routes/admin-generated'), '/');
        $translationsPath = trim((string) config('dashboard.generator.translations_path', 'resources/lang'), '/');
        $controllerClass = trim((string) config('dashboard.generator.controller_namespace'), '\\').'\\'.$d['controller'];
        $modelClass = trim((string) config('dashboard.generator.model_namespace'), '\\').'\\'.$d['model'];
        $namePrefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        $routeName = ($namePrefix === '' ? '' : $namePrefix.'.').$d['resource'].'.index';
        $uriPrefix = trim((string) config('dashboard.route_prefix', 'admin'), '/');
        $url = '/'.($uriPrefix === '' ? '' : $uriPrefix.'/').$d['resource'];
        $filePlan = [
            ['path' => $controllerPath.'/'.$d['controller'].'.php', 'operation' => 'create', 'purpose' => 'Page controller '.$controllerClass.' and explicit custom read/save methods'],
            ['path' => $definitionPath.'/'.$d['resource'].'.json', 'operation' => 'create', 'purpose' => 'Normalized v2 contract'],
            ['path' => $modelPath.'/'.$d['model'].'.php', 'operation' => $d['create_model'] ? 'create' : 'inspect', 'purpose' => 'Model '.$modelClass.': columns, fillable, casts, relationships and scopes'],
            ['path' => $routesPath.'/'.$d['resource'].'.php', 'operation' => 'create', 'purpose' => 'Routes loaded inside the configured dashboard group'],
            ['path' => $translationsPath.'/{ar,en}/{admin,inputs}.php', 'operation' => 'reviewed_patch', 'purpose' => 'Reuse identical translations; reject conflicting values'],
        ];
        if ($d['create_model']) $filePlan[] = ['path' => 'database/migrations/*_create_'.$d['resource'].'_table.php', 'operation' => 'create', 'purpose' => 'Create schema; manually run only after review'];
        if ($d['capabilities']['show']) $filePlan[] = ['path' => 'resources/views/dashboard/admin/'.$d['resource'].'/show.blade.php', 'operation' => 'create_or_verified_destination', 'purpose' => 'A real details page, not a dangling view action'];
        $functionPlan = [
            'inputs_list' => 'Render fields in order with labels, type, validation and explicit relation metadata.',
            'update_inputs_list' => 'Reuse create fields plus hidden id, or edit_fields when custom; context-specific rules.',
            'filters' => 'Independent whitelist target/operator/audience; empty all means no query constraint.',
            'show_list' => 'Ordered columns with direct or relation source and safe display types.',
            'datatable_actions / modals' => 'Capabilities, permission keys and AND conditions; bind every operation to an authorized endpoint.',
            'get_single_item' => 'findOrFail; route id, explicit safe projection, eager relations and derived paths; selected labels and owned child IDs.',
            'store / update' => 'Server validation, scalar allowlist, scoped relation/media adapters; transactions, presence markers and compensating file cleanup.',
        ];
        $lines = ['# تنفيذ صفحة '.$d['resource'], '', '**الحالة: '.($reasons ? 'READY_FOR_AGENT — لم تُنشأ الصفحة' : 'DIRECT_PREVIEW — معاينة قبل الإنشاء').'**', '',
            '- Model: `'.$modelClass.'`', '- Controller: `'.$controllerClass.'`', '- Route: `'.$routeName.'`', '- URL: `'.$url.'`',
            '- Permission keys: `'.implode('`, `', app(ResourceDefinitionV2::class)->permissions($d)).'`', '', '## أسباب مسار Agent'];
        foreach ($reasons as $reason) $lines[] = '- '.$reason;
        if (!$reasons) $lines[] = 'التعريف داخل نطاق التوليد المباشر.';
        $lines = [...$lines, '', '## الملفات المقترحة — ليست ملفات منفذة'];
        foreach ($filePlan as $file) $lines[] = '- `'.$file['path'].'` — '.$file['operation'].': '.$file['purpose'];
        $lines = [...$lines, '', '## خطة الدوال'];
        foreach ($functionPlan as $method => $purpose) $lines[] = '- `'.$method.'()` — '.$purpose;
        $lines = [...$lines, '', '## العقد الكامل', 'ملف JSON المصاحب هو المصدر الكامل للحقول والأعمدة والفلاتر والأكشنات والمودالات والقائمة والترجمات. لا تتجاهل خصائص الأنواع المتخصصة.', '',
            '## دوال الكنترولر', '`inputs_list`, `update_inputs_list`, `filters`, `show_list`, `datatable_actions`, `modals`, `get_single_item`, `store`, `update`.', '', '## خريطة القراءة والحفظ', '| الطلب | الواجهة | التخزين | القراءة | العلاقة |', '| --- | --- | --- | --- | --- |'];
        $this->fieldLines($d['fields'], $lines);
        if ($d['edit_mode'] === 'custom') { $lines[] = ''; $lines[] = '### حقول التعديل المستقلة'; $this->fieldLines($d['edit_fields'], $lines); }
        $lines = [...$lines, '', '## متطلبات التنفيذ حسب النوع',
            '- scalar: whitelist بعد server validation؛ اختلاف create/update وread_only؛ معرف الراوت هو المرجع.',
            '- password/secret: لا تخزين مباشر؛ عيّن صراحة hash لكلمة المرور أو encryption للسر القابل للاسترجاع. write-only، لا إرجاع في الجدول أو get_single_item ولا تعبئة قيمة حالية؛ الغياب في التعديل يحتفظ بالقيمة.',
            '- belongsTo/chain: exists داخل نطاق الموديل، تحقق انتماء الطفل للأب، تحميل العلاقات/المعرفات المشتقة null-safe؛ hydrate بترتيب الآباء ثم الأبناء؛ تغيير الأب يمسح descendants داخل النموذج/الصف نفسه.',
            '- multi_select/pivot: IDs مميزة وصالحة؛ sync داخل transaction إن كانت العلاقة قياسية، وإلا adapter؛ الغياب يبقي القيم و[] يفرغها. إعادة id وvalue_name عند التعديل.',
            '- multiform: validate children.*، معرف مخفي للأبناء الموجودين فقط، ارفض child ID خارج الأب، create/update/delete داخل transaction. presence marker يميز الغياب عن []، وتجنب تكرار indexes عند الحذف والإضافة. nesting العميق يحتاج تنفيذًا خاصًا.',
            '- image/file/upload_images: احتفظ بالموجود عند غياب البديل؛ validate MIME والحجم والعدد النهائي retained+new-deleted؛ قيّد deleted IDs بالأب؛ جهّز تعويض الملفات عند فشل DB واحذف القديم بعد النجاح. لا يكفي DB rollback لاسترجاع ملف.',
            '- boundary: JSON [[lat,lng],…] مع ثلاث نقاط صالحة على الأقل؛ حفظ/استعادة/مسح polygon. map: حقلا إحداثيات محددان والصفر صالح. كل مكون scoped لمسار حقله.',
            '- html: تنقية خادمية للمحتوى قبل الحفظ؛ permissions: adapter محصور بالمجموعات المسموحة؛ empty وimage_preview لا يحفظان.',
            '- actions: endpoint+method+authorization+validation+modal؛ if = equality AND، والتحقق من حالة السجل يعاد على الخادم. view يحتاج show route/controller/view. notifications/PDF/publish تتطلب خدمة حقيقية واختبارًا، لا stub.',
            '- filters: whitelist target/operator؛ all الفارغة لا تصفي؛ علاقات تستخدم query مناسبًا؛ لا تأخذ أسماء أعمدة حرة من الطلب.', '', '## الدمج',
            '- الراوتات في '.$routesPath.'/'.$d['resource'].'.php داخل مجموعة '.$uriPrefix.' المحمية.',
            '- عنوان الصفحة/المجموعات/الأكشنات في '.$translationsPath.'/{ar,en}/admin.php؛ الحقول في inputs.php؛ القيمتان يكتبهما المستخدم، والتعارض لا يستبدل تلقائيًا.',
            '- ResourceRegistry يربط Sidebar والصلاحيات. الأمر اليدوي: php artisan dashboard:sync-permissions '.$d['resource'],
            '- لا تشغيل migrations أو منح Roles تلقائيًا. لا تغيير مشروع المثال الخارجي.', '', '## قبول التنفيذ',
            '- اختبر create → get_single_item → update → reload للحقول والعلاقات الخاصة بهذه الصفحة، و404/403/422 وتسريب الحقول المخفية ومعرفات الأطفال/الصور غير المملوكة.',
            '- اختبر arrays الغائبة والفارغة، الصور المتبقية والجديدة والمحذوفة، rollback، chain ثلاثية، إعادة فتح سجلين، ونماذج create/edit/custom دون تكرار handlers/IDs.',
            '- تحقق Route/Controller/Model/ترجمات ar/en/Sidebar/PermissionRegistry، وشغّل أمر الصلاحيات مرتين في DB معزولة.',
            '- جرّب المتصفح بالعربية والإنجليزية و390px، وأرفق نتيجة PHP/JS/build وحدود أي اختبارات لم تنفذ.', '', '## توزيع العمل',
            'Astra high يخطط التخصيص؛ Sol high يراجع العلاقات والملفات؛ Terra high ينفذ الأجزاء المحسومة. يُسلّم JSON وهذا الملف في Codex يدويًا؛ التنزيل لا يستدعي Agents أو API تلقائيًا.', '', '## قواعد العمل التي كتبها المستخدم', $d['notes'] ?: 'لا توجد قواعد إضافية.'];
        return ['json' => json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", 'markdown' => implode("\n", $lines)."\n", 'file_plan' => $filePlan, 'function_plan' => $functionPlan];
    }

    private function fieldLines(array $fields, array &$lines, string $prefix = ''): void
    {
        foreach ($fields as $f) {
            $name = $prefix.($f['name'] ?? '—');
            $escape = fn ($s) => str_replace(["\n", '|'], [' ', '\\|'], (string) $s);
            $lines[] = '| '.implode(' | ', array_map($escape, [$name, $f['input'], $f['storage']['strategy'] ?? 'scalar', $f['read_path'] ?? $f['name'] ?? '', $f['relation']['name'] ?? $f['storage']['relation'] ?? ''])).' |';
            if (!empty($f['inputs'])) $this->fieldLines($f['inputs'], $lines, $name.'[].');
        }
    }
}
