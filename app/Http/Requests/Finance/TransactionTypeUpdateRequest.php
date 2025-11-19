<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionTypeUpdateRequest extends FormRequest
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
        $transactionType = $this->route('transaction_type');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('transaction_types', 'code')->ignore($transactionType)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', Rule::in(TransactionCategory::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
