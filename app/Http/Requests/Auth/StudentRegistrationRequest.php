<?php

namespace App\Http\Requests\Auth;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StudentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // People often type the session with an en dash (2023–24); store a plain hyphen.
        $this->merge([
            'session' => str_replace(['–', '—'], '-', (string) $this->input('session')),
            'email' => strtolower((string) $this->input('email')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_no' => ['required', 'digits:10', Rule::unique(StudentProfile::class)],
            'department' => ['required', Rule::in(config('hall.departments'))],
            'session' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['required', 'email', 'max:255', 'ends_with:@student.du.test', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('session')) {
                    return;
                }
                [$start, $end] = explode('-', $this->input('session'));
                if (((int) $start + 1) % 100 !== (int) $end) {
                    $validator->errors()->add('session', 'The session must span two consecutive years, e.g. 2023-24.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'session.regex' => 'The session must look like 2023-24.',
            'phone.regex' => 'The phone must be a Bangladeshi mobile number like 01712345678.',
            'email.ends_with' => 'Use your university email ending in @student.du.test.',
        ];
    }
}
