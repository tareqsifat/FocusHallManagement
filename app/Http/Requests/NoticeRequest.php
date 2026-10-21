<?php

namespace App\Http\Requests;

use App\Enums\NoticeStatus;
use App\Policies\NoticePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoticeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            // Club admins may only choose the Club category.
            'category' => ['required', Rule::in(array_map(fn ($c) => $c->value, NoticePolicy::categoriesFor($this->user())))],
            'status' => ['required', Rule::enum(NoticeStatus::class)],
            'is_pinned' => ['boolean'],
            // BR-13: PDF only (extension and sniffed type), max 5 MB.
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'],
            'remove_attachment' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'You can only publish notices in the Club category.',
            'attachment.mimes' => 'The attachment must be a PDF.',
            'attachment.mimetypes' => 'The attachment must be a PDF.',
            'attachment.max' => 'The attachment may not be larger than 5 MB.',
        ];
    }
}
