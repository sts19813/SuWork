<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateChargesRequest;
use App\Http\Requests\SendChargeReminderRequest;
use App\Http\Requests\StoreChargePaymentRequest;
use App\Http\Requests\StoreChargeRequest;
use App\Http\Requests\UpdateChargeRequest;
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
use Illuminate\Support\Collection;
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
                ->with('tenant:id,full_name')
                ->orderBy('internal_name')
                ->get(['id', 'internal_name', 'internal_reference', 'tenant_id']),
            'chargeableProperties' => Property::query()
                ->with('tenant:id,full_name')
                ->whereNotNull('tenant_id')
                ->orderBy('internal_name')
                ->get([
                    'id',
                    'internal_name',
                    'internal_reference',
                    'tenant_id',
                    'contract_starts_at',
                    'contract_expires_at',
                    'monthly_rent_price',
                ]),
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

        $charge = Charge::create([
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

        $this->syncPropertyPlanWithCharge($charge);

        return redirect()
            ->route('charges.index')
            ->with('success', 'Cargo creado correctamente.');
    }

    public function update(UpdateChargeRequest $request, Charge $charge): RedirectResponse
    {
        if (in_array($charge->status, [Charge::STATUS_PAID, Charge::STATUS_CANCELED], true)) {
            return redirect()->route('charges.index')->with('error', 'Este cargo no se puede editar por su estado actual.');
        }

        $validated = $request->validated();
        $newAmount = (float) $validated['amount'];
        if ($newAmount < (float) $charge->paid_amount) {
            return redirect()->route('charges.index')->with(
                'error',
                'El monto no puede ser menor al total ya pagado de este cargo.',
            );
        }

        DB::transaction(function () use ($charge, $validated): void {
            $charge->update([
                'type' => $validated['type'],
                'due_date' => $validated['due_date'],
                'amount' => $validated['amount'],
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
                'concept' => $validated['concept'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $charge->refreshPaymentStatus();
            $this->syncPropertyPlanWithCharge($charge);
        });

        return redirect()->route('charges.index')->with('success', 'Cargo actualizado correctamente.');
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
        $property = Property::query()
            ->with('tenant:id,full_name')
            ->findOrFail((int) $validated['property_id']);

        return response()->json([
            'preview' => $this->buildBulkPreview($property, $validated['rows'] ?? null),
        ]);
    }

    public function storeBulk(GenerateChargesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $property = Property::query()
            ->with('tenant:id,full_name')
            ->findOrFail((int) $validated['property_id']);

        if (!$property->tenant_id) {
            return redirect()->route('charges.index')->with(
                'warning',
                'La propiedad no tiene inquilino activo. Asigna un inquilino antes de generar cargos.',
            );
        }

        $preview = $this->buildBulkPreview($property, $validated['rows'] ?? null);

        if (empty($preview['rows'])) {
            return redirect()->route('charges.index')->with(
                'warning',
                'No hay cargos por generar para esta propiedad.',
            );
        }

        $created = 0;
        DB::transaction(function () use ($preview, &$created, $request, $property): void {
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
                    'notes' => $row['notes'] ?? 'Generado automaticamente por contrato.',
                    'status' => Charge::STATUS_PENDING,
                    'created_by' => $request->user()?->id,
                ]);
                $created++;
            }

            $this->syncPropertyPlanFromBulk($property, $preview['rows']);
        });

        $message = $created > 0
            ? "Se generaron {$created} cargos de renta."
            : 'No se crearon cargos nuevos porque todos ya existen.';

        return redirect()
            ->route('charges.index')
            ->with('success', $message);
    }

    private function buildBulkPreview(Property $property, ?array $requestRows = null): array
    {
        if (!$property->tenant_id) {
            return [
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'already_exists' => 0,
                    'to_create' => 0,
                ],
            ];
        }

        $rowsSource = $this->normalizeBulkRows($requestRows);
        if ($rowsSource->isEmpty()) {
            $rowsSource = $this->getPropertyPlanRows($property);
        }

        $rows = [];
        foreach ($rowsSource as $row) {
            $periodMonth = (int) $row['period_month'];
            $periodYear = (int) $row['period_year'];
            $amount = (float) $row['amount'];
            if ($amount <= 0) {
                continue;
            }

            $alreadyExists = Charge::query()
                ->where('property_id', $property->id)
                ->where('tenant_id', $property->tenant_id)
                ->where('type', Charge::TYPE_RENT)
                ->where('period_month', $periodMonth)
                ->where('period_year', $periodYear)
                ->exists();

            $rows[] = [
                'property_id' => $property->id,
                'property_name' => $property->internal_name,
                'tenant_id' => (int) $property->tenant_id,
                'tenant_name' => $property->tenant?->full_name ?? '-',
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => (string) $row['due_date'],
                'amount' => $amount,
                'concept' => (string) $row['concept'],
                'notes' => $row['notes'] ?? null,
                'already_exists' => $alreadyExists,
            ];
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

    private function getPropertyPlanRows(Property $property): Collection
    {
        $storedRows = $this->normalizeBulkRows($property->rent_charge_plan);
        if ($storedRows->isNotEmpty()) {
            return $storedRows;
        }

        return $this->generateFallbackRowsFromContract($property);
    }

    private function normalizeBulkRows(?array $rows): Collection
    {
        return collect($rows ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): ?array {
                $periodMonth = (int) ($row['period_month'] ?? 0);
                $periodYear = (int) ($row['period_year'] ?? 0);
                if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000) {
                    return null;
                }

                try {
                    $dueDate = Carbon::parse((string) ($row['due_date'] ?? now()->toDateString()))
                        ->toDateString();
                } catch (\Throwable) {
                    $dueDate = now()->toDateString();
                }
                $amount = (float) ($row['amount'] ?? 0);
                $concept = trim((string) ($row['concept'] ?? ''));

                return [
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'concept' => $concept !== '' ? $concept : $this->buildRentConcept($periodMonth, $periodYear),
                    'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                    'is_custom_amount' => (bool) ($row['is_custom_amount'] ?? false),
                ];
            })
            ->filter()
            ->values();
    }

    private function generateFallbackRowsFromContract(Property $property): Collection
    {
        if (
            !$property->contract_starts_at ||
            !$property->contract_expires_at ||
            (float) $property->monthly_rent_price <= 0
        ) {
            return collect();
        }

        $startsAt = $property->contract_starts_at->copy();
        $contractDay = (int) $startsAt->day;
        $startsAt = $startsAt->startOfMonth();
        $expiresAt = $property->contract_expires_at->copy()->startOfMonth();
        if ($startsAt->gt($expiresAt)) {
            return collect();
        }

        $rows = [];
        $cursor = $startsAt->copy();
        while ($cursor->lte($expiresAt)) {
            $periodMonth = (int) $cursor->month;
            $periodYear = (int) $cursor->year;
            $rows[] = [
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => $cursor->copy()->day(min($contractDay, $cursor->daysInMonth))->toDateString(),
                'amount' => (float) $property->monthly_rent_price,
                'concept' => $this->buildRentConcept($periodMonth, $periodYear),
                'notes' => null,
                'is_custom_amount' => false,
            ];
            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return collect($rows);
    }

    private function syncPropertyPlanFromBulk(Property $property, array $rows): void
    {
        $planRows = $this->normalizeBulkRows($property->rent_charge_plan)
            ->keyBy(fn (array $row) => $this->periodKey((int) $row['period_year'], (int) $row['period_month']));

        foreach ($rows as $row) {
            $periodMonth = (int) ($row['period_month'] ?? 0);
            $periodYear = (int) ($row['period_year'] ?? 0);
            if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000) {
                continue;
            }

            $planRows->put($this->periodKey($periodYear, $periodMonth), [
                'type' => Charge::TYPE_RENT,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => (string) ($row['due_date'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'concept' => (string) ($row['concept'] ?? $this->buildRentConcept($periodMonth, $periodYear)),
                'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                'is_custom_amount' => true,
            ]);
        }

        $property->forceFill([
            'rent_charge_plan' => $planRows
                ->values()
                ->sortBy(fn (array $row) => ((int) $row['period_year'] * 100) + (int) $row['period_month'])
                ->values()
                ->all(),
        ])->save();
    }

    private function syncPropertyPlanWithCharge(Charge $charge): void
    {
        if ($charge->type !== Charge::TYPE_RENT || !$charge->property_id) {
            return;
        }

        $property = Property::query()->find($charge->property_id);
        if (!$property) {
            return;
        }

        $planRows = $this->normalizeBulkRows($property->rent_charge_plan)
            ->keyBy(fn (array $row) => $this->periodKey((int) $row['period_year'], (int) $row['period_month']));
        $periodKey = $this->periodKey((int) $charge->period_year, (int) $charge->period_month);

        $planRows->put($periodKey, [
            'type' => Charge::TYPE_RENT,
            'period_month' => (int) $charge->period_month,
            'period_year' => (int) $charge->period_year,
            'due_date' => $charge->due_date?->toDateString(),
            'amount' => (float) $charge->amount,
            'concept' => (string) $charge->concept,
            'notes' => $charge->notes,
            'is_custom_amount' => true,
        ]);

        $property->forceFill([
            'rent_charge_plan' => $planRows
                ->values()
                ->sortBy(fn (array $row) => ((int) $row['period_year'] * 100) + (int) $row['period_month'])
                ->values()
                ->all(),
        ])->save();
    }

    private function periodKey(int $periodYear, int $periodMonth): string
    {
        return sprintf('%04d-%02d', $periodYear, $periodMonth);
    }

    private function buildRentConcept(int $periodMonth, int $periodYear): string
    {
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

        return 'Renta ' . ($monthNames[$periodMonth] ?? (string) $periodMonth) . ' ' . $periodYear;
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
