<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChargeRequest;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChargeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['', 'pending', 'partial', 'paid', 'overdue', 'canceled'])],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $status = (string) ($filters['status'] ?? '');

        $charges = Charge::query()
            ->with(['tenant:id,full_name', 'property:id,internal_name,internal_reference'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('concept', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('property', function ($propertyQuery) use ($search) {
                            $propertyQuery
                                ->where('internal_name', 'like', "%{$search}%")
                                ->orWhere('internal_reference', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'overdue') {
                    $query
                        ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                        ->whereDate('due_date', '<', now()->toDateString());

                    return;
                }

                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $now = now();

        $stats = [
            'pending_amount' => (float) Charge::query()
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                ->sum('amount') - (float) Charge::query()
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                    ->sum('paid_amount'),
            'overdue_amount' => (float) Charge::query()
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                ->whereDate('due_date', '<', $now->toDateString())
                ->sum('amount') - (float) Charge::query()
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                    ->whereDate('due_date', '<', $now->toDateString())
                    ->sum('paid_amount'),
            'collected_month' => (float) ChargePayment::query()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->whereYear('paid_at', $now->year)
                ->whereMonth('paid_at', $now->month)
                ->sum('amount'),
            'pending_validation' => ChargePayment::query()
                ->where('status', ChargePayment::STATUS_PENDING)
                ->count(),
            'charges_count' => Charge::query()->count(),
            'payments_count' => ChargePayment::query()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->count(),
        ];

        return view('charges.index', [
            'charges' => $charges,
            'properties' => Property::query()
                ->orderBy('internal_name')
                ->get(['id', 'internal_name', 'internal_reference', 'tenant_id']),
            'tenants' => Tenant::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']),
            'typeOptions' => Charge::TYPE_LABELS,
            'statusOptions' => [
                '' => 'Todos',
                Charge::STATUS_PENDING => 'Pendiente',
                'overdue' => 'Vencido',
                Charge::STATUS_PARTIAL => 'Parcial',
                Charge::STATUS_PAID => 'Pagado',
                Charge::STATUS_CANCELED => 'Cancelado',
            ],
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
            'currentMonthLabel' => Carbon::create($now->year, $now->month, 1)->translatedFormat('M Y'),
        ]);
    }

    public function store(StoreChargeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Charge::create([
            'property_id' => $validated['property_id'],
            'tenant_id' => $validated['tenant_id'],
            'type' => $validated['type'],
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'concept' => $validated['concept'],
            'notes' => $validated['notes'] ?? null,
            'status' => Charge::STATUS_PENDING,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('charges.index')
            ->with('success', 'Cargo creado correctamente.');
    }
}
