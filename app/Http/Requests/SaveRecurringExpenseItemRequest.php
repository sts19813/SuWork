<?php

namespace App\Http\Requests;

use App\Models\RecurringExpenseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRecurringExpenseItemRequest extends FormRequest
{
    protected $errorBag = 'recurringExpenseItem';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', Rule::in(array_keys(RecurringExpenseItem::FREQUENCY_LABELS))],
            'starts_on' => ['required', 'date'],
            'occurrences_count' => ['required', 'integer', 'min:1', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
