<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\EntryDirection;
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
            'accounts' => ['sometimes', 'array', 'min:1'],
            'accounts.*.id' => ['nullable', 'uuid', 'exists:transaction_accounts,id'],
            'accounts.*.role' => ['required_with:accounts', 'string', 'max:255'],
            'accounts.*.label' => ['required_with:accounts', 'string', 'max:255'],
            'accounts.*.direction' => ['required_with:accounts', 'string', Rule::in(EntryDirection::values())],
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

            if (empty($accounts)) {
                return;
            }

            $roles = array_column($accounts, 'role');
            $uniqueRoles = array_unique($roles);

            if (count($roles) !== count($uniqueRoles)) {
                $validator->errors()->add('accounts', 'Each account role must be unique within the transaction type.');
            }
        });
    }
}
