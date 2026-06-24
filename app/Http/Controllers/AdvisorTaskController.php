<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\TenantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdvisorTaskController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,urgent,today,charges,maintenance,documents,contracts'],
            'range' => ['nullable', 'in:today,current_week,current_month'],
        ]);

        $filter = $validated['filter'] ?? 'all';
        $activeRange = $validated['range'] ?? 'today';
        $period = $this->periodForRange($activeRange);
        $propertyIds = $this->advisorPropertyIds($request);
        $today = now()->startOfDay();

        $tasks = $this->buildChargeTasks($propertyIds, $today, $period['start'], $period['end'], $period['include_overdue'])
            ->concat($this->buildMaintenanceTasks($propertyIds, $today, $period['start'], $period['end'], $period['include_overdue']))
            ->concat($this->buildContractTasks($propertyIds, $today, $period['start'], $period['end'], $period['include_overdue']))
            ->concat($this->buildPropertyDocumentTasks($propertyIds, $today, $period['start'], $period['end'], $period['include_overdue']))
            ->concat($this->buildTenantDocumentTasks($propertyIds, $today, $period['start'], $period['end'], $period['include_overdue']))
            ->sortBy(fn (array $task): string => sprintf(
                '%02d-%s-%s',
                $task['sort_rank'],
                $task['sort_at'] instanceof Carbon ? $task['sort_at']->format('Y-m-d H:i:s') : '9999-12-31 23:59:59',
                $task['id'],
            ))
            ->values();

        $filteredTasks = $this->filterTasks($tasks, $filter)->values();

        return view('advisor-tasks.index', [
            'activeFilter' => $filter,
            'activeRange' => $activeRange,
            'periodStart' => $period['start'],
            'periodEnd' => $period['end'],
            'periodLabel' => $period['label'],
            'periodIncludesOverdue' => $period['include_overdue'],
            'assignedPropertyCount' => $propertyIds->count(),
            'tasks' => $filteredTasks,
            'allTasksCount' => $tasks->count(),
            'urgentTasksCount' => $tasks->where('priority', 'urgent')->count(),
            'todayTasksCount' => $tasks->where('is_today', true)->count(),
            'upcomingTasksCount' => $tasks->where('priority', 'upcoming')->count(),
            'filterCounts' => [
                'all' => $tasks->count(),
                'urgent' => $tasks->where('priority', 'urgent')->count(),
                'today' => $tasks->where('is_today', true)->count(),
                'charges' => $tasks->where('category', 'charges')->count(),
                'maintenance' => $tasks->where('category', 'maintenance')->count(),
                'documents' => $tasks->where('category', 'documents')->count(),
                'contracts' => $tasks->where('category', 'contracts')->count(),
            ],
        ]);
    }

    private function buildChargeTasks(Collection $propertyIds, Carbon $today, Carbon $periodStart, Carbon $periodEnd, bool $includeOverdue): Collection
    {
        if ($propertyIds->isEmpty()) {
            return collect();
        }

        $query = Charge::query()
            ->with(['property:id,uuid,internal_name,internal_reference', 'tenant:id,full_name'])
            ->whereIn('property_id', $propertyIds->all())
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->whereDate('due_date', '<=', $periodEnd->toDateString());

        if (! $includeOverdue) {
            $query->whereDate('due_date', '>=', $periodStart->toDateString());
        }

        return $query->orderBy('due_date')
            ->limit(80)
            ->get()
            ->map(function (Charge $charge) use ($today): array {
                $dueDate = $charge->due_date?->copy()->startOfDay();
                $isOverdue = $dueDate?->lt($today) ?? false;
                $isToday = $dueDate?->isSameDay($today) ?? false;
                $isRent = $charge->type === Charge::TYPE_RENT;
                $priority = $isOverdue ? 'urgent' : ($isToday ? 'today' : 'upcoming');

                return [
                    'id' => 'charge-'.$charge->id,
                    'category' => 'charges',
                    'category_label' => $isRent ? 'Renta' : 'Cobranza',
                    'priority' => $priority,
                    'is_today' => $isToday,
                    'tone' => $isOverdue ? 'danger' : ($charge->status === Charge::STATUS_IN_VALIDATION ? 'primary' : 'warning'),
                    'icon' => $isOverdue ? 'bi-exclamation-octagon' : 'bi-wallet2',
                    'title' => $charge->property?->internal_name ?: 'Propiedad',
                    'subtitle' => ($isOverdue ? 'Cobro vencido' : 'Cobro por atender').' por '.$this->money($charge->outstanding_amount),
                    'detail' => ($charge->tenant?->full_name ?: 'Sin inquilino').' · '.$charge->type_label,
                    'due_label' => $this->dateStatusLabel($dueDate),
                    'due_detail' => $dueDate?->translatedFormat('d M Y') ?: 'Sin fecha',
                    'route' => route('charges.show', $charge),
                    'sort_at' => $dueDate,
                    'sort_rank' => $isOverdue ? 10 : ($isToday ? 20 : 40),
                ];
            });
    }

    private function buildMaintenanceTasks(Collection $propertyIds, Carbon $today, Carbon $periodStart, Carbon $periodEnd, bool $includeOverdue): Collection
    {
        if ($propertyIds->isEmpty()) {
            return collect();
        }

        $activeStatuses = array_diff(array_keys(MaintenanceTicket::STATUS_LABELS), ['completado', 'cancelado']);

        $query = MaintenanceTicket::query()
            ->with(['property:id,uuid,internal_name,internal_reference', 'currentProvider:id,name'])
            ->whereIn('property_id', $propertyIds->all())
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('scheduled_visit_at')
            ->where('scheduled_visit_at', '<=', $periodEnd);

        if (! $includeOverdue) {
            $query->where('scheduled_visit_at', '>=', $periodStart);
        }

        return $query->orderByRaw('scheduled_visit_at is null')
            ->orderBy('scheduled_visit_at')
            ->limit(60)
            ->get()
            ->map(function (MaintenanceTicket $ticket) use ($today): array {
                $scheduledAt = $ticket->scheduled_visit_at?->copy();
                $scheduledDate = $scheduledAt?->copy()->startOfDay();
                $isOverdue = $scheduledAt?->lt(now()) ?? false;
                $isToday = $scheduledDate?->isSameDay($today) ?? false;
                $isUrgent = $ticket->priority === 'urgente' || $isOverdue;

                return [
                    'id' => 'maintenance-'.$ticket->id,
                    'category' => 'maintenance',
                    'category_label' => 'Mantenimiento',
                    'priority' => $isUrgent ? 'urgent' : ($isToday ? 'today' : 'upcoming'),
                    'is_today' => $isToday,
                    'tone' => $isUrgent ? 'danger' : 'info',
                    'icon' => $isUrgent ? 'bi-tools' : 'bi-calendar2-check',
                    'title' => $ticket->title,
                    'subtitle' => $ticket->property?->internal_name ?: 'Propiedad',
                    'detail' => ($ticket->currentProvider?->name ?: 'Sin técnico').' · '.(MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status),
                    'due_label' => $scheduledAt ? $this->dateStatusLabel($scheduledAt) : 'Urgente',
                    'due_detail' => $scheduledAt?->translatedFormat('d M Y H:i') ?: 'Sin visita programada',
                    'route' => route('maintenance.show', $ticket),
                    'sort_at' => $scheduledAt,
                    'sort_rank' => $isOverdue ? 10 : ($isUrgent ? 15 : ($isToday ? 20 : 45)),
                ];
            });
    }

    private function buildContractTasks(Collection $propertyIds, Carbon $today, Carbon $periodStart, Carbon $periodEnd, bool $includeOverdue): Collection
    {
        if ($propertyIds->isEmpty()) {
            return collect();
        }

        $query = Property::query()
            ->with('tenant:id,full_name')
            ->whereIn('id', $propertyIds->all())
            ->whereNotNull('contract_expires_at')
            ->whereDate('contract_expires_at', '<=', $periodEnd->toDateString());

        if (! $includeOverdue) {
            $query->whereDate('contract_expires_at', '>=', $periodStart->toDateString());
        }

        return $query->orderBy('contract_expires_at')
            ->limit(60)
            ->get()
            ->map(function (Property $property) use ($today): array {
                $expiresAt = $property->contract_expires_at?->copy()->startOfDay();
                $isOverdue = $expiresAt?->lt($today) ?? false;
                $isToday = $expiresAt?->isSameDay($today) ?? false;

                return [
                    'id' => 'contract-'.$property->id,
                    'category' => 'contracts',
                    'category_label' => 'Contrato',
                    'priority' => $isOverdue ? 'urgent' : ($isToday ? 'today' : 'upcoming'),
                    'is_today' => $isToday,
                    'tone' => $isOverdue ? 'danger' : 'warning',
                    'icon' => 'bi-file-earmark-text',
                    'title' => $property->internal_name,
                    'subtitle' => $isOverdue ? 'Contrato vencido' : 'Contrato por vencer',
                    'detail' => $property->tenant?->full_name ?: 'Sin inquilino asignado',
                    'due_label' => $this->dateStatusLabel($expiresAt),
                    'due_detail' => $expiresAt?->translatedFormat('d M Y') ?: 'Sin fecha',
                    'route' => route('properties.show', $property),
                    'sort_at' => $expiresAt,
                    'sort_rank' => $isOverdue ? 12 : ($isToday ? 22 : 55),
                ];
            });
    }

    private function buildPropertyDocumentTasks(Collection $propertyIds, Carbon $today, Carbon $periodStart, Carbon $periodEnd, bool $includeOverdue): Collection
    {
        if ($propertyIds->isEmpty()) {
            return collect();
        }

        $query = PropertyDocument::query()
            ->with('property:id,uuid,internal_name,internal_reference')
            ->whereIn('property_id', $propertyIds->all())
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $periodEnd->toDateString());

        if (! $includeOverdue) {
            $query->whereDate('expires_at', '>=', $periodStart->toDateString());
        }

        return $query->orderBy('expires_at')
            ->limit(60)
            ->get()
            ->map(function (PropertyDocument $document) use ($today): array {
                $expiresAt = $document->expires_at?->copy()->startOfDay();
                $isOverdue = $expiresAt?->lt($today) || $document->status === PropertyDocument::STATUS_EXPIRED;
                $isToday = $expiresAt?->isSameDay($today) ?? false;

                return [
                    'id' => 'property-document-'.$document->id,
                    'category' => 'documents',
                    'category_label' => 'Documento',
                    'priority' => $isOverdue ? 'urgent' : ($isToday ? 'today' : 'upcoming'),
                    'is_today' => $isToday,
                    'tone' => $isOverdue ? 'danger' : 'warning',
                    'icon' => 'bi-folder2-open',
                    'title' => $document->label ?: 'Documento de propiedad',
                    'subtitle' => $document->property?->internal_name ?: 'Propiedad',
                    'detail' => 'Expediente de propiedad',
                    'due_label' => $this->dateStatusLabel($expiresAt),
                    'due_detail' => $expiresAt?->translatedFormat('d M Y') ?: 'Sin fecha',
                    'route' => $document->property ? route('dossiers.properties.show', $document->property) : route('documents.index'),
                    'sort_at' => $expiresAt,
                    'sort_rank' => $isOverdue ? 14 : ($isToday ? 24 : 60),
                ];
            });
    }

    private function buildTenantDocumentTasks(Collection $propertyIds, Carbon $today, Carbon $periodStart, Carbon $periodEnd, bool $includeOverdue): Collection
    {
        if ($propertyIds->isEmpty()) {
            return collect();
        }

        $query = TenantDocument::query()
            ->with('tenant.properties:id,uuid,internal_name,tenant_id')
            ->whereHas('tenant.properties', fn ($query) => $query->whereIn('properties.id', $propertyIds->all()))
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $periodEnd->toDateString());

        if (! $includeOverdue) {
            $query->whereDate('expires_at', '>=', $periodStart->toDateString());
        }

        return $query->orderBy('expires_at')
            ->limit(60)
            ->get()
            ->map(function (TenantDocument $document) use ($propertyIds, $today): array {
                $property = $document->tenant?->properties
                    ->first(fn (Property $property): bool => $propertyIds->contains($property->id));
                $expiresAt = $document->expires_at?->copy()->startOfDay();
                $isOverdue = $expiresAt?->lt($today) || $document->status === TenantDocument::STATUS_EXPIRED;
                $isToday = $expiresAt?->isSameDay($today) ?? false;

                return [
                    'id' => 'tenant-document-'.$document->id,
                    'category' => 'documents',
                    'category_label' => 'Documento',
                    'priority' => $isOverdue ? 'urgent' : ($isToday ? 'today' : 'upcoming'),
                    'is_today' => $isToday,
                    'tone' => $isOverdue ? 'danger' : 'warning',
                    'icon' => 'bi-person-vcard',
                    'title' => $document->label ?: 'Documento de inquilino',
                    'subtitle' => $document->tenant?->full_name ?: 'Inquilino',
                    'detail' => $property?->internal_name ?: 'Propiedad asignada',
                    'due_label' => $this->dateStatusLabel($expiresAt),
                    'due_detail' => $expiresAt?->translatedFormat('d M Y') ?: 'Sin fecha',
                    'route' => $document->tenant ? route('dossiers.tenants.show', $document->tenant) : route('documents.index'),
                    'sort_at' => $expiresAt,
                    'sort_rank' => $isOverdue ? 14 : ($isToday ? 24 : 62),
                ];
            });
    }

    private function filterTasks(Collection $tasks, string $filter): Collection
    {
        return match ($filter) {
            'urgent' => $tasks->where('priority', 'urgent'),
            'today' => $tasks->where('is_today', true),
            'charges' => $tasks->where('category', 'charges'),
            'maintenance' => $tasks->where('category', 'maintenance'),
            'documents' => $tasks->where('category', 'documents'),
            'contracts' => $tasks->where('category', 'contracts'),
            default => $tasks,
        };
    }

    private function advisorPropertyIds(Request $request): Collection
    {
        $user = $request->user();

        if (! $user) {
            return collect();
        }

        return $user->advisorProperties()
            ->select('properties.id')
            ->pluck('properties.id')
            ->merge(Property::query()->where('advisor_user_id', $user->id)->pluck('id'))
            ->unique()
            ->values();
    }

    private function periodForRange(string $range): array
    {
        $today = now();

        [$start, $end, $label, $includeOverdue] = match ($range) {
            'today' => [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay(),
                'Hoy',
                true,
            ],
            'current_week' => [
                $today->copy()->startOfWeek()->startOfDay(),
                $today->copy()->endOfWeek()->endOfDay(),
                'Esta semana',
                true,
            ],
            default => [
                $today->copy()->startOfMonth()->startOfDay(),
                $today->copy()->endOfMonth()->endOfDay(),
                'Este mes',
                true,
            ],
        };

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
            'include_overdue' => $includeOverdue,
        ];
    }

    private function dateStatusLabel(?Carbon $date): string
    {
        if (! $date) {
            return 'Sin fecha';
        }

        $day = $date->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($day->isSameDay($today)) {
            return 'Hoy';
        }

        if ($day->isSameDay($today->copy()->addDay())) {
            return 'Mañana';
        }

        if ($day->lt($today)) {
            return 'Vencido hace '.$day->diffForHumans($today, true);
        }

        return 'En '.$today->diffForHumans($day, true);
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }
}
