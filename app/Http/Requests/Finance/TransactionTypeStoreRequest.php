<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\EntryDirection;
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
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*.role' => ['required', 'string', 'max:255'],
            'accounts.*.label' => ['required', 'string', 'max:255'],
            'accounts.*.direction' => ['required', 'string', Rule::in(EntryDirection::values())],
            'accounts.*.account_type' => ['nullable', 'string', Rule::in(AccountType::values())],
            'accounts.*.chart_of_account_id' => ['nullable', 'uuid', 'exists:chart_of_accounts,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $accounts = $this->input('accounts', []);
            $roles = array_column($accounts, 'role');
            $uniqueRoles = array_unique($roles);

            if (count($roles) !== count($uniqueRoles)) {
                $validator->errors()->add('accounts', 'Each account role must be unique within the transaction type.');
            }
        });
    }
}
