<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_primary', 'like', "%{$search}%")
                        ->orWhere('phone_secondary', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('dossier_status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'complete' => Tenant::query()->where('dossier_status', Tenant::DOSSIER_COMPLETE)->count(),
            'in_review' => Tenant::query()->where('dossier_status', Tenant::DOSSIER_IN_REVIEW)->count(),
            'incomplete' => Tenant::query()->where('dossier_status', Tenant::DOSSIER_INCOMPLETE)->count(),
            'rejected' => Tenant::query()->where('dossier_status', Tenant::DOSSIER_REJECTED)->count(),
        ];

        return view('tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
            'dossierStatuses' => Tenant::DOSSIER_STATUS_LABELS,
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Tenant::create([
            'full_name' => $validated['full_name'],
            'phone_primary' => $validated['phone_primary'],
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'employer' => $validated['employer'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'employment_years' => $validated['employment_years'] ?? null,
            'personal_reference_name' => $validated['personal_reference_name'] ?? null,
            'personal_reference_phone' => $validated['personal_reference_phone'] ?? null,
            'work_reference_name' => $validated['work_reference_name'] ?? null,
            'work_reference_phone' => $validated['work_reference_phone'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'previous_address' => $validated['previous_address'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'dossier_status' => $validated['dossier_status'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'El inquilino se creo correctamente.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.edit', [
            'tenant' => $tenant,
            'dossierStatuses' => Tenant::DOSSIER_STATUS_LABELS,
        ]);
    }

    public function update(StoreTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();

        $tenant->update([
            'full_name' => $validated['full_name'],
            'phone_primary' => $validated['phone_primary'],
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'email' => $validated['email'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'employer' => $validated['employer'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'employment_years' => $validated['employment_years'] ?? null,
            'personal_reference_name' => $validated['personal_reference_name'] ?? null,
            'personal_reference_phone' => $validated['personal_reference_phone'] ?? null,
            'work_reference_name' => $validated['work_reference_name'] ?? null,
            'work_reference_phone' => $validated['work_reference_phone'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'previous_address' => $validated['previous_address'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'dossier_status' => $validated['dossier_status'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'El inquilino se actualizo correctamente.');
    }
}

