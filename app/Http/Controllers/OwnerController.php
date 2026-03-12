<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOwnerRequest;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $owners = Owner::query()
            ->withCount('properties')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('owners.index', [
            'owners' => $owners,
            'search' => $search,
            'ownerTypes' => Owner::OWNER_TYPE_LABELS,
            'paymentMethods' => Owner::PAYMENT_METHOD_LABELS,
        ]);
    }

    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Owner::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'owner_type' => $validated['owner_type'] ?? Owner::OWNER_INDIVIDUAL,
            'bank_name' => $validated['bank_name'] ?? null,
            'clabe' => $validated['clabe'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'payment_method' => $validated['payment_method'] ?? Owner::PAYMENT_METHOD_TRANSFER,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('owners.index')
            ->with('success', 'El propietario se creó correctamente.');
    }

    public function edit(Owner $owner): View
    {
        return view('owners.edit', [
            'owner' => $owner,
            'ownerTypes' => Owner::OWNER_TYPE_LABELS,
            'paymentMethods' => Owner::PAYMENT_METHOD_LABELS,
        ]);
    }

    public function update(StoreOwnerRequest $request, Owner $owner): RedirectResponse
    {
        $validated = $request->validated();

        $owner->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'owner_type' => $validated['owner_type'] ?? Owner::OWNER_INDIVIDUAL,
            'bank_name' => $validated['bank_name'] ?? null,
            'clabe' => $validated['clabe'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'payment_method' => $validated['payment_method'] ?? Owner::PAYMENT_METHOD_TRANSFER,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('owners.index')
            ->with('success', 'El propietario se actualizó correctamente.');
    }
}

