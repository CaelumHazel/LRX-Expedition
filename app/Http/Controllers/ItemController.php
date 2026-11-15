<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Models\Item;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::latest()->paginate(20);

        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(StoreItemRequest $request)
    {
        Item::create($request->validated());

        return redirect()
            ->route('items.index')
            ->with('success', 'Item berhasil dibuat.');
    }

    public function show(Item $item)
    {
        $item->load('stockMovements');

        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(
        UpdateItemRequest $request,
        Item $item
    ) {
        $item->update($request->validated());

        return redirect()
            ->route('items.index')
            ->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        if ($item->shipmentItems()->exists()) {
            return back()->with(
                'error',
                'Item tidak dapat dihapus karena sudah digunakan shipment.'
            );
        }

        $item->delete();

        return redirect()
            ->route('items.index')
            ->with('success', 'Item berhasil dihapus.');
    }
}