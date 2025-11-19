<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionTypeStoreRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('transaction_types', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(TransactionCategory::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
