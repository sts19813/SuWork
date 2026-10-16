<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MaintenanceCut;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Services\MaintenanceCutReportData;
use App\Services\MaintenanceCutReportExcelExporter;
use App\Services\MaintenanceCutReportPdfExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaintenanceCutController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdministrator($request);
        $historyFilters = $this->historyFilters($request);

        $completedTickets = MaintenanceTicket::query()
            ->where('status', 'completado')
            ->whereNull('master_ticket_id')
            ->whereDoesntHave('cutItem')
            ->with([
                'property:id,uuid,internal_name,internal_reference',
                'currentProvider:id,name',
                'childTickets:id,uuid,master_ticket_id,property_id,reference,title,reported_at,completed_at,created_at',
                'childTickets.property:id,uuid,internal_name,internal_reference',
            ])
            ->withSum('costs as labor_total', 'labor_cost')
            ->withSum('costs as material_total', 'material_cost')
            ->withSum('costs as grand_total', 'final_cost')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        $partitionedTickets = $completedTickets->partition(
            fn (MaintenanceTicket $ticket): bool => round((float) ($ticket->grand_total ?? 0), 2) <= 0
        );
        $zeroCostTickets = $partitionedTickets[0]->values();
        $tickets = $partitionedTickets[1]->values();

        $propertyOptions = $tickets
            ->pluck('property')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($property) => mb_strtolower((string) $property->internal_name))
            ->values();

        $technicianOptions = $tickets
            ->map(fn (MaintenanceTicket $ticket): array => [
                'key' => $ticket->current_provider_id ? 'technician-'.$ticket->current_provider_id : 'unassigned',
                'name' => $ticket->currentProvider?->name ?? 'Sin técnico asignado',
            ])
            ->unique('key')
            ->sortBy(fn (array $technician) => mb_strtolower($technician['name']))
            ->values();

        $zeroCostPropertyOptions = $zeroCostTickets
            ->pluck('property')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($property) => mb_strtolower((string) $property->internal_name))
            ->values();

        $zeroCostTechnicianOptions = $zeroCostTickets
            ->map(fn (MaintenanceTicket $ticket): array => [
                'key' => $ticket->current_provider_id ? 'technician-'.$ticket->current_provider_id : 'unassigned',
                'name' => $ticket->currentProvider?->name ?? 'Sin técnico asignado',
            ])
            ->unique('key')
            ->sortBy(fn (array $technician) => mb_strtolower($technician['name']))
            ->values();

        $cuts = MaintenanceCut::query()
            ->with([
                'paidBy:id,name,email',
                'provider:id,name,type,is_active',
                'items' => fn ($query) => $query->orderBy('id'),
                'items.ticket:id,uuid,property_id,current_provider_id,reference,title,reported_at,completed_at,created_at',
                'items.ticket.currentProvider:id,name',
                'items.ticket.property:id,uuid,internal_name,internal_reference',
                'items.ticket.childTickets:id,uuid,master_ticket_id,property_id,reference,title,reported_at,completed_at,created_at',
                'items.ticket.childTickets.property:id,uuid,internal_name,internal_reference',
            ])
            ->when($historyFilters['provider_id'], fn (Builder $query, int $providerId) => $query->where('provider_id', $providerId))
            ->when($historyFilters['property_id'], fn (Builder $query, int $propertyId) => $query->whereHas('items.ticket', fn (Builder $ticketQuery) => $ticketQuery->where('property_id', $propertyId)))
            ->when($historyFilters['date_from'], fn (Builder $query, string $dateFrom) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($historyFilters['date_to'], fn (Builder $query, string $dateTo) => $query->whereDate('paid_at', '<=', $dateTo))
            ->when($historyFilters['search'], function (Builder $query, string $search): void {
                $like = '%'.$search.'%';
                $referenceId = (int) preg_replace('/\D+/', '', $search);

                $query->where(function (Builder $searchQuery) use ($like, $referenceId): void {
                    if ($referenceId > 0) {
                        $searchQuery->orWhereKey($referenceId);
                    }

                    $searchQuery
                        ->orWhereHas('provider', fn (Builder $providerQuery) => $providerQuery->where('name', 'like', $like))
                        ->orWhereHas('paidBy', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like))
                        ->orWhereHas('items.ticket', function (Builder $ticketQuery) use ($like): void {
                            $ticketQuery
                                ->where('reference', 'like', $like)
                                ->orWhere('title', 'like', $like)
                                ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery
                                    ->where('internal_name', 'like', $like)
                                    ->orWhere('internal_reference', 'like', $like));
                        });
                });
            })
            ->latest('paid_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $historyProviderOptions = MaintenanceProvider::query()
            ->whereIn('id', MaintenanceCut::query()->whereNotNull('provider_id')->select('provider_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_active']);
        $historyPropertyOptions = Property::query()
            ->whereIn('id', MaintenanceTicket::query()->whereHas('cutItem')->select('property_id'))
            ->orderBy('internal_name')
            ->get(['id', 'internal_name', 'internal_reference']);

        return view('maintenance.cuts.index', [
            'tickets' => $tickets,
            'zeroCostTickets' => $zeroCostTickets,
            'cuts' => $cuts,
            'propertyOptions' => $propertyOptions,
            'technicianOptions' => $technicianOptions,
            'zeroCostPropertyOptions' => $zeroCostPropertyOptions,
            'zeroCostTechnicianOptions' => $zeroCostTechnicianOptions,
            'historyProviderOptions' => $historyProviderOptions,
            'historyPropertyOptions' => $historyPropertyOptions,
            'historyFilters' => $historyFilters,
            'pendingTotals' => $this->totalsFor($tickets),
            'paidGrandTotal' => (float) MaintenanceCut::query()->sum('grand_total'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator($request);

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['required', 'integer', 'distinct', 'exists:maintenance_tickets,id'],
        ], [
            'ticket_ids.required' => 'Selecciona al menos un ticket para generar el pago.',
            'ticket_ids.min' => 'Selecciona al menos un ticket para generar el pago.',
        ]);

        $ticketIds = collect($validated['ticket_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $cut = DB::transaction(function () use ($ticketIds, $request): MaintenanceCut {
            $tickets = MaintenanceTicket::query()
                ->whereIn('id', $ticketIds)
                ->where('status', 'completado')
                ->whereNull('master_ticket_id')
                ->whereDoesntHave('cutItem')
                ->with('costs:id,ticket_id,expense_id,labor_cost,material_cost,final_cost')
                ->lockForUpdate()
                ->get();

            if ($tickets->count() !== $ticketIds->count()) {
                throw ValidationException::withMessages([
                    'ticket_ids' => 'Uno o más tickets ya fueron pagados o dejaron de estar completados. Actualiza la página e inténtalo nuevamente.',
                ]);
            }

            $technicianKeys = $tickets
                ->map(fn (MaintenanceTicket $ticket): string => $ticket->current_provider_id
                    ? (string) $ticket->current_provider_id
                    : 'unassigned')
                ->unique();

            if ($technicianKeys->count() > 1) {
                throw ValidationException::withMessages([
                    'ticket_ids' => 'Todos los tickets del corte deben pertenecer al mismo técnico.',
                ]);
            }

            $rows = $tickets->map(function (MaintenanceTicket $ticket): array {
                $labor = round((float) $ticket->costs->sum('labor_cost'), 2);
                $materials = round((float) $ticket->costs->sum('material_cost'), 2);
                $total = round((float) $ticket->costs->sum('final_cost'), 2);

                return [
                    'ticket_id' => $ticket->id,
                    'labor_total' => $labor,
                    'material_total' => $materials,
                    'grand_total' => $total,
                ];
            });
            $totals = $this->totalsFor($rows);

            $hasZeroCostTickets = $rows->contains(fn (array $row): bool => (float) $row['grand_total'] <= 0);
            $hasPayableTickets = $rows->contains(fn (array $row): bool => (float) $row['grand_total'] > 0);

            if ($hasZeroCostTickets && $hasPayableTickets) {
                throw ValidationException::withMessages([
                    'ticket_ids' => 'Los tickets sin costo deben cerrarse en su propio corte, separados de los tickets con monto.',
                ]);
            }

            $cut = MaintenanceCut::create([
                'paid_by_user_id' => $request->user()?->id,
                'provider_id' => $tickets->first()?->current_provider_id,
                'ticket_count' => $rows->count(),
                'labor_total' => $totals['labor'],
                'material_total' => $totals['materials'],
                'grand_total' => $totals['grand'],
                'paid_at' => now(),
            ]);
            $cut->items()->createMany($rows->all());

            $expenseIds = $tickets
                ->flatMap(fn (MaintenanceTicket $ticket) => $ticket->costs->pluck('expense_id'))
                ->filter()
                ->unique()
                ->values();

            if ($expenseIds->isNotEmpty()) {
                Expense::query()
                    ->whereIn('id', $expenseIds->all())
                    ->whereNull('paid_at')
                    ->update(['paid_at' => $cut->paid_at]);
            }

            return $cut;
        });

        return redirect()
            ->route('maintenance-cuts.index')
            ->with('success', "{$cut->display_reference} pagado correctamente con {$cut->ticket_count} ticket(s).");
    }

    public function exportPdf(Request $request)
    {
        [$cuts, $filterSummary] = $this->cutsForReport($request);
        $generatedAt = now();
        $reportData = app(MaintenanceCutReportData::class);
        $pdf = app(MaintenanceCutReportPdfExporter::class)->generate(
            $reportData->rows($cuts),
            $filterSummary,
            $generatedAt,
            $reportData->indicators($cuts),
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_cortes_mantenimiento_'.$generatedAt->format('Ymd_His').'.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$cuts, $filterSummary] = $this->cutsForReport($request);
        $generatedAt = now();
        $reportData = app(MaintenanceCutReportData::class);
        $spreadsheet = app(MaintenanceCutReportExcelExporter::class)->generate(
            $reportData->rows($cuts),
            $filterSummary,
            $generatedAt,
            $reportData->indicators($cuts),
        );
        $filename = 'reporte_cortes_mantenimiento_'.$generatedAt->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function ensureAdministrator(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('administrador') && ! $user->hasRole('admin'))) {
            abort(403);
        }
    }

    /** @return array{provider_id: ?int, property_id: ?int, search: ?string, date_from: ?string, date_to: ?string} */
    private function historyFilters(Request $request): array
    {
        $filters = $request->validate([
            'history_provider_id' => ['nullable', 'integer', Rule::exists('maintenance_providers', 'id')],
            'history_property_id' => ['nullable', 'integer', Rule::exists('properties', 'id')],
            'history_search' => ['nullable', 'string', 'max:100'],
            'history_date_from' => ['nullable', 'date'],
            'history_date_to' => ['nullable', 'date', 'after_or_equal:history_date_from'],
        ]);

        return [
            'provider_id' => filled($filters['history_provider_id'] ?? null) ? (int) $filters['history_provider_id'] : null,
            'property_id' => filled($filters['history_property_id'] ?? null) ? (int) $filters['history_property_id'] : null,
            'search' => filled($filters['history_search'] ?? null) ? trim((string) $filters['history_search']) : null,
            'date_from' => $filters['history_date_from'] ?? null,
            'date_to' => $filters['history_date_to'] ?? null,
        ];
    }

    /**
     * @return array{0: Collection<int, MaintenanceCut>, 1: string}
     */
    private function cutsForReport(Request $request): array
    {
        $this->ensureAdministrator($request);

        $filters = $request->validateWithBag('maintenanceCutExport', [
            'provider_ids' => ['nullable', 'array'],
            'provider_ids.*' => ['integer', Rule::exists('maintenance_providers', 'id')],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', Rule::exists('properties', 'id')],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_paid' => ['nullable', Rule::in(['paid_desc', 'paid_asc'])],
        ]);
        $providerIds = collect($filters['provider_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $propertyIds = collect($filters['property_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $search = filled($filters['search'] ?? null) ? trim((string) $filters['search']) : null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $sortPaid = (string) ($filters['sort_paid'] ?? 'paid_desc');

        $cuts = MaintenanceCut::query()
            ->with([
                'provider:id,name',
                'paidBy:id,name',
                'items.ticket:id,current_provider_id',
                'items.ticket.currentProvider:id,name',
            ])
            ->when($providerIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('provider_id', $providerIds->all()))
            ->when($propertyIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('items.ticket', fn (Builder $ticketQuery) => $ticketQuery->whereIn('property_id', $propertyIds->all())))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('paid_at', '<=', $dateTo))
            ->when($search, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';
                $referenceId = (int) preg_replace('/\D+/', '', $search);

                $query->where(function (Builder $searchQuery) use ($like, $referenceId): void {
                    if ($referenceId > 0) {
                        $searchQuery->orWhereKey($referenceId);
                    }

                    $searchQuery
                        ->orWhereHas('provider', fn (Builder $providerQuery) => $providerQuery->where('name', 'like', $like))
                        ->orWhereHas('items.ticket', fn (Builder $ticketQuery) => $ticketQuery
                            ->where('reference', 'like', $like)
                            ->orWhere('title', 'like', $like));
                });
            })
            ->orderBy('paid_at', $sortPaid === 'paid_asc' ? 'asc' : 'desc')
            ->orderBy('id', $sortPaid === 'paid_asc' ? 'asc' : 'desc')
            ->get();

        $providerNames = $providerIds->isEmpty()
            ? collect()
            : MaintenanceProvider::query()->whereIn('id', $providerIds->all())->orderBy('name')->pluck('name');
        $propertyNames = $propertyIds->isEmpty()
            ? collect()
            : Property::query()->whereIn('id', $propertyIds->all())->orderBy('internal_name')->pluck('internal_name');

        $summary = collect([
            'Proveedor / técnico: '.($providerNames->isNotEmpty() ? $providerNames->implode(', ') : 'Todos'),
            'Propiedad: '.($propertyNames->isNotEmpty() ? $propertyNames->implode(', ') : 'Todas'),
            'Rango por fecha de pago: '.($dateFrom ? Carbon::parse($dateFrom)->format('d/m/Y') : 'sin inicio').' a '.($dateTo ? Carbon::parse($dateTo)->format('d/m/Y') : 'sin fin'),
            'Orden: '.($sortPaid === 'paid_asc' ? 'pagos más antiguos' : 'pagos más recientes'),
        ])->implode(' · ');

        return [$cuts, $summary];
    }

    private function totalsFor(Collection $rows): array
    {
        return [
            'labor' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'labor_total') ?? 0)), 2),
            'materials' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'material_total') ?? 0)), 2),
            'grand' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'grand_total') ?? 0)), 2),
        ];
    }
}
