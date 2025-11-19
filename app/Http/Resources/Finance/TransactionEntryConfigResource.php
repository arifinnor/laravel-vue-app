<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Finance\TransactionEntryConfig
 */
class TransactionEntryConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_type_id' => $this->transaction_type_id,
            'config_key' => $this->config_key,
            'ui_label' => $this->ui_label,
            'position' => $this->position->value,
            'account_type_filter' => $this->account_type_filter,
            'account_id' => $this->account_id,
            'is_required' => $this->is_required,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'code' => $this->account->code,
                'name' => $this->account->name,
                'account_type' => $this->account->account_type->value,
            ]),
        ];
    }
}
