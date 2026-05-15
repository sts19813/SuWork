<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorageItemRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'product_type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'condition' => 'required|in:bueno,regular,malo',
            'quantity' => 'required|integer|min:1',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }
}
