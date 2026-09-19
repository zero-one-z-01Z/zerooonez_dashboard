<?php

namespace ZeroOneZ\Dashboard\Http\Controllers;

use Illuminate\Routing\Controller;
use ZeroOneZ\Dashboard\Services\Dashboard\ResourceDefinition;
use ZeroOneZ\Dashboard\Services\Dashboard\ScaffoldGenerator;
use Illuminate\Http\Request;

/** واجهة المطوّر المحلية؛ المسارات محمية بتسجيل الأدمن وبيئة local واتصال loopback. */
class DashboardBuilderController extends Controller
{
    /**
     * يتحقق من local وتفعيل الأداة وعنوان اتصال loopback قبل السماح بقراءة واجهتها أو كتابة المصدر.
     *
     * يعتمد على REMOTE_ADDR الفعلي؛ تسجيل الأدمن وCSRF يطبقهما مسار admin الخارجي.
     *
     * @param Request $request الطلب الحالي الذي يحتوي عنوان الاتصال.
     * @return void يتابع التنفيذ أو ينهي الطلب باستجابة 404 عند عدم تحقق الشروط.
     */
    private function ensureLocal(Request $request): void
    {
        abort_unless(app()->environment('local') && config('dashboard.builder_enabled')
            && in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true), 404);
    }

    /**
     * يعرض محرر تعريف الصفحة مع الأنواع التي يدعمها ResourceDefinition.
     *
     * @param Request $request يستخدم للتحقق من شروط تشغيل أداة المطور المحلية.
     * @return \Illuminate\Contracts\View\View واجهة dashboard.builder.index دون جلب بيانات أعمال.
     */
    public function index(Request $request)
    {
        $this->ensureLocal($request);
        return view(config('dashboard.view_namespace', 'zerooonez-dashboard').'::builder.index', ['types' => ResourceDefinition::TYPES]);
    }

    public function catalog(Request $request, \ZeroOneZ\Dashboard\Services\Dashboard\BuilderCatalog $catalog)
    {
        $this->ensureLocal($request);
        $request->validate(['model' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z][A-Za-z0-9]*$/']]);
        return response()->json($catalog->data($request->input('model')));
    }

    /**
     * يحوّل قيم النموذج إلى خطة ملفات قابلة للقراءة مع بصمة تثبت نصوص هذه المعاينة.
     *
     * @param Request $request تعريف المورد وحقوله؛ تتحقق الخدمة من الأسماء والتعارضات.
     * @param ScaffoldGenerator $generator خدمة المعاينة المشتركة مع أمر Artisan.
     * @return \Illuminate\Http\JsonResponse definition وfiles وfingerprint وnext_steps؛ لا يكتب ملفات.
     */
    public function preview(Request $request, ScaffoldGenerator $generator)
    {
        $this->ensureLocal($request);
        return response()->json($generator->preview($request->all()));
    }

    /**
     * ينشئ الملفات الجديدة المطابقة للمعاينة المعتمدة، دون تشغيل Migration أو Seeder.
     *
     * @param Request $request نفس التعريف ومعه fingerprint بطول 64 حرفا.
     * @param ScaffoldGenerator $generator خدمة القفل ومطابقة البصمة والكتابة والتراجع عند الفشل.
     * @return \Illuminate\Http\JsonResponse استجابة 201 فيها paths وurl وخطوات التهيئة التالية.
     */
    public function generate(Request $request, ScaffoldGenerator $generator)
    {
        $this->ensureLocal($request);
        $request->validate(['fingerprint' => ['required', 'string', 'size:64']]);
        return response()->json($generator->generate($request->all(), $request->string('fingerprint')->toString()), 201);
    }
}
