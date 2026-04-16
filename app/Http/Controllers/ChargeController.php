<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateChargesRequest;
use App\Http\Requests\SendChargeReminderRequest;
use App\Http\Requests\StoreChargePaymentRequest;
use App\Http\Requests\StoreChargeRequest;
use App\Mail\ChargeCompletedMail;
use App\Mail\ChargeReminderMail;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChargeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['', 'pending', 'in_validation', 'partial', 'paid', 'overdue', 'canceled'])],
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
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
                ->sum('amount') - (float) Charge::query()
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
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
                ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
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
                Charge::STATUS_IN_VALIDATION => 'En validacion',
                'overdue' => 'Vencido',
                Charge::STATUS_PARTIAL => 'Parcial',
                Charge::STATUS_PAID => 'Pagado',
                Charge::STATUS_CANCELED => 'Cancelado',
            ],
            'paymentMethods' => ChargePayment::METHOD_LABELS,
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

    public function show(Charge $charge): View
    {
        $charge->load([
            'tenant:id,full_name,email,phone_primary',
            'property.owners:id,name,phone,email,bank_name,clabe,account_holder',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        return view('charges.show', [
            'charge' => $charge,
            'paymentMethods' => ChargePayment::METHOD_LABELS,
        ]);
    }

    public function storePayment(StoreChargePaymentRequest $request, Charge $charge): RedirectResponse
    {
        if (!in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION], true)) {
            return redirect()->back()->with('error', 'Este cargo ya no admite pagos.');
        }

        $validated = $request->validated();
        $amount = (float) $validated['amount'];
        if ($amount > $charge->outstanding_amount) {
            return redirect()->back()->with('error', 'El monto no puede ser mayor al saldo pendiente.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store("charges/{$charge->id}/payments", 'public');
        }

        $becamePaid = false;
        DB::transaction(function () use ($charge, $validated, $amount, $receiptPath, $request, &$becamePaid): void {
            $charge->payments()->create([
                'amount' => $amount,
                'currency' => strtolower((string) config('services.stripe.currency', 'mxn')),
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'source' => ChargePayment::SOURCE_ADMIN,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['payment_date'],
                'paid_at' => Carbon::parse($validated['payment_date'])->endOfDay(),
                'registered_by' => $request->user()?->id,
            ]);

            $becamePaid = $charge->refreshPaymentStatus();
        });

        if ($becamePaid) {
            $this->sendCompletedMail($charge);
        }

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    public function validatePayment(Request $request, Charge $charge, ChargePayment $payment): RedirectResponse
    {
        if ($payment->charge_id !== $charge->id) {
            abort(404);
        }

        if ($payment->status !== ChargePayment::STATUS_PENDING_VALIDATION) {
            return redirect()->back()->with('error', 'Este pago ya fue validado o rechazado.');
        }

        $validated = $request->validate([
            'validation_notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $becamePaid = false;
        DB::transaction(function () use ($payment, $charge, $validated, $request, &$becamePaid): void {
            $payment->update([
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'validated_by' => $request->user()?->id,
                'validation_notes' => $validated['validation_notes'] ?? null,
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $becamePaid = $charge->refreshPaymentStatus();
        });

        if ($becamePaid) {
            $this->sendCompletedMail($charge);
        }

        return redirect()->back()->with('success', 'Comprobante validado correctamente.');
    }

    public function sendReminder(SendChargeReminderRequest $request, Charge $charge): RedirectResponse
    {
        $validated = $request->validated();
        $channel = (string) $validated['channel'];
        $daysBefore = (int) $validated['days_before'];
        $message = $validated['message'] ?? null;

        if ($channel === 'whatsapp') {
            return redirect()->back()->with('warning', 'WhatsApp aun no esta integrado. Usa correo por ahora.');
        }

        $charge->loadMissing(['tenant:id,full_name,email']);
        if (!filled($charge->tenant?->email)) {
            return redirect()->back()->with('error', 'El inquilino no tiene correo configurado.');
        }

        Mail::to($charge->tenant->email)->send(new ChargeReminderMail($charge, $daysBefore, $message));

        return redirect()->back()->with('success', 'Recordatorio enviado por correo.');
    }

    public function previewBulk(GenerateChargesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenant = Tenant::query()->findOrFail((int) $validated['tenant_id']);
        $paymentDay = (int) $validated['payment_day'];

        return response()->json([
            'preview' => $this->buildBulkPreview($tenant, $paymentDay),
        ]);
    }

    public function storeBulk(GenerateChargesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tenant = Tenant::query()->findOrFail((int) $validated['tenant_id']);
        $paymentDay = (int) $validated['payment_day'];
        $preview = $this->buildBulkPreview($tenant, $paymentDay);

        if (empty($preview['rows'])) {
            return redirect()->route('charges.index')->with('warning', 'No hay cargos por generar para ese inquilino.');
        }

        $created = 0;
        DB::transaction(function () use ($preview, &$created, $request): void {
            foreach ($preview['rows'] as $row) {
                if ($row['already_exists']) {
                    continue;
                }

                Charge::create([
                    'property_id' => $row['property_id'],
                    'tenant_id' => $row['tenant_id'],
                    'type' => Charge::TYPE_RENT,
                    'due_date' => $row['due_date'],
                    'amount' => $row['amount'],
                    'paid_amount' => 0,
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                    'concept' => $row['concept'],
                    'notes' => 'Generado automaticamente por contrato.',
                    'status' => Charge::STATUS_PENDING,
                    'created_by' => $request->user()?->id,
                ]);
                $created++;
            }
        });

        return redirect()
            ->route('charges.index')
            ->with('success', "Se generaron {$created} cargos de renta.");
    }

    private function buildBulkPreview(Tenant $tenant, int $paymentDay): array
    {
        $rows = [];

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('contract_starts_at')
            ->whereNotNull('contract_expires_at')
            ->with('tenant:id,full_name')
            ->orderBy('internal_name')
            ->get();

        foreach ($properties as $property) {
            if ((float) $property->monthly_rent_price <= 0) {
                continue;
            }

            $startsAt = $property->contract_starts_at?->copy()->startOfMonth();
            $expiresAt = $property->contract_expires_at?->copy()->startOfMonth();

            if (!$startsAt || !$expiresAt || $startsAt->gt($expiresAt)) {
                continue;
            }

            $cursor = $startsAt->copy();
            while ($cursor->lte($expiresAt)) {
                $day = min($paymentDay, $cursor->daysInMonth);
                $dueDate = $cursor->copy()->day($day)->toDateString();
                $periodMonth = (int) $cursor->month;
                $periodYear = (int) $cursor->year;
                $monthNames = [
                    1 => 'Enero',
                    2 => 'Febrero',
                    3 => 'Marzo',
                    4 => 'Abril',
                    5 => 'Mayo',
                    6 => 'Junio',
                    7 => 'Julio',
                    8 => 'Agosto',
                    9 => 'Septiembre',
                    10 => 'Octubre',
                    11 => 'Noviembre',
                    12 => 'Diciembre',
                ];
                $concept = 'Renta ' . ($monthNames[$periodMonth] ?? (string) $periodMonth) . ' ' . $periodYear;
                $alreadyExists = Charge::query()
                    ->where('property_id', $property->id)
                    ->where('tenant_id', $tenant->id)
                    ->where('type', Charge::TYPE_RENT)
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear)
                    ->exists();

                $rows[] = [
                    'property_id' => $property->id,
                    'property_name' => $property->internal_name,
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->full_name,
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'due_date' => $dueDate,
                    'amount' => (float) $property->monthly_rent_price,
                    'concept' => $concept,
                    'already_exists' => $alreadyExists,
                ];

                $cursor->addMonthNoOverflow()->startOfMonth();
            }
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'already_exists' => collect($rows)->where('already_exists', true)->count(),
                'to_create' => collect($rows)->where('already_exists', false)->count(),
            ],
        ];
    }

    private function sendCompletedMail(Charge $charge): void
    {
        $charge->loadMissing(['tenant:id,email,full_name']);
        if (!filled($charge->tenant?->email)) {
            return;
        }

        Mail::to($charge->tenant->email)->send(new ChargeCompletedMail($charge));
    }
}
