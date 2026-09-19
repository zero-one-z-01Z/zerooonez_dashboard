<?php

namespace App\Http\Requests\Admin\Admin;

use App\Http\Requests\BaseRequest;

class RegisterAdminRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone'=>'required|numeric|unique:admins',
            'name'=>'required',
            'email'=>'required|unique:admins|email',
            'password'=>'required|min:6',
        ];
    }
    public function messages()
    {
        return [
            'phone.required' => __('validation.phone'),
            'phone.numeric' => __('validation.numeric'),
            'phone.unique' => __('validation.unique_phone'),
            'email.unique' => __('validation.unique_email'),
            'email.email' => __('validation.correct_email'),
            'password.required' => __('validation.password'),
            'email.required' => __('validation.email'),
            'name.required' => __('validation.name'),
            'password.min'=>__('validation.min_password'),
        ];
    }
}
