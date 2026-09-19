<?php

namespace App\Http\Requests\Admin\Admin;

use App\Http\Requests\BaseRequest;

class UpdateAdminRequest extends BaseRequest
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
            'phone'=>'required|numeric',
            'name'=>'required',
            'email'=>'required|email',
        ];
    }
    public function messages()
    {
        return [
            'phone.required' => __('validation.phone'),
            'phone.numeric' => __('validation.numeric'),
            'email.email' => __('validation.correct_email'),
            'password.required' => __('validation.password'),
            'email.required' => __('validation.email'),
            'name.required' => __('validation.name'),
        ];
    }
}
