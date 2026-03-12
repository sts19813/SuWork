<?php

namespace App\Http\Requests;

use App\Models\Owner;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'internal_name' => ['required', 'string', 'max:150'],
            'internal_reference' => ['nullable', 'string', 'max:100'],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'zone_id' => ['required', 'exists:zones,id'],
            'full_address' => ['required', 'string', 'max:255'],
            'complex_name' => ['nullable', 'string', 'max:255'],
            'official_number' => ['nullable', 'string', 'max:100'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'facade_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'status' => ['required', Rule::in(array_keys(Property::STATUS_LABELS))],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'current_tenant_name' => ['nullable', 'string', 'max:255'],
            'contract_expires_at' => ['nullable', 'date'],
            'owner_ids' => ['nullable', 'array'],
            'owner_ids.*' => ['integer', 'exists:owners,id'],
            'new_owners' => ['nullable', 'array'],
            'new_owners.*.name' => ['nullable', 'string', 'max:255'],
            'new_owners.*.phone' => ['nullable', 'string', 'max:40'],
            'new_owners.*.email' => ['nullable', 'email', 'max:255'],
            'new_owners.*.rfc' => ['nullable', 'string', 'max:20'],
            'new_owners.*.curp' => ['nullable', 'string', 'max:20'],
            'new_owners.*.owner_type' => ['nullable', Rule::in(array_keys(Owner::OWNER_TYPE_LABELS))],
            'new_owners.*.bank_name' => ['nullable', 'string', 'max:255'],
            'new_owners.*.clabe' => ['nullable', 'digits:18'],
            'new_owners.*.account_holder' => ['nullable', 'string', 'max:255'],
            'new_owners.*.payment_method' => ['nullable', Rule::in(array_keys(Owner::PAYMENT_METHOD_LABELS))],
            'new_owners.*.address' => ['nullable', 'string', 'max:2000'],
            'new_owners.*.notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
            'documents.title_deed' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.property_tax' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.cfe_receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.water_receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.cadastral_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'existing_custom_documents' => ['nullable', 'array'],
            'existing_custom_documents.*.label' => ['nullable', 'string', 'max:150'],
            'existing_custom_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'existing_custom_documents.*.expires_at' => ['nullable', 'date'],
            'new_custom_documents' => ['nullable', 'array'],
            'new_custom_documents.*.label' => ['nullable', 'string', 'max:150'],
            'new_custom_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'new_custom_documents.*.expires_at' => ['nullable', 'date'],
            'inventory_areas' => ['nullable', 'array'],
            'inventory_areas.*.name' => ['nullable', 'string', 'max:120'],
            'inventory_areas.*.notes' => ['nullable', 'string', 'max:1500'],
            'inventory_areas.*.items' => ['nullable', 'array'],
            'inventory_areas.*.items.*.name' => ['nullable', 'string', 'max:120'],
            'inventory_areas.*.items.*.condition' => ['nullable', 'string', 'max:120'],
            'inventory_areas.*.items.*.notes' => ['nullable', 'string', 'max:500'],
            'inventory_areas.*.photos' => ['nullable', 'array', 'max:3'],
            'inventory_areas.*.photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'internal_name' => 'nombre interno',
            'property_type_id' => 'tipo de propiedad',
            'zone_id' => 'zona',
            'full_address' => 'direccion completa',
            'tenant_id' => 'inquilino',
            'owner_ids' => 'propietarios',
            'new_owners.*.name' => 'nombre del propietario',
            'new_owners.*.phone' => 'telefono del propietario',
            'new_owners.*.email' => 'correo del propietario',
            'new_owners.*.owner_type' => 'tipo de titular',
            'new_owners.*.clabe' => 'CLABE',
            'existing_custom_documents.*.label' => 'nombre del documento personalizado',
            'existing_custom_documents.*.file' => 'archivo del documento personalizado',
            'new_custom_documents.*.label' => 'nombre del nuevo documento',
            'new_custom_documents.*.file' => 'archivo del nuevo documento',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_type_id.required' => 'Debes seleccionar un tipo de propiedad.',
            'property_type_id.exists' => 'El tipo de propiedad seleccionado no es valido.',
            'zone_id.required' => 'Debes seleccionar una zona.',
            'zone_id.exists' => 'La zona seleccionada no es valida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ownerIds = collect($this->input('owner_ids', []))
                ->filter(fn ($ownerId) => filled($ownerId));

            $newOwners = collect($this->input('new_owners', []))
                ->filter(fn ($owner) => is_array($owner));

            $validNewOwners = $newOwners->filter(
                fn ($owner) => filled($owner['name'] ?? null) && filled($owner['phone'] ?? null),
            );

            if ($ownerIds->isEmpty() && $validNewOwners->isEmpty()) {
                $validator->errors()->add('owner_ids', 'Debes seleccionar al menos un propietario o capturar uno nuevo.');
            }

            foreach ($newOwners as $index => $ownerData) {
                $hasAnyData = collect($ownerData)->contains(fn ($value) => filled($value));
                if (!$hasAnyData) {
                    continue;
                }

                if (blank($ownerData['name'] ?? null)) {
                    $validator->errors()->add("new_owners.$index.name", 'El nombre del nuevo propietario es obligatorio.');
                }

                if (blank($ownerData['phone'] ?? null)) {
                    $validator->errors()->add("new_owners.$index.phone", 'El telefono del nuevo propietario es obligatorio.');
                }
            }

            $status = (string) $this->input('status');
            if ($status === Property::STATUS_OCCUPIED && blank($this->input('tenant_id'))) {
                $validator->errors()->add('tenant_id', 'Debes seleccionar un inquilino cuando la propiedad esta ocupada.');
            }

            foreach ((array) $this->input('new_custom_documents', []) as $index => $documentData) {
                if (!is_array($documentData)) {
                    continue;
                }

                $hasLabel = filled($documentData['label'] ?? null);
                $hasFile = $this->hasFile("new_custom_documents.$index.file");

                if ($hasLabel && !$hasFile) {
                    $validator->errors()->add(
                        "new_custom_documents.$index.file",
                        'Debes cargar el archivo del nuevo documento personalizado.',
                    );
                }

                if (!$hasLabel && $hasFile) {
                    $validator->errors()->add(
                        "new_custom_documents.$index.label",
                        'Debes capturar el nombre del nuevo documento personalizado.',
                    );
                }
            }
        });
    }
}
