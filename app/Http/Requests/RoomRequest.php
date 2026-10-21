<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'block_id' => ['required', 'exists:blocks,id'],
            'room_no' => [
                'required', 'string', 'max:10',
                Rule::unique(Room::class)->where('block_id', $this->input('block_id'))->ignore($this->route('room')?->id),
            ],
            'floor' => ['required', 'integer', 'between:0,20'],
            'capacity' => ['required', 'integer', 'between:1,6'],
        ];
    }

    public function messages(): array
    {
        return ['room_no.unique' => 'This block already has a room with that number.'];
    }
}
