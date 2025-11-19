<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Finance\TransactionType
 */
class TransactionTypeResource extends JsonResource
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
            'is_system' => $this->is_system,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'configs' => $this->relationLoaded('configs')
                ? TransactionEntryConfigResource::collection($this->configs)->resolve()
                : [],
        ];
    }
}
