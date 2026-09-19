<?php

namespace App\Http\Requests\Admin;

use App\Http\Traits\AjaxResponseTrait;
use App\Http\Traits\PaginateTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseAdminRequest extends FormRequest
{
    use AjaxResponseTrait;
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();
//        if(admin()->check()){
//            $errors = handleErrorMessage($errors);
//        }
        $errors = handleErrorMessage($errors);
        throw new HttpResponseException(
            $this->errorResponse($errors,)
        );
    }
}
