<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\TransactionCategory;
use App\Models\Finance\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionType>
 */
class TransactionTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TransactionType>
     */
    protected $model = TransactionType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_system' => false,
            'code' => strtoupper(fake()->unique()->lexify('TXN-????')),
            'name' => fake()->words(2, true),
            'category' => fake()->randomElement(TransactionCategory::cases()),
            'is_active' => true,
        ];
    }

    public function system(): self
    {
        return $this->state(fn (): array => [
            'is_system' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
