<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VacateSeatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vacated_at' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
