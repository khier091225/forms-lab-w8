<?php

namespace App\Http\Requests;

use App\Rules\ValidCourseCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends StoreCourseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = [
            'required', 'string', 'max:20', new ValidCourseCode,
            Rule::unique('courses', 'code')->ignore($this->route('course')),
        ];

        return $rules;
    }
}
