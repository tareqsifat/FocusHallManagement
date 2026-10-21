<?php

namespace App\Http\Requests;

use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('complaint'));
    }

    public function rules(): array
    {
        return [
            'to_status' => ['required', Rule::enum(ComplaintStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toStatus(): ComplaintStatus
    {
        return ComplaintStatus::from($this->validated('to_status'));
    }
}
