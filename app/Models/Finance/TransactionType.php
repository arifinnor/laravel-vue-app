<?php

namespace App\Models\Finance;

use App\Enums\Finance\TransactionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_system',
        'code',
        'name',
        'category',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => TransactionCategory::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<TransactionEntryConfig>
     */
    public function configs(): HasMany
    {
        return $this->hasMany(TransactionEntryConfig::class, 'transaction_type_id');
    }

    /**
     * @param  Builder<TransactionType>  $query
     * @return Builder<TransactionType>
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * @param  Builder<TransactionType>  $query
     * @return Builder<TransactionType>
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }
}
