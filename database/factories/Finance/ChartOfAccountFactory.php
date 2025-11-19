<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\NormalBalance;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Finance\ChartOfAccount>
 */
class ChartOfAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountType = fake()->randomElement(AccountType::cases());

        return [
            'category_id' => AccountCategory::factory(),
            'code' => fake()->unique()->numerify('#-####'),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'account_type' => $accountType->value,
            'normal_balance' => $this->normalBalanceFor($accountType)->value,
            'is_posting' => true,
            'is_cash' => false,
            'is_active' => true,
        ];
    }

    public function asset(): self
    {
        return $this->state(fn (): array => [
            'account_type' => AccountType::Asset->value,
            'normal_balance' => NormalBalance::Debit->value,
        ]);
    }

    public function liability(): self
    {
        return $this->state(fn (): array => [
            'account_type' => AccountType::Liability->value,
            'normal_balance' => NormalBalance::Credit->value,
        ]);
    }

    public function equity(): self
    {
        return $this->state(fn (): array => [
            'account_type' => AccountType::Equity->value,
            'normal_balance' => NormalBalance::Credit->value,
        ]);
    }

    public function revenue(): self
    {
        return $this->state(fn (): array => [
            'account_type' => AccountType::Revenue->value,
            'normal_balance' => NormalBalance::Credit->value,
        ]);
    }

    public function expense(): self
    {
        return $this->state(fn (): array => [
            'account_type' => AccountType::Expense->value,
            'normal_balance' => NormalBalance::Debit->value,
        ]);
    }

    public function header(): self
    {
        return $this->state(fn (): array => [
            'is_posting' => false,
            'parent_id' => null,
        ]);
    }

    public function cash(): self
    {
        return $this->state(fn (): array => [
            'is_cash' => true,
            'account_type' => AccountType::Asset->value,
            'normal_balance' => NormalBalance::Debit->value,
        ]);
    }

    public function forParent(ChartOfAccount $parent): self
    {
        return $this->state(fn () => [
            'parent_id' => $parent->getKey(),
        ]);
    }

    private function normalBalanceFor(AccountType $accountType): NormalBalance
    {
        return match ($accountType) {
            AccountType::Asset,
            AccountType::Expense => NormalBalance::Debit,
            default => NormalBalance::Credit,
        };
    }
}
