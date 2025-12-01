<?php

namespace App\Services\Reporting;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\ReportType;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\JournalEntryLine;
use Illuminate\Support\Collection;

class BalanceSheetService
{
    public function __construct(
        private IncomeStatementService $incomeStatementService
    ) {}

    /**
     * Get balance sheet report as of a specific date.
     *
     * @return array{
     *     asset_categories: Collection<int, object>,
     *     liability_categories: Collection<int, object>,
     *     equity_categories: Collection<int, object>,
     *     total_assets: string,
     *     total_liabilities: string,
     *     total_equity: string,
     *     current_year_earnings: string,
     *     total_liabilities_equity: string,
     *     is_balanced: bool
     * }
     */
    public function getReport(string $asOfDate): array
    {
        // Fetch all categories for Balance Sheet, ordered by sequence
        $categories = AccountCategory::query()
            ->where('report_type', ReportType::BalanceSheet)
            ->with(['accounts' => function ($query) {
                $query->posting()
                    ->active()
                    ->orderBy('code');
            }])
            ->orderBy('sequence')
            ->get();

        if ($categories->isEmpty()) {
            return $this->emptyResult();
        }

        // Collect all account IDs for batch calculation
        $allAccountIds = $categories->flatMap(
            fn ($category) => $category->accounts->pluck('id')
        )->toArray();

        // Calculate cumulative balances up to the as-of date
        $balances = $this->calculateCumulativeBalances($allAccountIds, $asOfDate);

        // Build result structure
        $assetCategories = collect();
        $liabilityCategories = collect();
        $equityCategories = collect();

        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;

        foreach ($categories as $category) {
            $categoryAccounts = collect();
            $categoryTotal = 0.0;

            foreach ($category->accounts as $account) {
                $balance = $balances->get($account->id, [
                    'debit_amount' => 0,
                    'credit_amount' => 0,
                ]);

                // Calculate net balance based on account type
                $netBalance = $this->calculateNetBalance(
                    (float) $balance['debit_amount'],
                    (float) $balance['credit_amount'],
                    $account->account_type
                );

                // Skip accounts with zero balance
                if (abs($netBalance) < 0.01) {
                    continue;
                }

                $categoryAccounts->push((object) [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type->value,
                    'balance' => $this->formatAmount($netBalance),
                ]);

                $categoryTotal += $netBalance;
            }

            // Skip empty categories
            if ($categoryAccounts->isEmpty()) {
                continue;
            }

            // Determine category type from first account
            $categoryType = $category->accounts->first()?->account_type;

            $categoryData = (object) [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $categoryType?->value ?? 'UNKNOWN',
                'sequence' => $category->sequence,
                'accounts' => $categoryAccounts,
                'total' => $this->formatAmount($categoryTotal),
            ];

            // Assign to appropriate collection
            match ($categoryType) {
                AccountType::Asset => $assetCategories->push($categoryData),
                AccountType::Liability => $liabilityCategories->push($categoryData),
                AccountType::Equity => $equityCategories->push($categoryData),
                default => null,
            };

            // Accumulate totals
            match ($categoryType) {
                AccountType::Asset => $totalAssets += $categoryTotal,
                AccountType::Liability => $totalLiabilities += $categoryTotal,
                AccountType::Equity => $totalEquity += $categoryTotal,
                default => null,
            };
        }

        // Calculate Current Year Earnings from Income Statement
        $currentYearEarnings = $this->calculateCurrentYearEarnings($asOfDate);

        // Total Liabilities & Equity = Liabilities + Equity + Current Year Earnings
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity + $currentYearEarnings;

        // Validation: Assets should equal Liabilities + Equity
        $isBalanced = abs($totalAssets - $totalLiabilitiesEquity) < 0.01;

        return [
            'asset_categories' => $assetCategories,
            'liability_categories' => $liabilityCategories,
            'equity_categories' => $equityCategories,
            'total_assets' => $this->formatAmount($totalAssets),
            'total_liabilities' => $this->formatAmount($totalLiabilities),
            'total_equity' => $this->formatAmount($totalEquity),
            'current_year_earnings' => $this->formatAmount($currentYearEarnings),
            'total_liabilities_equity' => $this->formatAmount($totalLiabilitiesEquity),
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Calculate cumulative balances for accounts up to a specific date.
     *
     * @param  array<string>  $accountIds
     * @return Collection<string, array{debit_amount: float, credit_amount: float}>
     */
    private function calculateCumulativeBalances(array $accountIds, string $asOfDate): Collection
    {
        if (empty($accountIds)) {
            return collect();
        }

        $results = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_entry_lines.chart_of_account_id', $accountIds)
            ->where('journal_entries.status', JournalStatus::Posted)
            ->where('journal_entries.transaction_date', '<=', $asOfDate)
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
     * Calculate net balance based on account type.
     * Assets: Debit increases, Credit decreases (Dr - Cr)
     * Liabilities & Equity: Credit increases, Debit decreases (Cr - Dr)
     */
    private function calculateNetBalance(float $debitAmount, float $creditAmount, AccountType $accountType): float
    {
        return match ($accountType) {
            AccountType::Asset => $debitAmount - $creditAmount,
            AccountType::Liability, AccountType::Equity => $creditAmount - $debitAmount,
            default => $debitAmount - $creditAmount,
        };
    }

    /**
     * Calculate Current Year Earnings (Net Surplus from Income Statement).
     * Uses the first day of the fiscal year up to the as-of date.
     */
    private function calculateCurrentYearEarnings(string $asOfDate): float
    {
        // Assume fiscal year starts on January 1st of the same year
        $fiscalYearStart = date('Y-01-01', strtotime($asOfDate));

        $incomeStatement = $this->incomeStatementService->getReport($fiscalYearStart, $asOfDate);

        return (float) $incomeStatement['net_surplus'];
    }

    /**
     * Format amount as a decimal string with 2 decimal places.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Return empty result structure.
     *
     * @return array<string, mixed>
     */
    private function emptyResult(): array
    {
        return [
            'asset_categories' => collect(),
            'liability_categories' => collect(),
            'equity_categories' => collect(),
            'total_assets' => '0.00',
            'total_liabilities' => '0.00',
            'total_equity' => '0.00',
            'current_year_earnings' => '0.00',
            'total_liabilities_equity' => '0.00',
            'is_balanced' => true,
        ];
    }
}
