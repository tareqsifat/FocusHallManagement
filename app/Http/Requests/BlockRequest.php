<?php

namespace App\Http\Requests;

use App\Models\Block;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        $ignore = $this->route('block')?->id;

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique(Block::class)->ignore($ignore)],
            'code' => ['required', 'alpha_num', 'max:10', Rule::unique(Block::class)->ignore($ignore)],
        ];
    }
}
