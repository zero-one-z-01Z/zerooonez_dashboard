<?php

namespace App\Http\Requests\Admin\OptionalPhone;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest;

class UpdatePhoneOptionalRequest extends BaseRequest
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
            'phone' => [
                'required',
                Rule::unique('optional_phones')->ignore($this->id),
            ],
        ];
    }
    public function messages()
    {
        return [
            'phone.required' => __('validation.phone_required'),
            'phone.unique' => __('validation.unique_phone'),
        ];
    }
}
