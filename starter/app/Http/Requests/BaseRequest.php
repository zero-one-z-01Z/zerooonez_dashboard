<?php

namespace App\Http\Requests;

use App\Http\Traits\PaginateTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    use PaginateTrait;
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();
//        if(admin()->check()){
//            $errors = handleErrorMessage($errors);
//        }
        $errors = handleErrorMessage($errors);
        throw new HttpResponseException(
            $this->apiResponse(null,$errors,'simple','422',false,422)
        );
    }
}
