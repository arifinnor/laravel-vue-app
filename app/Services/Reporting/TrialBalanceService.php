<?php

namespace App\Services\Reporting;

use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\NormalBalance;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntryLine;
use Illuminate\Support\Collection;

class TrialBalanceService
{
    /**
     * Get trial balance report for all posting accounts within a date range.
     *
     * @return Collection<int, object>
     */
    public function getReport(string $startDate, string $endDate): Collection
    {
        // Fetch all posting accounts with category
        $accounts = ChartOfAccount::query()
            ->with('category')
            ->posting()
            ->active()
            ->orderBy('code')
            ->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $accountIds = $accounts->pluck('id')->toArray();

        // Calculate opening balances (before start date) for all accounts
        $openingBalances = $this->calculateOpeningBalances($accountIds, $startDate);

        // Calculate period mutations (between start and end date) for all accounts
        $mutations = $this->calculateMutations($accountIds, $startDate, $endDate);

        // Build result collection
        $result = collect();

        foreach ($accounts as $account) {
            $accountId = $account->id;
            $opening = $openingBalances->get($accountId, ['total_debit' => 0, 'total_credit' => 0]);
            $mutation = $mutations->get($accountId, ['debit_mutation' => 0, 'credit_mutation' => 0]);

            $openingDebit = (float) ($opening['total_debit'] ?? 0);
            $openingCredit = (float) ($opening['total_credit'] ?? 0);
            $debitMutation = (float) ($mutation['debit_mutation'] ?? 0);
            $creditMutation = (float) ($mutation['credit_mutation'] ?? 0);

            // Calculate opening balance
            $openingBalance = $this->calculateBalance($openingDebit, $openingCredit, $account->normal_balance);

            // Calculate closing balance
            $closingDebit = $openingDebit + $debitMutation;
            $closingCredit = $openingCredit + $creditMutation;
            $closingBalance = $this->calculateBalance($closingDebit, $closingCredit, $account->normal_balance);

            // Filter out accounts with zero opening balance AND zero mutations
            $openingBalanceFloat = (float) $openingBalance;
            if (abs($openingBalanceFloat) < 0.01 && abs($debitMutation) < 0.01 && abs($creditMutation) < 0.01) {
                continue;
            }

            $result->push((object) [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'normal_balance' => $account->normal_balance->value,
                'category_id' => $account->category_id,
                'category_name' => $account->category?->name ?? '',
                'category_sequence' => $account->category?->sequence ?? 999,
                'opening_balance' => $openingBalance,
                'debit_mutation' => $this->formatAmount($debitMutation),
                'credit_mutation' => $this->formatAmount($creditMutation),
                'closing_balance' => $closingBalance,
            ]);
        }

        // Sort by category sequence, then account code
        return $result->sortBy([
            ['category_sequence', 'asc'],
            ['code', 'asc'],
        ])->values();
    }

    /**
     * Calculate opening balances for multiple accounts before the start date.
     *
     * @param  array<string>  $accountIds
     * @return Collection<string, array{total_debit: float, total_credit: float}>
     */
    private function calculateOpeningBalances(array $accountIds, string $startDate): Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        $results = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_entry_lines.chart_of_account_id', $accountIds)
            ->where('journal_entries.status', JournalStatus::Posted)
            ->where('journal_entries.transaction_date', '<', $startDate)
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->selectRaw('
                journal_entry_lines.chart_of_account_id,
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as total_credit
            ', [EntryDirection::Debit->value, EntryDirection::Credit->value])
            ->get();

        return $results->keyBy('chart_of_account_id')->map(function ($item) {
            return [
                'total_debit' => (float) $item->total_debit,
                'total_credit' => (float) $item->total_credit,
            ];
        });
    }

    /**
     * Calculate period mutations (debit and credit) for multiple accounts.
     *
     * @param  array<string>  $accountIds
     * @return Collection<string, array{debit_mutation: float, credit_mutation: float}>
     */
    private function calculateMutations(array $accountIds, string $startDate, string $endDate): Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        $results = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_entry_lines.chart_of_account_id', $accountIds)
            ->where('journal_entries.status', JournalStatus::Posted)
            ->whereBetween('journal_entries.transaction_date', [$startDate, $endDate])
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->selectRaw('
                journal_entry_lines.chart_of_account_id,
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as debit_mutation,
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as credit_mutation
            ', [EntryDirection::Debit->value, EntryDirection::Credit->value])
            ->get();

        return $results->keyBy('chart_of_account_id')->map(function ($item) {
            return [
                'debit_mutation' => (float) $item->debit_mutation,
                'credit_mutation' => (float) $item->credit_mutation,
            ];
        });
    }

    /**
     * Calculate balance based on normal balance type.
     */
    private function calculateBalance(float $totalDebit, float $totalCredit, NormalBalance $normalBalance): string
    {
        $balance = match ($normalBalance) {
            NormalBalance::Debit => $totalDebit - $totalCredit,
            NormalBalance::Credit => $totalCredit - $totalDebit,
        };

        return $this->formatAmount($balance);
    }

    /**
     * Format amount as a decimal string with 2 decimal places.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
