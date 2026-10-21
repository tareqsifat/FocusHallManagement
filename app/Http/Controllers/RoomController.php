<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRequest;
use App\Models\Block;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::query()
            ->with('block')
            ->withOccupancy()
            ->when($request->integer('block_id'), fn ($q, $id) => $q->where('block_id', $id))
            ->when($request->query('availability') === 'free', fn ($q) => $q->whereHas('seats', fn ($s) => $s->where('is_active', true)->whereDoesntHave('activeAllocation')))
            ->orderBy('block_id')->orderBy('room_no')
            ->paginate(25)
            ->withQueryString();

        return view('rooms.index', ['rooms' => $rooms, 'blocks' => Block::orderBy('name')->get()]);
    }

    public function create(Request $request): View
    {
        $room = new Room(['block_id' => $request->integer('block_id') ?: null, 'floor' => 1]);
        $room->capacity = 4;

        return view('rooms.form', ['room' => $room, 'blocks' => Block::orderBy('name')->get()]);
    }

    public function store(RoomRequest $request, RoomService $rooms): RedirectResponse
    {
        $room = $rooms->create($request->validated());

        return redirect()->route('rooms.show', $room)->with('status', 'Room created.');
    }

    public function show(Room $room): View
    {
        $room->load(['block', 'seats' => fn ($q) => $q->orderBy('label'), 'seats.activeAllocation.student.user']);

        return view('rooms.show', compact('room'));
    }

    public function edit(Room $room): View
    {
        return view('rooms.form', ['room' => $room, 'blocks' => Block::orderBy('name')->get()]);
    }

    public function update(RoomRequest $request, Room $room, RoomService $rooms): RedirectResponse
    {
        $rooms->update($room, $request->validated());

        return redirect()->route('rooms.show', $room)->with('status', 'Room updated.');
    }
}
