<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\Tenant;
use App\Models\TenantDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    private const STATUS_FILTERS = [
        'approved' => 'Vigentes',
        'pending_review' => 'Pend. revision',
        'expired' => 'Vencidos',
        'rejected' => 'Rechazados',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'entity' => ['nullable', Rule::in(['property', 'tenant'])],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_FILTERS))],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $entityFilter = (string) ($filters['entity'] ?? '');
        $statusFilter = (string) ($filters['status'] ?? '');

        $documents = $this->buildDocumentsCollection();

        $documents = $documents
            ->when($search !== '', function (Collection $collection) use ($search): Collection {
                return $collection->filter(function (array $document) use ($search): bool {
                    $needle = mb_strtolower($search);
                    $haystack = mb_strtolower(
                        ($document['label'] ?? '') . ' ' .
                        ($document['entity_name'] ?? '') . ' ' .
                        ($document['file_name'] ?? ''),
                    );

                    return str_contains($haystack, $needle);
                })->values();
            })
            ->when($entityFilter !== '', function (Collection $collection) use ($entityFilter): Collection {
                return $collection->where('entity_type', $entityFilter)->values();
            });

        $stats = [
            'approved' => $documents->filter(fn (array $document) => $this->matchesStatusFilter($document['status'], 'approved'))->count(),
            'pending_review' => $documents->filter(fn (array $document) => $this->matchesStatusFilter($document['status'], 'pending_review'))->count(),
            'expired' => $documents->filter(fn (array $document) => $this->matchesStatusFilter($document['status'], 'expired'))->count(),
            'rejected' => $documents->filter(fn (array $document) => $this->matchesStatusFilter($document['status'], 'rejected'))->count(),
        ];

        if ($statusFilter !== '') {
            $documents = $documents
                ->filter(fn (array $document) => $this->matchesStatusFilter($document['status'], $statusFilter))
                ->values();
        }

        $documents = $documents->sortByDesc('updated_at')->values();

        return view('documents.index', [
            'documents' => $this->paginateCollection($documents, $request),
            'filters' => [
                'q' => $search,
                'entity' => $entityFilter,
                'status' => $statusFilter,
            ],
            'statusFilters' => self::STATUS_FILTERS,
            'stats' => $stats,
        ]);
    }

    public function propertyDossier(Property $property): View
    {
        $this->ensurePropertyDocuments($property);

        $property->load([
            'type',
            'zone',
            'documents.versions' => fn ($query) => $query->orderByDesc('version_number'),
        ]);

        $documents = collect(PropertyDocument::REQUIRED_DOCUMENTS)
            ->map(function (string $label, string $documentType) use ($property) {
                return $property->documents->firstWhere('document_type', $documentType)
                    ?? new PropertyDocument([
                        'document_type' => $documentType,
                        'label' => $label,
                        'status' => PropertyDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $property->documents
            ->whereNotIn('document_type', array_keys(PropertyDocument::REQUIRED_DOCUMENTS))
            ->values();

        return view('documents.property-dossier', [
            'property' => $property,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
        ]);
    }

    public function tenantDossier(Tenant $tenant): View
    {
        $this->ensureTenantDocuments($tenant);

        $tenant->load([
            'documents.versions' => fn ($query) => $query->orderByDesc('version_number'),
        ]);

        $documents = collect(TenantDocument::REQUIRED_DOCUMENTS)
            ->map(function (string $label, string $documentType) use ($tenant) {
                return $tenant->documents->firstWhere('document_type', $documentType)
                    ?? new TenantDocument([
                        'document_type' => $documentType,
                        'label' => $label,
                        'status' => TenantDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $tenant->documents
            ->whereNotIn('document_type', array_keys(TenantDocument::REQUIRED_DOCUMENTS))
            ->values();

        return view('documents.tenant-dossier', [
            'tenant' => $tenant,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
        ]);
    }

    public function uploadPropertyDocument(Request $request, Property $property, string $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $document = $property->documents()->where('document_type', $documentType)->first();
        $isRequiredDocument = array_key_exists($documentType, PropertyDocument::REQUIRED_DOCUMENTS);

        if (!$document && !$isRequiredDocument) {
            abort(404);
        }

        if (!$document && $isRequiredDocument) {
            $document = $property->documents()->create([
                'document_type' => $documentType,
                'label' => PropertyDocument::REQUIRED_DOCUMENTS[$documentType],
                'status' => PropertyDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }

        if ($isRequiredDocument) {
            $document->update([
                'label' => PropertyDocument::REQUIRED_DOCUMENTS[$documentType],
            ]);
        }

        $file = $validated['file'];
        $storedPath = $file->store("properties/{$property->id}/documents", 'public');
        $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        $document->update([
            'file_path' => $storedPath,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? $document->expires_at,
        ]);

        return back()->with('success', 'Documento de propiedad actualizado. Se genero una nueva version.');
    }

    public function uploadTenantDocument(Request $request, Tenant $tenant, string $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $document = $tenant->documents()->where('document_type', $documentType)->first();
        $isRequiredDocument = array_key_exists($documentType, TenantDocument::REQUIRED_DOCUMENTS);

        if (!$document && !$isRequiredDocument) {
            abort(404);
        }

        if (!$document && $isRequiredDocument) {
            $document = $tenant->documents()->create([
                'document_type' => $documentType,
                'label' => TenantDocument::REQUIRED_DOCUMENTS[$documentType],
                'status' => TenantDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }

        if ($isRequiredDocument) {
            $document->update([
                'label' => TenantDocument::REQUIRED_DOCUMENTS[$documentType],
            ]);
        }

        $file = $validated['file'];
        $storedPath = $file->store("tenants/{$tenant->id}/documents", 'public');
        $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        $document->update([
            'file_path' => $storedPath,
            'status' => TenantDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? $document->expires_at,
        ]);

        return back()->with('success', 'Documento de inquilino actualizado. Se genero una nueva version.');
    }

    public function storeCustomPropertyDocument(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $documentType = $this->buildCustomDocumentType(
            existingTypes: $property->documents()->pluck('document_type')->all(),
            label: $validated['label'],
        );

        $file = $validated['file'];
        $storedPath = $file->store("properties/{$property->id}/documents", 'public');

        $document = $property->documents()->create([
            'document_type' => $documentType,
            'label' => $validated['label'],
            'file_path' => $storedPath,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Documento personalizado agregado al expediente de la propiedad.');
    }

    public function storeCustomTenantDocument(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $documentType = $this->buildCustomDocumentType(
            existingTypes: $tenant->documents()->pluck('document_type')->all(),
            label: $validated['label'],
        );

        $file = $validated['file'];
        $storedPath = $file->store("tenants/{$tenant->id}/documents", 'public');

        $document = $tenant->documents()->create([
            'document_type' => $documentType,
            'label' => $validated['label'],
            'file_path' => $storedPath,
            'status' => TenantDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $document->versions()->create([
            'version_number' => 1,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Documento personalizado agregado al expediente del inquilino.');
    }

    private function buildDocumentsCollection(): Collection
    {
        $propertyDocuments = PropertyDocument::query()
            ->with(['property:id,uuid,internal_name', 'latestVersion'])
            ->withCount('versions')
            ->get()
            ->map(fn (PropertyDocument $document) => $this->mapPropertyDocument($document));

        $tenantDocuments = TenantDocument::query()
            ->with(['tenant:id,uuid,full_name', 'latestVersion'])
            ->withCount('versions')
            ->get()
            ->map(fn (TenantDocument $document) => $this->mapTenantDocument($document));

        return $propertyDocuments->concat($tenantDocuments)->values();
    }

    private function mapPropertyDocument(PropertyDocument $document): array
    {
        return [
            'id' => 'property-' . $document->id,
            'label' => $document->label,
            'document_type' => $document->document_type,
            'entity_type' => 'property',
            'entity_type_label' => 'Propiedad',
            'entity_name' => $document->property?->internal_name ?? 'Propiedad eliminada',
            'entity_url' => $document->property ? route('dossiers.properties.show', $document->property) : null,
            'status' => $document->status,
            'status_label' => $document->status_label,
            'status_badge_class' => $document->status_badge_class,
            'expires_at' => $document->expires_at,
            'file_name' => $document->latestVersion?->original_name,
            'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            'versions_count' => $document->versions_count,
            'updated_at' => $document->updated_at,
        ];
    }

    private function mapTenantDocument(TenantDocument $document): array
    {
        return [
            'id' => 'tenant-' . $document->id,
            'label' => $document->label,
            'document_type' => $document->document_type,
            'entity_type' => 'tenant',
            'entity_type_label' => 'Inquilino',
            'entity_name' => $document->tenant?->full_name ?? 'Inquilino eliminado',
            'entity_url' => $document->tenant ? route('dossiers.tenants.show', $document->tenant) : null,
            'status' => $document->status,
            'status_label' => $document->status_label,
            'status_badge_class' => $document->status_badge_class,
            'expires_at' => $document->expires_at,
            'file_name' => $document->latestVersion?->original_name,
            'file_url' => $document->file_path ? Storage::url($document->file_path) : null,
            'versions_count' => $document->versions_count,
            'updated_at' => $document->updated_at,
        ];
    }

    private function paginateCollection(Collection $documents, Request $request): LengthAwarePaginator
    {
        $perPage = 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $documents->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $documents->count(),
            $perPage,
            $page,
            [
                'path' => route('documents.index'),
                'query' => $request->query(),
            ],
        );
    }

    private function matchesStatusFilter(string $status, string $filter): bool
    {
        return match ($filter) {
            'approved' => $status === PropertyDocument::STATUS_APPROVED || $status === TenantDocument::STATUS_APPROVED,
            'pending_review' => in_array($status, [PropertyDocument::STATUS_PENDING, PropertyDocument::STATUS_UPLOADED, TenantDocument::STATUS_PENDING, TenantDocument::STATUS_UPLOADED], true),
            'expired' => $status === PropertyDocument::STATUS_EXPIRED || $status === TenantDocument::STATUS_EXPIRED,
            'rejected' => $status === PropertyDocument::STATUS_REJECTED || $status === TenantDocument::STATUS_REJECTED,
            default => true,
        };
    }

    private function ensurePropertyDocuments(Property $property): void
    {
        foreach (PropertyDocument::REQUIRED_DOCUMENTS as $documentType => $label) {
            $existingDocument = $property->documents()->where('document_type', $documentType)->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $property->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => PropertyDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }
    }

    private function ensureTenantDocuments(Tenant $tenant): void
    {
        foreach (TenantDocument::REQUIRED_DOCUMENTS as $documentType => $label) {
            $existingDocument = $tenant->documents()->where('document_type', $documentType)->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $tenant->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => TenantDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
                'expires_at' => null,
            ]);
        }
    }

    private function buildCustomDocumentType(array $existingTypes, string $label): string
    {
        $base = 'custom_' . Str::slug($label, '_');
        if ($base === 'custom_') {
            $base = 'custom_documento';
        }

        $candidate = $base;
        $counter = 2;

        while (in_array($candidate, $existingTypes, true)) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
