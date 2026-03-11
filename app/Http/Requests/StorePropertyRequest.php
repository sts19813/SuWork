<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Models\PropertyOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'current_tenant_name' => ['nullable', 'string', 'max:255'],
            'contract_expires_at' => ['nullable', 'date'],
            'owners' => ['required', 'array', 'min:1'],
            'owners.*.name' => ['required', 'string', 'max:255'],
            'owners.*.phone' => ['required', 'string', 'max:40'],
            'owners.*.email' => ['required', 'email', 'max:255'],
            'owners.*.owner_type' => ['required', Rule::in(array_keys(PropertyOwner::OWNER_TYPE_LABELS))],
            'owners.*.bank_name' => ['nullable', 'string', 'max:255'],
            'owners.*.clabe' => ['nullable', 'digits:18'],
            'owners.*.account_holder' => ['nullable', 'string', 'max:255'],
            'owners.*.payment_method' => ['nullable', Rule::in(array_keys(PropertyOwner::PAYMENT_METHOD_LABELS))],
            'documents' => ['nullable', 'array'],
            'documents.title_deed' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.property_tax' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.cfe_receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.water_receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.cadastral_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
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
            'full_address' => 'dirección completa',
            'owners.*.name' => 'nombre del propietario',
            'owners.*.phone' => 'teléfono del propietario',
            'owners.*.email' => 'correo del propietario',
            'owners.*.owner_type' => 'tipo de titular',
            'owners.*.clabe' => 'CLABE',
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
            'property_type_id.exists' => 'El tipo de propiedad seleccionado no es válido.',
            'zone_id.required' => 'Debes seleccionar una zona.',
            'zone_id.exists' => 'La zona seleccionada no es válida.',
        ];
    }
}
