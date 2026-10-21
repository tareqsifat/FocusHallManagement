<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlockRequest;
use App\Models\Block;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlockController extends Controller
{
    public function index(): View
    {
        $blocks = Block::query()
            ->withCount('rooms')
            ->withSum('rooms', 'capacity')
            ->withCount(['seats as occupied_count' => fn ($q) => $q->whereHas('activeAllocation')])
            ->orderBy('name')
            ->paginate(20);

        return view('blocks.index', compact('blocks'));
    }

    public function create(): View
    {
        return view('blocks.form', ['block' => new Block]);
    }

    public function store(BlockRequest $request): RedirectResponse
    {
        Block::create($request->validated());

        return redirect()->route('blocks.index')->with('status', 'Block created.');
    }

    public function edit(Block $block): View
    {
        return view('blocks.form', compact('block'));
    }

    public function update(BlockRequest $request, Block $block): RedirectResponse
    {
        $block->update($request->validated());

        return redirect()->route('blocks.index')->with('status', 'Block updated.');
    }
}
