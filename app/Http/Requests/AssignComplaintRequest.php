<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', Rule::in(User::assignableTo($this->route('complaint'))->pluck('id'))],
        ];
    }

    public function messages(): array
    {
        return ['assignee_id.in' => 'Complaints can only be assigned to office staff or an active house tutor of the complaint\'s block.'];
    }
}
