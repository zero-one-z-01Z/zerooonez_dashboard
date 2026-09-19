<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ZeroOneZ\Dashboard\Http\Controllers\Concerns\BuildsDashboardPage;
use ZeroOneZ\Dashboard\Http\Controllers\Concerns\HandlesDashboardTable;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Http\Traits\AjaxResponseTrait;
use App\Http\Traits\PaginateTrait;
use App\Http\Traits\SendEmail;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminController extends Controller
{
    use BuildsDashboardPage, HandlesDashboardTable;
    use AjaxResponseTrait,PaginateTrait,SendEmail;
    public $modelName = 'admins';

    public $permissions = ['create_admin', 'update_admin', 'delete_admin', 'view_admin',];
    public $datatable_url = 'admin.admins.datatable';
    public $delete_all_url = 'admin.admins.delete_all';
    public $get_single_item = 'admin.admins.get_single_item';
    public $update_url = 'admin.admins.update';
    public $delete_url = 'admin.admins.delete';
    public $store_url = 'admin.admins.store';
    public $daterange_filter = false;
    public $select2 = true;
    public $datatable = true;
    public $have_actions = true;
    public $have_check_box = true;
    public $have_validation = true;
    public $have_export = true;
    public $have_add = true;
    public $have_delete_all = true;
    public $daterannge_filter_name = 'created_at';
    public $daterange_filter_tooltip = '';
    public $filter_display = 'float'; // modal - float
    public $show_columns = false;

    public $columns = ['id','name','phone','email','role_id'];

    /**
     * يبدأ استعلام Eloquent لموديل Admin الذي يدير حسابات الإدارة، دون تنفيذ القراءة.
     *
     * @return \Illuminate\Database\Eloquent\Builder الاستعلام القابل لإضافة شروط أخرى.
     */
    public function model(): \Illuminate\Database\Eloquent\Builder
    {
        return Admin::query();
    }

    /**
     * يعيد تعريف بطاقات إحصاءات حسابات الإدارة؛ القائمة الحالية فارغة فلا تظهر بطاقات إضافية.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function statistics()
    {
        $data = [];
        return $data;
    }

    /**
     * يعرف أزرار صف حسابات الإدارة وروابطها وأنواع التفاعل التي يستخدمها DataTables.
     * مفاتيح صلاحيات الأزرار: update_admin, delete_admin.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function datatable_actions()
    {
        $data = [];
        $data[] = ['name'=>__('buttons.edit'),'type'=>'modal','link'=>'#edit_modal','icon'=>'icon-base ti tabler-edit','onclick'=>'edit_item','permission'=>'update_admin'];
        $data[] = ['name'=>__('buttons.delete'),'type'=>'delete','link'=>route($this->delete_url,'#placeholder#'),
            'value'=>'id','icon'=>'icon-base ti tabler-trash','onclick'=>'delete_item','permission'=>'delete_admin'];
        return $data;
    }

    /**
     * يعيد قائمة مرشحات واجهة فارغة لشاشة حسابات الإدارة؛ يظل البحث العام متاحا بحسب إعدادات الجدول.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function filters()
    {

        $data = [];
                return $data;
    }

    /**
     * يحدد ترتيب خلايا جدول حسابات الإدارة وعناوينها وأنواع عرضها ومسارات العلاقات.
     * مفاتيح العرض: id, name, email, phone, online, role.name, admin_tickets.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function show_list()
    {
        $body[] = [
            "key"=>"id",
            "type"=>"id",
            "title"=>__('inputs.id'),
        ];
        $body[] = [
            "key"=>"name",
            "title"=>__('inputs.name'),
            "type"=>"text",
        ];
        $body[] = [
            "key"=>"email",
            "title"=>__('inputs.email'),
            "type"=>"text",
        ];
        $body[] = [
            "key"=>"phone",
            "title"=>__('inputs.phone'),
            "type"=>"text",
        ];
        $body[] = [
            "key"=>"online",
            "title"=>__('inputs.online'),
            "type"=>"text",
        ];
        $body[] = [
            "key"=>"role.name",
            "title"=>__('admin.permissions'),
            "type"=>"text",
        ];
        $body[] = [
            "key"=>"admin_tickets",
            "title"=>__('inputs.is_admin_tickets'),
            "type"=>"switch",
            "link"=>route('admin.admins.update_admin_tickets'),
        ];
        return $body;
    }

    /**
     * يعرف حقول إضافة حسابات الإدارة وأنواع المدخلات والتحقق المستخدم في الواجهة.
     * الحقول: name, email, phone, role_id.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function inputs_list()
    {
        $inputs = [];
        $inputs[] = ["id"=>"name","input"=>"text","label"=>__('inputs.name'),'value'=>"",'type'=>"text",'validation'=>[
            'required'=>true,
        ]];
        $inputs[] = ["id"=>"email","input"=>"text","label"=>__('inputs.email'),'value'=>"",'type'=>"email",'validation'=>[
            'required'=>true,
        ]];
//        $inputs[] = ["id"=>"password","input"=>"text","label"=>__('inputs.password'),'value'=>"",'type'=>"text",'validation'=>[
//            'required'=>true,
//        ],];
        $inputs[] = ["id"=>"phone","input"=>"text","label"=>__('inputs.phone'),'value'=>"",'type'=>"tel",'validation'=>[
            'required'=>true,
        ],];
        $inputs[] = ["id"=>"role_id","input"=>"single","label"=>__('inputs.permission'),'value'=>"",'type'=>"single",'validation'=>[
            'required'=>true,
        ],'select2'=>true,'url'=>route('admin.permissions.list')];
//        $inputs[] = ["id"=>"admin_company_id","input"=>"single","label"=>__('admin.companies'),'value'=>"",'type'=>"single",'validation'=>[
//            'required'=>false,
//        ],'select2'=>true,'url'=>route('admin.admins.list_super')];
        return $inputs;
    }

    /**
     * يعرف حقول تعديل حسابات الإدارة وأنواع المدخلات والتحقق المستخدم في الواجهة.
     * الحقول: id, name, email, phone, role_id.
     * key_name وvalue_name يحددان مسار تعبئة العلاقات عند فتح التعديل.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function update_inputs_list()
    {
        $inputs = [];
        $inputs[] = ["id"=>"id","input"=>"hidden"];
        $inputs[] = ["id"=>"name","input"=>"text","label"=>__('inputs.name'),'value'=>"",'type'=>"text",'validation'=>[
            'required'=>true,
        ]];
        $inputs[] = ["id"=>"email","input"=>"text","label"=>__('inputs.email'),'value'=>"",'type'=>"email",'validation'=>[
            'required'=>true,
        ]];
//        $inputs[] = ["id"=>"new_password","input"=>"text","label"=>__('inputs.password'),'value'=>"",'type'=>"text",'validation'=>[
//            'required'=>false,
//        ],];
        $inputs[] = ["id"=>"phone","input"=>"text","label"=>__('inputs.phone'),'value'=>"",'type'=>"tel",'validation'=>[
            'required'=>true,
        ],];
        $inputs[] = ["id"=>"role_id","input"=>"single","label"=>__('inputs.permission'),'value'=>"",'type'=>"single",'validation'=>[
            'required'=>true,
        ],'select2'=>true,'url'=>route('admin.permissions.list'),'key_name'=>'role','value_name'=>'name'];
//        $inputs[] = ["id"=>"admin_company_id","input"=>"single","label"=>__('inputs.companies'),'value'=>"",'type'=>"single",'validation'=>[
//            'required'=>false,
//        ],'select2'=>true,'url'=>route('admin.admins.list_super'),'key_name'=>'admin_company','value_name'=>'name'];
        return $inputs;
    }

    /**
     * يعرض صفحة دخول حسابات الإدارة أو يحول الحساب المسجل بالفعل إلى المسار المخصص لدوره.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse الصفحة المطلوبة أو التحويل في الفرع المحدد.
     */
    public function login(){
        if (admin()->check()){
            return redirect()->route('admin.home');
        }
        return view('zerooonez-dashboard::screen.auth.login');
    }

    /**
     * يعرض نموذج طلب استعادة كلمة مرور الإدارة أو يحول الإداري المسجل إلى الرئيسية.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse الصفحة المطلوبة أو التحويل في الفرع المحدد.
     */
    public function forget_password(){
        if (admin()->check()){
            return redirect()->route('admin.home');
        }
        return view('zerooonez-dashboard::screen.auth.forget_password');
    }

    /**
     * يعرض نموذج تعيين كلمة مرور الإدارة مع الرمز الوارد في الرابط، أو يحول الحساب المسجل إلى الرئيسية.
     *
     * @param string $token رمز الاستعادة المأخوذ من الرابط؛ يتحقق منه مسار الإرسال. لا يستخدم مباشرة في جسم الدالة الحالي.
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse الصفحة المطلوبة أو التحويل في الفرع المحدد.
     */
    public function reset_password($token){
        if (admin()->check()){
            return redirect()->route('admin.home');
        }
        return view('zerooonez-dashboard::screen.auth.reset_password',compact('token'));
    }

    /**
     * يشفر كلمة المرور الواردة ويحفظها لحساب حسابات الإدارة الحالي ثم يحول إلى تسجيل الدخول.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: password.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function update_password(Request $request)
    {
        $password = Hash::make($request->password);
        $user = admin_user();
        $user->password = $password;
        $user->save();
        return redirect()->route('admin.login');
    }

    /**
     * يتحقق من البريد، ويحفظ رمزا مشفرا لاستعادة كلمة المرور، ثم يرسل رابط الاستعادة بالبريد.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: email.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function send_forget_password(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => bcrypt($token),
                'created_at' => Carbon::now()
            ]
        );

        $link = url("/admin/reset-password/$token?email=" . $request->email);
        $emailTitle = __('email.reset_password');
        $emailItems = [];
        $emailItems[] = [
            'title'=>__('email.reset_password_body'),
            'items'=>[
                ['title'=>__('admin.url').': ','body'=>$link],
            ],
        ];
        $this->send_EmailFun($request->email,$emailItems,$emailTitle,'Email.email');

        return back()->with('success', __('admin.success'));
    }

    /**
     * يتحقق من تطابق كلمة المرور وصلاحية الرمز لمدة ساعة، ويحدث كلمة المرور ويحذف الرمز المستخدم.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: email, token, password.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function send_reset_password(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required'
        ], [
            'email.required' => __('validation.email'),
            'email.email' => __('validation.invalid_email'),
            'password.required' => __('validation.password'),
            'password.confirmed' => __('validation.password_not_match'),
            'password.min' => __('validation.password_min_6'),
        ]);

        if ($validator->fails()) {

            return back()->with([
                'message' => $validator->errors()->first(),
                'type' => 'error'
            ])->withInput();
        }
        $check = Admin::where('email',$request->email)->first();
        if(!$check){
            return back()->with([
                'message' => __('validation.field'),
                'type' => 'error',
            ]);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->with([
                'message' => __('validation.field'),
                'type' => 'error',
            ]);
        }

        // check expiry (optional 60 min)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return back()->with([
                'message' => __('validation.expired'),
                'type' => 'error',
            ]);
        }

        // verify token
        if (!password_verify($request->token, $record->token)) {
            return back()->with([
                'message' => __('validation.expired'),
                'type' => 'error',
            ]);
        }

        // update password
        $check->update([
            'password' => Hash::make($request->password)
        ]);

        // delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('admin.login')->with([
            'type'=>'success',
            'message' => __('admin.success')
        ]);
    }

    /**
     * يحاول تسجيل الإدارة بالبريد وكلمة المرور، ويدعم مسار الحساب ذي كلمة المرور الفارغة الموجود حاليا.
     *
     * @param LoginAdminRequest $request الطلب؛ الحقول المستخدمة مباشرة: password, email.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function send_login(LoginAdminRequest $request){
        $credentials = $request->only('email', 'password');
        try {
            if(isset($request->password)&&$request->password){
                $token = admin()->attempt($credentials);
                if(!$token){
                    return back()->with([
                        'message' => __('validation.login_field'),
                        'type' => 'error',
                    ]);
//                    return $this->apiResponse(null,__('validation.login_field'),'simple',"500");
                }
                return  redirect()->route('admin.home');
            }else{
                $customer = Admin::where('email',$request->email)->whereNull('password')->first();
                if($customer){
                    Auth::guard('admin')->login($customer);
                    return  redirect()->route('admin.home');
                }else{
//                    return $this->apiResponse(null,__('validation.error'),'simple',"500");
                }
            }

        } catch (JWTException $e) {
//            return $this->apiResponse(null,__('validation.error'),'simple',"500");
        }
        return back()->with([
            'message' => __('validation.login_field'),
            'type' => 'error',
        ]);
    }

    /**
     * يجمع إعدادات شاشة حسابات الإدارة مع الأقسام وروابط الإجراءات المشتركة ثم يعرض table_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function index()
    {
        $columns = $this->columns;
        $sections = $this->dashboardPageSections();
        $data = $this->dashboardPageData($sections, [
            'columns' => $columns,
        ]);
        return view('zerooonez-dashboard::screen.table_view',compact('data'));
    }

    /**
     * يعيد صفوف حسابات الإدارة وأعدادها بصيغة DataTables عبر مسار التصفية والترتيب المشترك.
     *
     * @param Request $request طلب DataTables: draw وstart وlength وfilters والترتيب.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_datatable(Request $request)
    {

        $filterableColumns = $this->columns;

        return response()->json(
            $this->dashboardTableData($request, $filterableColumns)
        );
    }

    /**
     * يجهز أعمدة استعلام حسابات الإدارة والعلاقات المطلوبة لعرض صفوف الجدول دون جلب النتائج بعد.
     * الأعمدة المضافة: created_at, admin_tickets, admin_company_id.
     * العلاقات المحملة: role.
     *
     * @return \Illuminate\Database\Eloquent\Builder الاستعلام القابل لإضافة شروط أخرى.
     */
    private function initializeDataTableQuery()
    {
        $columns = $this->columns;
        $columns[] = 'created_at';
        $columns[] = 'admin_tickets';
        $columns[] = 'admin_company_id';
        return $this->model()->with('role')
            ->select($columns);
    }

    /**
     * يعيد قائمة اختيار حسابات الإدارة في صورة id وtext مع البحث والترقيم الحاليين.
     * يبحث في: name؛ وحجم الصفحة عشر نتائج.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: search, page.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_list(Request $request)
    {
        $search = $request->input('search');
        $page = $request->input('page');
        $skip = ($page -1)*10;

        $data = $this->model()->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");

        })
            ->select('id', 'name')
            ->skip($skip)
            ->take(10)
            ->get();

        $results = $data->map(function ($item)  {
            return [
                'id' => $item->id,
                'text' => $item->name,
            ];
        });

        return $this->successResponse($results, 'success', '200');
    }

    /**
     * يبحث في الإداريين غير المرتبطين بمسؤول خدمة عملاء ولديهم صلاحية view_customer_service، عشر نتائج لكل صفحة.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: search, page.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_list_super(Request $request)
    {
        $search = $request->input('search');
        $page = $request->input('page');
        $skip = ($page -1)*10;

        $data = $this->model()->whereDoesntHave('customer_service')->whereHas('role.permissions', function ($q) {
            $q->where('key_name', 'view_customer_service');
        })->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");

        })
            ->select('id', 'name')
            ->skip($skip)
            ->take(10)
            ->get();

        $results = $data->map(function ($item)  {
            return [
                'id' => $item->id,
                'text' => $item->name,
            ];
        });

        return $this->successResponse($results, 'success', '200');
    }

    /**
     * يبحث في الإداريين غير المرتبطين بموظف خدمة عملاء ولديهم صلاحية view_customer_service_employee، عشر نتائج لكل صفحة.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: search, page.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_list_super_customer_service(Request $request)
    {
        $search = $request->input('search');
        $page = $request->input('page');
        $skip = ($page -1)*10;
        $data = $this->model()->whereDoesntHave('customer_service_employee')->whereHas('role.permissions', function ($q) {
            $q->where('key_name', 'view_customer_service_employee');
        })->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");

        });

        $data = $data
            ->select('id', 'name')
            ->skip($skip)
            ->take(10)
            ->get();

        $results = $data->map(function ($item)  {
            return [
                'id' => $item->id,
                'text' => $item->name,
            ];
        });

        return $this->successResponse($results, 'success', '200');
    }

    /**
     * يجلب سجل حسابات الإدارة بالمعرف ويجهز بياناته لتعبئة نموذج التعديل.
     * يكشف الحقول المخفية كما يتطلب عقد التعديل الحالي؛ شكل الاستجابة لم يتغير.
     *
     * @param int|string $id معرف السجل المطلوب في قاعدة البيانات.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_single_item($id)
    {
        $data = $this->model()->with('role','admin_company')->find($id);
        $data->makeVisible($data->getHidden());
        return $this->successResponse($data, 'success', '200');
    }

    /**
     * يحفظ قيمة المفتاح admin_tickets لسجل حسابات الإدارة المحدد في request.id ثم يعيد استجابة الزر.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: id, value.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function update_admin_tickets(Request $request)
    {
        $data = $this->model()->find($request->id);
        $data->admin_tickets = $request->value;
        $data->save();
        return $this->successResponse(true, 'success', '200');
    }

    /**
     * يحفظ قيمة المفتاح active لسجل حسابات الإدارة المحدد في request.id ثم يعيد استجابة الزر.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: id, value.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function update_active(Request $request)
    {
        $data = $this->model()->find($request->id);
        $data->active = $request->value;
        $data->save();
        return $this->successResponse(true, 'success', '200');
    }

    /**
     * ينشئ حساب إدارة بعد استبعاد countries ويضع كلمة المرور الابتدائية المشفرة المحددة في الكود.
     *
     * @param Request $request طلب العملية بالحقول التي يعالجها هذا المسار.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function store(Request $request)
    {
        $data = $request->except('countries');
        $data['password'] = Hash::make('[REDACTED_SOURCE_DEFAULT]');
        $data = $this->model()->create($data);
        return $this->successResponse($data, 'success', '200');
    }

    /**
     * يرفض تكرار الهاتف أو البريد ثم يحدث بيانات الإداري؛ يبقى استبعاد password من الحفظ كما في التنفيذ الحالي.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: id, phone, email, new_password.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function update(Request $request)
    {
        $count = $this->model()->where('id','!=',$request->id)->where('phone',$request->phone)->count();
        if($count>0){
            return $this->errorResponse(__('validation.phone_unique'));
        }
        $count = $this->model()->where('id','!=',$request->id)->where('email',$request->email)->count();
        if($count>0){
            return $this->errorResponse(__('validation.email_unique'));
        }
        $data = $request->all();
        if(isset($request->new_password)&&$request->new_password!=null) {
            $data['password'] = Hash::make($request->new_password);
        }else{
            unset($data['new_password']);
        }
        unset($data['password']);
        $this->model()->find($request->id)->update($data);
        return $this->successResponse($data, 'success', '200');
    }

    /**
     * يحذف سجل حسابات الإدارة المحدد بالمعرف عبر Eloquent ثم يعيد استجابة نجاح.
     *
     * @param int|string $id معرف السجل المطلوب في قاعدة البيانات.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function delete($id)
    {
        $this->model()->find($id)->delete();
        return $this->successResponse('success', 'success', '200');
    }

    /**
     * يمر على ids ويحذف سجلات حسابات الإدارة باستدعاء delete لكل معرف للحفاظ على مسار الحذف الفردي.
     * يعيد قائمة المعرفات؛ يحتفظ السلوك الحالي بعدم تجميع استجابات كل حذف.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: ids.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function delete_all(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $this->delete($id);
        }
        return $this->successResponse($ids, 'success', '200');
    }

    /**
     * ينهي جلسة حارس حسابات الإدارة ثم يحول إلى المسار المحدد لتسجيل الخروج.
     *
     * @param Request $request طلب العملية بالحقول التي يعالجها هذا المسار. لا يستخدم مباشرة في جسم الدالة الحالي.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function logout(Request $request)
    {
        admin()->logout();
        return redirect()->route('admin.home');
    }
}
