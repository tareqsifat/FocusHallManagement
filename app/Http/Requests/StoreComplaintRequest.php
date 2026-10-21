<?php

namespace App\Http\Requests;

use App\Enums\ComplaintCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        $isResident = $this->user()->studentProfile?->currentAllocation()->exists();

        return [
            'category' => ['required', Rule::enum(ComplaintCategory::class)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            // Residents get their room filled in; attached students must say where the problem is.
            'location_text' => [$isResident ? 'nullable' : 'required', 'string', 'max:255'],
            // BR-13: content-sniffed type, extension and size (2 MB).
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_text.required' => 'You do not have a seat in the hall, so please say where the problem is.',
            'photo.max' => 'The photo may not be larger than 2 MB.',
        ];
    }
}
