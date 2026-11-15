<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingRate\SaveShippingRateRequest;
use App\Models\Courier;
use App\Models\ShippingRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShippingRateController extends Controller
{
    public function index(): View
    {
        $this->assertAdmin();

        $rates = ShippingRate::with('courier')->orderBy('origin_city')
            ->orderBy('destination_city')
            ->paginate(20);
        $couriers = Courier::orderBy('name')->get(['id', 'name']);

        return view('shipping-rates.index', compact('rates', 'couriers'));
    }

    public function store(SaveShippingRateRequest $request): RedirectResponse
    {
        ShippingRate::create($request->validated() + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('shipping-rates.index')->with('success', 'Tarif pengiriman berhasil ditambahkan.');
    }

    public function update(SaveShippingRateRequest $request, ShippingRate $shippingRate): RedirectResponse
    {
        $shippingRate->update($request->validated() + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('shipping-rates.index')->with('success', 'Tarif pengiriman berhasil diperbarui.');
    }

    public function destroy(ShippingRate $shippingRate): RedirectResponse
    {
        $this->assertAdmin();
        $shippingRate->delete();

        return redirect()->route('shipping-rates.index')->with('success', 'Tarif pengiriman berhasil dihapus.');
    }

    private function assertAdmin(): void
    {
        if (auth()->user()?->role !== 'admin') {
            throw new HttpException(403, 'Hanya admin yang dapat mengelola tarif pengiriman.');
        }
    }
}
