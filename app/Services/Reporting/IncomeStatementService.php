<?php

namespace App\Services\Reporting;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\ReportType;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\JournalEntryLine;
use Illuminate\Support\Collection;

class IncomeStatementService
{
    /**
     * Get income statement report for a date range.
     *
     * @return array{
     *     categories: Collection<int, object>,
     *     total_revenue: string,
     *     total_expense: string,
     *     net_surplus: string
     * }
     */
    public function getReport(string $startDate, string $endDate): array
    {
        // Fetch all categories for Income Statement, ordered by sequence
        $categories = AccountCategory::query()
            ->where('report_type', ReportType::IncomeStatement)
            ->with(['accounts' => function ($query) {
                $query->posting()
                    ->active()
                    ->orderBy('code');
            }])
            ->orderBy('sequence')
            ->get();

        if ($categories->isEmpty()) {
            return [
                'categories' => collect(),
                'total_revenue' => '0.00',
                'total_expense' => '0.00',
                'net_surplus' => '0.00',
            ];
        }

        // Collect all account IDs for batch calculation
        $allAccountIds = $categories->flatMap(
            fn ($category) => $category->accounts->pluck('id')
        )->toArray();

        // Calculate net movements for all accounts in the date range
        $movements = $this->calculateNetMovements($allAccountIds, $startDate, $endDate);

        // Build result structure
        $result = collect();
        $totalRevenue = 0.0;
        $totalExpense = 0.0;

        foreach ($categories as $category) {
            $categoryAccounts = collect();
            $categoryTotal = 0.0;

            foreach ($category->accounts as $account) {
                $movement = $movements->get($account->id, [
                    'debit_amount' => 0,
                    'credit_amount' => 0,
                ]);

                // Calculate net movement based on account type
                // Revenue: Credit is positive (Credit - Debit)
                // Expense: Debit is positive (Debit - Credit)
                $netAmount = $this->calculateNetAmount(
                    (float) $movement['debit_amount'],
                    (float) $movement['credit_amount'],
                    $account->account_type
                );

                // Skip accounts with zero movement
                if (abs($netAmount) < 0.01) {
                    continue;
                }

                $categoryAccounts->push((object) [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type->value,
                    'amount' => $this->formatAmount($netAmount),
                ]);

                $categoryTotal += $netAmount;
            }

            // Skip empty categories
            if ($categoryAccounts->isEmpty()) {
                continue;
            }

            // Determine category type (Revenue or Expense) from first account
            $categoryType = $category->accounts->first()?->account_type;

            $result->push((object) [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $categoryType?->value ?? 'UNKNOWN',
                'sequence' => $category->sequence,
                'accounts' => $categoryAccounts,
                'total' => $this->formatAmount($categoryTotal),
            ]);

            // Accumulate to totals
            if ($categoryType === AccountType::Revenue) {
                $totalRevenue += $categoryTotal;
            } elseif ($categoryType === AccountType::Expense) {
                $totalExpense += $categoryTotal;
            }
        }

        // Net Surplus = Total Revenue - Total Expense
        $netSurplus = $totalRevenue - $totalExpense;

        return [
            'categories' => $result,
            'total_revenue' => $this->formatAmount($totalRevenue),
            'total_expense' => $this->formatAmount($totalExpense),
            'net_surplus' => $this->formatAmount($netSurplus),
        ];
    }

    /**
     * Calculate net movements for multiple accounts within a date range.
     *
     * @param  array<string>  $accountIds
     * @return Collection<string, array{debit_amount: float, credit_amount: float}>
     */
    private function calculateNetMovements(array $accountIds, string $startDate, string $endDate): Collection
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
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as debit_amount,
                COALESCE(SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END), 0) as credit_amount
            ', [EntryDirection::Debit->value, EntryDirection::Credit->value])
            ->get();

        return $results->keyBy('chart_of_account_id')->map(function ($item) {
            return [
                'debit_amount' => (float) $item->debit_amount,
                'credit_amount' => (float) $item->credit_amount,
            ];
        });
    }

    /**
     * Calculate net amount based on account type.
     * Revenue accounts: Credit increases, Debit decreases
     * Expense accounts: Debit increases, Credit decreases
     */
    private function calculateNetAmount(float $debitAmount, float $creditAmount, AccountType $accountType): float
    {
        return match ($accountType) {
            AccountType::Revenue => $creditAmount - $debitAmount,
            AccountType::Expense => $debitAmount - $creditAmount,
            default => $debitAmount - $creditAmount,
        };
    }

    /**
     * Format amount as a decimal string with 2 decimal places.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
