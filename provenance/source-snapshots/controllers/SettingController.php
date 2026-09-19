<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\AjaxResponseTrait;
use App\Http\Traits\ImageTrait;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use AjaxResponseTrait,ImageTrait;

    /**
     * يحمل سجل الإعدادات الأول ويبني حقول الاتصال والبنك والقيم الرقمية والنصوص وملفات اللغة لشاشة الإعدادات.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public  function index(){
        $settings = Setting::first();
        $inputs[] = ["id"=>"email","input"=>"text","label"=>__('inputs.email'),'value'=>$settings->email,'type'=>"text",'required'=>true,];
        $inputs[] = ["id"=>"iban","input"=>"text","label"=>__('inputs.iban'),'value'=>$settings->iban,'type'=>"text",'required'=>true,];
        $inputs[] = ["id"=>"bank_name","input"=>"text","label"=>__('inputs.bank_name'),'value'=>$settings->bank_name,'type'=>"text",'required'=>true];
        $data = ['tax_percentage','gift_value','register_gift','update_profile_gift','bill_paid_gift','phone','scanner_range','auto_cancel_min_auction','user_version','user_ios_version','privacy_version'];
        foreach ($data as $input){
            $inputs[] = ["id"=>$input,"input"=>"text","label"=>__('inputs.'.$input),'value'=>$settings->$input,'type'=>"number",];
        }
        $status = ['auto','manually',];
        $items = [];
        foreach ($status as $s){
            $selected = $settings->scanner_auto == $s;
            $items[] = ["value"=>$s, "text"=>__('admin.'.$s),'selected'=>$selected];
        }
        $inputs[] = ['label'=>__('admin.scanner_auto'),'width'=>6,'input'=>'single','id'=>'scanner_auto',
            "type"=>"single",'required'=>true,'value'=>"",'select2'=>false,
            "items"=>$items,
        ];

        $inputs[] = ["id"=>"instagram","input"=>"text","label"=>__('inputs.instagram'),'value'=>$settings->instagram,'type'=>"text",'required'=>true];
        $inputs[] = ["id"=>"snapchat","input"=>"text","label"=>__('inputs.snapchat'),'value'=>$settings->snapchat,'type'=>"text",'required'=>true];

        $inputs[] = ["id"=>"terms_ar","input"=>"html","label"=>__('inputs.terms_ar'),'value'=>$settings->terms_ar,'type'=>"html",];
        $inputs[] = ["id"=>"terms_en","input"=>"html","label"=>__('inputs.terms_en'),'value'=>$settings->terms_en,'type'=>"html",];
        $inputs[] = ["id"=>"privacy_ar","input"=>"html","label"=>__('inputs.privacy_ar'),'value'=>$settings->privacy_ar,'type'=>"html",];
        $inputs[] = ["id"=>"privacy_en","input"=>"html","label"=>__('inputs.privacy_en'),'value'=>$settings->privacy_en,'type'=>"html",];
        $inputs[] = ["id"=>"insurance_ar","input"=>"html","label"=>__('inputs.insurance_ar'),'value'=>$settings->insurance_ar,'type'=>"html",];
        $inputs[] = ["id"=>"insurance_en","input"=>"html","label"=>__('inputs.insurance_en'),'value'=>$settings->insurance_en,'type'=>"html",];


        $inputs[] = ["id"=>"about_ar","input"=>"html","label"=>__('inputs.about_ar'),'value'=>$settings->about_ar,'type'=>"html",];
        $inputs[] = ["id"=>"about_en","input"=>"html","label"=>__('inputs.about_en'),'value'=>$settings->about_en,'type'=>"html",];
        $inputs[] = ["id"=>"refund_ar","input"=>"html","label"=>__('inputs.refund_ar'),'value'=>$settings->refund_ar,'type'=>"html",];
        $inputs[] = ["id"=>"refund_en","input"=>"html","label"=>__('inputs.refund_en'),'value'=>$settings->refund_en,'type'=>"html",];
        $inputs[] = ["id"=>"user_ar","input"=>"file","label"=>__('inputs.user_ar_file'),'value'=>"",'type'=>"file",];
        $inputs[] = ["id"=>"user_en","input"=>"file","label"=>__('inputs.user_en_file'),'value'=>"",'type'=>"file",];

        $data['inputs'] = $inputs;
        $data['id'] = $settings->id;
        return view('zerooonez-dashboard::screen.settings_view',compact('data'));
    }

    /**
     * يحفظ الإعدادات ويستبدل ملفي ترجمة التطبيق ar.json وen.json عند رفعهما، ثم يعود برسالة نجاح.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: user_ar, user_en.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public  function update(Request $request){
        Setting::first()->update($request->except('user_ar','user_en','branch_ar','branch_en','delivery_ar','delivery_en'));
        if($request->hasFile('user_ar')){
            $this->addLocalImage($request->user_ar,'user',null,'app_languages','ar.json');
        }
        if($request->hasFile('user_en')){
            $this->addLocalImage($request->user_en,'user',null,'app_languages','en.json');
        }


        return redirect()->back()->with([
            'message' => __('admin.success'),
            'type' => 'success'
        ]);
    }


    /**
     * يحمل نص الخصوصية من سجل الإعدادات الأول ويعرضه في webview.web_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function privacy(){
        $settings = Setting::first();
        $title = __('admin.policy');
        $data = [];
        $data['title'] = $title;
        $data['body'] = $settings->privacy;
        return view('webview.web_view',compact('data'));
    }

    /**
     * يحمل نص التأمين من سجل الإعدادات الأول ويعرضه في webview.web_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function insurance(){
        $settings = Setting::first();
        $title = __('admin.insurance');
        $data = [];
        $data['title'] = $title;
        $data['body'] = $settings->insurance;
        return view('webview.web_view',compact('data'));
    }

    /**
     * يحمل نص الشروط من سجل الإعدادات الأول ويعرضه في webview.web_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function terms(){
        $settings = Setting::first();
        $title = __('admin.terms');
        $data['title'] = $title;
        $data['body'] = $settings->terms;
        return view('webview.web_view',compact('data'));
    }

    /**
     * يحمل نص التعريف بالتطبيق من سجل الإعدادات الأول ويعرضه في webview.web_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function about(){
        $settings = Setting::first();
        $title = __('admin.about');
        $data['title'] = $title;
        $data['body'] = $settings->about;
        return view('webview.web_view',compact('data'));
    }

    /**
     * يحمل نص الاسترجاع من سجل الإعدادات الأول ويعرضه في webview.web_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function refund(){
        $settings = Setting::first();
        $title = __('admin.refund');
        $data['title'] = $title;
        $data['body'] = $settings->refund;
        return view('webview.web_view',compact('data'));
    }
}
