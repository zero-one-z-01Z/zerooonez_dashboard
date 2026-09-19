<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends \Illuminate\Database\Eloquent\Model
{
    protected  $appends = ['terms','privacy','about','insurance','terms_link','privacy_link','about_link','refund_link','insurance_link','app_id','package_name'];
    protected $hidden = ['terms_ar','terms_en','privacy_en','privacy_ar','about_ar','about_en','refund_en','refund_ar',
        'percentage_branch','percentage_delivery','terms','privacy','about','refund','created_at','updated_at','insurance_ar','insurance_en'];

    public function getAppIdAttribute()
    {
        return env("IOS_APP_STORE_ID");
    }
    public function getPackageNameAttribute()
    {
        return env("ANDROID_APP_STORE_ID");
    }
    public function getTermsAttribute(){
        $lang = app()->getLocale();
        return $this->attributes["terms_$lang"];
    }
    public function getInsuranceAttribute(){
        $lang = app()->getLocale();
        return $this->attributes["insurance_$lang"];
    }
    public function getPrivacyAttribute(){
        $lang = app()->getLocale();
        return $this->attributes["privacy_$lang"];
    }
    public function getAboutAttribute(){
        $lang = app()->getLocale();
        return $this->attributes["about_$lang"];
    }

    public function getRefundAttribute(){
        $lang = app()->getLocale();
        return $this->attributes["refund_$lang"];
    }

    public function getTermsLinkAttribute(){
        return route('terms_link');
    }
    public function getInsuranceLinkAttribute(){
        return route('insurance_link');
    }
    public function getPrivacyLinkAttribute(){
        return route('privacy_link');
    }
    public function getAboutLinkAttribute(){
        return route('about_link');
    }

    public function getRefundLinkAttribute(){
        return route('refund_link');
    }
}
