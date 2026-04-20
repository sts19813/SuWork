<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateChargesRequest extends FormRequest
{
    protected $errorBag = 'generateCharges';

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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'rows' => ['nullable', 'array'],
            'rows.*.period_month' => ['required_with:rows', 'integer', 'between:1,12'],
            'rows.*.period_year' => ['required_with:rows', 'integer', 'between:2000,2200'],
            'rows.*.due_date' => ['required_with:rows', 'date'],
            'rows.*.amount' => ['required_with:rows', 'numeric', 'min:0.01'],
            'rows.*.concept' => ['required_with:rows', 'string', 'max:190'],
            'rows.*.notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
