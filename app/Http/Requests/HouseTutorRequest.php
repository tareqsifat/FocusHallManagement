<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class HouseTutorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower((string) $this->input('email'))]);
    }

    public function rules(): array
    {
        $tutor = $this->route('house_tutor');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($tutor?->user_id)],
            // Set on creation; leave blank when editing to keep the current password.
            'password' => [$tutor ? 'nullable' : 'required', Password::defaults()],
            'designation' => ['required', 'string', 'max:100'],
            'department' => ['required', Rule::in(config('hall.departments'))],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'block_ids' => ['required', 'array', 'min:1'],
            'block_ids.*' => ['integer', 'distinct', 'exists:blocks,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone must be a Bangladeshi mobile number like 01712345678.',
            'block_ids.required' => 'Assign the tutor to at least one block.',
        ];
    }
}
