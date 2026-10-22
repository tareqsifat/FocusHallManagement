<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentProfileUpdateRequest extends FormRequest
{
    /** Registration number, department, session and email come from the university record and stay locked. */
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleName::Student) && $this->user()->studentProfile !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone must be a Bangladeshi mobile number like 01712345678.',
        ];
    }
}
