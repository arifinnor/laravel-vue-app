<?php

namespace App\Services\Reporting;

use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\NormalBalance;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntryLine;
use Illuminate\Support\Collection;

class GeneralLedgerService
{
    /**
     * Get ledger data for a specific account within a date range.
     *
     * @return array{
     *     account: ChartOfAccount,
     *     opening_balance: string,
     *     transactions: Collection<int, object>,
     *     closing_balance: string
     * }
     */
    public function getLedger(string $accountId, string $startDate, string $endDate): array
    {
        $account = ChartOfAccount::query()->findOrFail($accountId);

        $openingBalance = $this->calculateOpeningBalance($account, $startDate);
        $transactions = $this->fetchTransactions($account, $startDate, $endDate);
        $transactionsWithRunningBalance = $this->calculateRunningBalances($transactions, $openingBalance, $account->normal_balance);

        $closingBalance = $transactionsWithRunningBalance->isNotEmpty()
            ? $transactionsWithRunningBalance->last()->running_balance
            : $openingBalance;

        return [
            'account' => $account,
            'opening_balance' => $openingBalance,
            'transactions' => $transactionsWithRunningBalance,
            'closing_balance' => $closingBalance,
        ];
    }

    /**
     * Calculate opening balance for an account before the start date.
     *
     * Formula respects NormalBalance:
     * - DEBIT normal (Asset/Expense): balance = sum(Debits) - sum(Credits)
     * - CREDIT normal (Liability/Revenue/Equity): balance = sum(Credits) - sum(Debits)
     */
    private function calculateOpeningBalance(ChartOfAccount $account, string $startDate): string
    {
        $totals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.chart_of_account_id', $account->id)
            ->where('journal_entries.status', JournalStatus::Posted)
            ->where('journal_entries.transaction_date', '<', $startDate)
            ->selectRaw('
                SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN journal_entry_lines.direction = ? THEN journal_entry_lines.amount ELSE 0 END) as total_credit
            ', [EntryDirection::Debit->value, EntryDirection::Credit->value])
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);

        return $this->calculateBalance($totalDebit, $totalCredit, $account->normal_balance);
    }

    /**
     * Fetch transactions for an account within the date range.
     *
     * @return Collection<int, JournalEntryLine>
     */
    private function fetchTransactions(ChartOfAccount $account, string $startDate, string $endDate): Collection
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.chart_of_account_id', $account->id)
            ->where('journal_entries.status', JournalStatus::Posted)
            ->whereBetween('journal_entries.transaction_date', [$startDate, $endDate])
            ->orderBy('journal_entries.transaction_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->select([
                'journal_entry_lines.id',
                'journal_entry_lines.journal_entry_id',
                'journal_entry_lines.direction',
                'journal_entry_lines.amount',
                'journal_entry_lines.description as line_description',
                'journal_entries.transaction_date',
                'journal_entries.reference_number',
                'journal_entries.description as journal_description',
            ])
            ->get();
    }

    /**
     * Calculate running balance for each transaction.
     *
     * @param  Collection<int, JournalEntryLine>  $transactions
     * @return Collection<int, object>
     */
    private function calculateRunningBalances(Collection $transactions, string $openingBalance, NormalBalance $normalBalance): Collection
    {
        $currentBalance = (float) $openingBalance;

        return $transactions->map(function ($transaction) use (&$currentBalance, $normalBalance) {
            $amount = (float) $transaction->amount;
            $direction = $transaction->direction instanceof EntryDirection
                ? $transaction->direction
                : EntryDirection::from($transaction->direction);

            $movement = $this->calculateMovement($amount, $direction, $normalBalance);
            $currentBalance += $movement;

            return (object) [
                'id' => $transaction->id,
                'journal_entry_id' => $transaction->journal_entry_id,
                'transaction_date' => $transaction->transaction_date,
                'reference_number' => $transaction->reference_number,
                'description' => $transaction->line_description ?? $transaction->journal_description,
                'debit' => $direction === EntryDirection::Debit ? $this->formatAmount($amount) : null,
                'credit' => $direction === EntryDirection::Credit ? $this->formatAmount($amount) : null,
                'running_balance' => $this->formatAmount($currentBalance),
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
     * Calculate movement amount based on direction and normal balance.
     *
     * - Debit normal: Debit increases (+), Credit decreases (-)
     * - Credit normal: Credit increases (+), Debit decreases (-)
     */
    private function calculateMovement(float $amount, EntryDirection $direction, NormalBalance $normalBalance): float
    {
        $isIncreasing = match ($normalBalance) {
            NormalBalance::Debit => $direction === EntryDirection::Debit,
            NormalBalance::Credit => $direction === EntryDirection::Credit,
        };

        return $isIncreasing ? $amount : -$amount;
    }

    /**
     * Format amount as a decimal string with 2 decimal places.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
