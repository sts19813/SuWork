<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Expense;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();

        $periodStart = $selectedMonth->copy()->startOfMonth();
        $periodEnd = $selectedMonth->copy()->endOfMonth();

        $kpis = $this->buildKpis($periodStart, $periodEnd);
        $collectionSummary = $this->buildCollectionSummary($periodStart, $periodEnd, $selectedMonth);
        $alerts = $this->buildImportantAlerts($periodStart, $periodEnd, $selectedMonth);
        $propertySummaries = $this->buildPropertySummaries($selectedMonth);
        $profitability = $this->buildProfitabilitySummary($selectedMonth);

        return view('dashboard', [
            'selectedMonth' => $selectedMonth,
            'monthOptions' => $this->monthOptions($selectedMonth, 12),
            'dashboardKpis' => $kpis,
            'collectionSummary' => $collectionSummary,
            'importantAlerts' => $alerts,
            'propertySummaries' => $propertySummaries,
            'profitabilitySummary' => $profitability,
        ]);
    }

    private function buildKpis(Carbon $periodStart, Carbon $periodEnd): array
    {
        $chargesForMonth = Charge::query()
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);

        $expectedIncome = (float) (clone $chargesForMonth)->sum('amount');
        $pendingOutstanding = (float) (clone $chargesForMonth)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));

        return [
            [
                'label' => 'Total de propiedades',
                'value' => number_format((int) Property::query()->count()),
                'icon' => 'bi-house-door',
                'tone' => 'primary',
            ],
            [
                'label' => 'Propiedades ocupadas',
                'value' => number_format((int) Property::query()->whereIn('status', $this->occupiedStatuses())->count()),
                'icon' => 'bi-buildings',
                'tone' => 'success',
            ],
            [
                'label' => 'Ingreso mensual esperado',
                'value' => $this->money($expectedIncome),
                'icon' => 'bi-graph-up-arrow',
                'tone' => 'info',
            ],
            [
                'label' => 'Cobrado de este mes',
                'value' => $this->money((float) ChargePayment::query()
                    ->where('status', ChargePayment::STATUS_SUCCEEDED)
                    ->whereBetween('paid_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
                    ->sum('amount')),
                'icon' => 'bi-check2-circle',
                'tone' => 'success',
            ],
            [
                'label' => 'Pendiente por cobrar',
                'value' => $this->money(max(0, $pendingOutstanding)),
                'icon' => 'bi-hourglass-split',
                'tone' => 'warning',
            ],
            [
                'label' => 'Gastos totales del mes',
                'value' => $this->money((float) Expense::query()
                    ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('amount')),
                'icon' => 'bi-receipt-cutoff',
                'tone' => 'danger',
            ],
        ];
    }

    private function buildCollectionSummary(Carbon $periodStart, Carbon $periodEnd, Carbon $selectedMonth): array
    {
        $charges = Charge::query()
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $paid = 0.0;
        $pending = 0.0;
        $overdue = 0.0;

        foreach ($charges as $charge) {
            $paid += min((float) $charge->paid_amount, (float) $charge->amount);
            $outstanding = max(0, (float) $charge->amount - (float) $charge->paid_amount);

            if ($outstanding <= 0) {
                continue;
            }

            if ($this->isChargeOverdueForMonth($charge, $selectedMonth)) {
                $overdue += $outstanding;
            } else {
                $pending += $outstanding;
            }
        }

        $total = max(1, $paid + $pending + $overdue);

        return [
            'total' => $total,
            'series' => [round($paid, 2), round($pending, 2), round($overdue, 2)],
            'segments' => [
                [
                    'label' => 'Cobrado',
                    'value' => round($paid, 2),
                    'percent' => round(($paid / $total) * 100),
                    'color' => '#0bb783',
                ],
                [
                    'label' => 'Pendiente',
                    'value' => round($pending, 2),
                    'percent' => round(($pending / $total) * 100),
                    'color' => '#f59e0b',
                ],
                [
                    'label' => 'Vencido',
                    'value' => round($overdue, 2),
                    'percent' => round(($overdue / $total) * 100),
                    'color' => '#f1416c',
                ],
            ],
        ];
    }

    private function buildImportantAlerts(Carbon $periodStart, Carbon $periodEnd, Carbon $selectedMonth): Collection
    {
        $contractAlerts = Property::query()
            ->with(['tenant:id,full_name'])
            ->whereBetween('contract_expires_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('contract_expires_at')
            ->get()
            ->map(function (Property $property): array {
                return [
                    'tone' => 'warning',
                    'icon' => 'bi-file-earmark-text',
                    'title' => $property->internal_name,
                    'subtitle' => 'Contrato vence ' . optional($property->contract_expires_at)->translatedFormat('d M Y'),
                    'detail' => $property->tenant?->full_name ?: 'Sin inquilino asignado',
                    'route' => route('properties.show', $property),
                ];
            });

        $overdueAlerts = Charge::query()
            ->with(['property:id,uuid,internal_name', 'tenant:id,full_name'])
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Charge $charge) => $this->isChargeOverdueForMonth($charge, $selectedMonth))
            ->map(function (Charge $charge): array {
                return [
                    'tone' => 'danger',
                    'icon' => 'bi-exclamation-octagon',
                    'title' => $charge->property?->internal_name ?: 'Propiedad',
                    'subtitle' => 'Atraso de cobranza por ' . $this->money($charge->outstanding_amount),
                    'detail' => ($charge->tenant?->full_name ?: 'Sin inquilino') . ' · vence ' . optional($charge->due_date)->translatedFormat('d M Y'),
                    'route' => route('charges.show', $charge),
                ];
            });

        return $contractAlerts
            ->concat($overdueAlerts)
            ->take(8)
            ->values();
    }

    private function buildPropertySummaries(Carbon $selectedMonth): Collection
    {
        $referenceDate = $this->referenceDateForMonth($selectedMonth);

        return Property::query()
            ->with([
                'tenant:id,full_name',
                'advisor:id,name',
                'charges' => fn ($query) => $query
                    ->where('status', '!=', Charge::STATUS_CANCELED)
                    ->whereDate('due_date', '<=', $referenceDate->toDateString())
                    ->orderBy('due_date'),
            ])
            ->whereIn('status', $this->occupiedStatuses())
            ->orderBy('internal_name')
            ->get()
            ->map(function (Property $property) use ($selectedMonth): array {
                $overdueAmount = 0.0;
                $pendingAmount = 0.0;

                foreach ($property->charges as $charge) {
                    $outstanding = max(0, (float) $charge->amount - (float) $charge->paid_amount);
                    if ($outstanding <= 0) {
                        continue;
                    }

                    if ($this->isChargeOverdueForMonth($charge, $selectedMonth)) {
                        $overdueAmount += $outstanding;
                    } else {
                        $pendingAmount += $outstanding;
                    }
                }

                [$statusLabel, $tone] = match (true) {
                    $overdueAmount > 0 => ['Atrasado', 'danger'],
                    $pendingAmount > 0 => ['Pendiente', 'warning'],
                    default => ['Al corriente', 'success'],
                };

                return [
                    'property' => $property,
                    'tenant_name' => $property->tenant?->full_name ?: $property->current_tenant_name ?: '-',
                    'advisor_name' => $property->advisor?->name ?: 'Sin asesor',
                    'rent_amount' => (float) ($property->monthly_rent_price ?? 0),
                    'status_label' => $statusLabel,
                    'status_tone' => $tone,
                    'overdue_amount' => round($overdueAmount, 2),
                    'pending_amount' => round($pendingAmount, 2),
                ];
            });
    }

    private function buildProfitabilitySummary(Carbon $selectedMonth): array
    {
        $months = collect(range(5, 0))->map(fn ($offset) => $selectedMonth->copy()->subMonths($offset));

        $series = $months->map(function (Carbon $month): array {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $income = (float) ChargePayment::query()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->whereBetween('paid_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                ->sum('amount');

            $expenses = (float) Expense::query()
                ->where(function ($query) use ($monthStart, $monthEnd): void {
                    $query->whereBetween('paid_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                        ->orWhere(function ($nested) use ($monthStart, $monthEnd): void {
                            $nested->whereNull('paid_at')
                                ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                        });
                })
                ->sum('amount');

            return [
                'label' => ucfirst($month->translatedFormat('M')),
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
                'profit' => round($income - $expenses, 2),
            ];
        });

        return [
            'labels' => $series->pluck('label')->all(),
            'income_series' => $series->pluck('income')->all(),
            'expense_series' => $series->pluck('expenses')->all(),
            'profit_series' => $series->pluck('profit')->all(),
            'income_total' => (float) $series->sum('income'),
            'expense_total' => (float) $series->sum('expenses'),
            'profit_total' => (float) $series->sum('profit'),
        ];
    }

    private function monthOptions(Carbon $selectedMonth, int $count): Collection
    {
        return collect(range(0, $count - 1))
            ->map(fn ($offset) => $selectedMonth->copy()->subMonths($offset))
            ->map(fn (Carbon $month) => [
                'value' => $month->format('Y-m'),
                'label' => ucfirst($month->translatedFormat('F Y')),
            ]);
    }

    private function occupiedStatuses(): array
    {
        return [Property::STATUS_OCCUPIED, Property::STATUS_RENTED];
    }

    private function isChargeOverdueForMonth(Charge $charge, Carbon $selectedMonth): bool
    {
        if (!in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL], true)) {
            return false;
        }

        return $charge->due_date?->lt($this->referenceDateForMonth($selectedMonth)) ?? false;
    }

    private function referenceDateForMonth(Carbon $selectedMonth): Carbon
    {
        $currentMonthStart = now()->startOfMonth();

        if ($selectedMonth->lt($currentMonthStart)) {
            return $selectedMonth->copy()->endOfMonth()->addDay()->startOfDay();
        }

        if ($selectedMonth->equalTo($currentMonthStart)) {
            return now()->startOfDay();
        }

        return $selectedMonth->copy()->startOfMonth();
    }

    private function money(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
