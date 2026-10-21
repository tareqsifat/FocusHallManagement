<?php

namespace App\Http\Requests;

use App\Models\Club;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClubRequest extends FormRequest
{
    public function rules(): array
    {
        $club = $this->route('club');

        // Admins can be picked while creating; afterwards they change through ClubAdminsRequest.
        $admins = $club ? [] : ClubAdminsRequest::adminRules(null);

        return $admins + [
            'name' => ['required', 'string', 'max:100', Rule::unique(Club::class)->ignore($club?->id)],
            'description' => ['required', 'string', 'max:1000'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return ClubAdminsRequest::adminMessages();
    }
}
