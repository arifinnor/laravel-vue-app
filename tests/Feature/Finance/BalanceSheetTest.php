<?php

use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\ReportType;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\User;
use App\Services\Reporting\BalanceSheetService;

beforeEach(function () {
    $this->service = app(BalanceSheetService::class);
});

describe('BalanceSheetService', function () {
    it('returns empty report when no categories exist', function () {
        $result = $this->service->getReport('2025-12-31');

        expect($result['asset_categories'])->toBeEmpty()
            ->and($result['liability_categories'])->toBeEmpty()
            ->and($result['equity_categories'])->toBeEmpty()
            ->and($result['total_assets'])->toBe('0.00')
            ->and($result['total_liabilities'])->toBe('0.00')
            ->and($result['total_equity'])->toBe('0.00')
            ->and($result['current_year_earnings'])->toBe('0.00')
            ->and($result['total_liabilities_equity'])->toBe('0.00')
            ->and($result['is_balanced'])->toBeTrue();
    });

    it('returns structured report with asset, liability and equity categories', function () {
        // Asset category
        $assetCategory = AccountCategory::factory()->create([
            'name' => 'Current Assets',
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $cashAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $assetCategory->id,
            'name' => 'Cash',
        ]);

        // Liability category
        $liabilityCategory = AccountCategory::factory()->create([
            'name' => 'Current Liabilities',
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 2,
        ]);
        $payableAccount = ChartOfAccount::factory()->liability()->create([
            'category_id' => $liabilityCategory->id,
            'name' => 'Accounts Payable',
        ]);

        // Equity category
        $equityCategory = AccountCategory::factory()->create([
            'name' => 'Owner Equity',
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 3,
        ]);
        $capitalAccount = ChartOfAccount::factory()->equity()->create([
            'category_id' => $equityCategory->id,
            'name' => 'Capital Stock',
        ]);

        // Asset transaction: Debit 10000
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($cashAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        // Liability transaction: Credit 3000
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($payableAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 3000.00,
        ]);

        // Equity transaction: Credit 7000
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($capitalAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 7000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        expect($result['asset_categories'])->toHaveCount(1)
            ->and($result['liability_categories'])->toHaveCount(1)
            ->and($result['equity_categories'])->toHaveCount(1)
            ->and($result['total_assets'])->toBe('10000.00')
            ->and($result['total_liabilities'])->toBe('3000.00')
            ->and($result['total_equity'])->toBe('7000.00')
            ->and($result['is_balanced'])->toBeTrue();
    });

    it('calculates asset balance as debit minus credit', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
        ]);

        // Debit 15000 (asset increase)
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 15000.00,
        ]);

        // Credit 5000 (asset decrease)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Asset: 15000 - 5000 = 10000
        expect($result['total_assets'])->toBe('10000.00');
    });

    it('calculates liability balance as credit minus debit', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->liability()->create([
            'category_id' => $category->id,
        ]);

        // Credit 8000 (liability increase)
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 8000.00,
        ]);

        // Debit 2000 (liability decrease/payment)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 2000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Liability: 8000 - 2000 = 6000
        expect($result['total_liabilities'])->toBe('6000.00');
    });

    it('calculates equity balance as credit minus debit', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->equity()->create([
            'category_id' => $category->id,
        ]);

        // Credit 20000 (equity increase)
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 20000.00,
        ]);

        // Debit 5000 (equity decrease/withdrawal)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Equity: 20000 - 5000 = 15000
        expect($result['total_equity'])->toBe('15000.00');
    });

    it('includes cumulative balances from beginning of time up to as-of date', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
        ]);

        // Previous year transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2024-06-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        // Current year transaction
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Balance sheet is cumulative: 10000 + 5000 = 15000
        expect($result['total_assets'])->toBe('15000.00');
    });

    it('excludes transactions after as-of date', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
        ]);

        // Transaction before as-of date
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        // Transaction after as-of date (should be excluded)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-02-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        expect($result['total_assets'])->toBe('10000.00');
    });

    it('excludes voided transactions', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
        ]);

        // Posted transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);

        // Voided transaction - should be excluded
        $journal2 = JournalEntry::factory()->void()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Void,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        expect($result['total_assets'])->toBe('5000.00');
    });

    it('excludes income statement categories', function () {
        // Balance sheet category
        $assetCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $assetAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $assetCategory->id,
        ]);

        // Income statement category (should be excluded from balance sheet)
        $revenueCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 2,
        ]);
        $revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $revenueCategory->id,
        ]);

        // Asset transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($assetAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        // Revenue transaction (should not appear in balance sheet categories)
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($revenueAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 10000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        expect($result['asset_categories'])->toHaveCount(1)
            ->and($result['liability_categories'])->toBeEmpty()
            ->and($result['equity_categories'])->toBeEmpty();
    });

    it('injects current year earnings from income statement', function () {
        // Balance sheet accounts
        $assetCategory = AccountCategory::factory()->create([
            'name' => 'Current Assets',
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $cashAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $assetCategory->id,
        ]);

        $equityCategory = AccountCategory::factory()->create([
            'name' => 'Owner Equity',
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 2,
        ]);
        $capitalAccount = ChartOfAccount::factory()->equity()->create([
            'category_id' => $equityCategory->id,
        ]);

        // Income statement accounts
        $revenueCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 3,
        ]);
        $revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $revenueCategory->id,
        ]);

        // Journal entry: Cash 15000, Capital 10000, Revenue 5000
        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($cashAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 15000.00,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($capitalAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 10000.00,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($revenueAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Assets = 15000, Equity = 10000, Current Year Earnings = 5000 (from revenue)
        expect($result['total_assets'])->toBe('15000.00')
            ->and($result['total_equity'])->toBe('10000.00')
            ->and($result['current_year_earnings'])->toBe('5000.00')
            ->and($result['total_liabilities_equity'])->toBe('15000.00')
            ->and($result['is_balanced'])->toBeTrue();
    });

    it('handles deficit in current year earnings', function () {
        // Balance sheet accounts
        $assetCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $cashAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $assetCategory->id,
        ]);

        $equityCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 2,
        ]);
        $capitalAccount = ChartOfAccount::factory()->equity()->create([
            'category_id' => $equityCategory->id,
        ]);

        // Income statement accounts
        $expenseCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 3,
        ]);
        $expenseAccount = ChartOfAccount::factory()->expense()->create([
            'category_id' => $expenseCategory->id,
        ]);

        // Journal: Cash 10000, Capital 15000, Expense 5000
        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($cashAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($expenseAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($capitalAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 15000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        // Assets = 10000, Equity = 15000, Current Year Earnings = -5000 (expense)
        // Total Liab & Equity = 15000 + (-5000) = 10000
        expect($result['total_assets'])->toBe('10000.00')
            ->and($result['total_equity'])->toBe('15000.00')
            ->and($result['current_year_earnings'])->toBe('-5000.00')
            ->and($result['total_liabilities_equity'])->toBe('10000.00')
            ->and($result['is_balanced'])->toBeTrue();
    });

    it('skips accounts with zero balance', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $activeAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
            'name' => 'Active Cash',
        ]);
        $inactiveAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
            'name' => 'Inactive Cash',
        ]);

        // Only create transaction for active account
        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($activeAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-31');

        $accounts = $result['asset_categories']->first()->accounts;
        expect($accounts)->toHaveCount(1)
            ->and($accounts->first()->name)->toBe('Active Cash');
    });
});

describe('Balance Sheet Controller', function () {
    it('shows balance sheet index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.balance-sheet.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Finance/Reports/BalanceSheet')
                ->has('filters.as_of_date')
                ->where('total_assets', '0.00')
                ->where('total_liabilities', '0.00')
                ->where('total_equity', '0.00')
                ->where('current_year_earnings', '0.00')
                ->where('is_balanced', true)
            );
    });

    it('shows balance sheet report with valid filter', function () {
        $user = User::factory()->create();

        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->asset()->create([
            'category_id' => $category->id,
        ]);

        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        $this->actingAs($user)
            ->get(route('finance.reports.balance-sheet.show', [
                'as_of_date' => '2025-01-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Finance/Reports/BalanceSheet')
                ->has('asset_categories', 1)
                ->where('total_assets', '10000.00')
            );
    });

    it('validates required as_of_date field', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.balance-sheet.show'))
            ->assertRedirect()
            ->assertSessionHasErrors(['as_of_date']);
    });

    it('validates as_of_date is a valid date', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.balance-sheet.show', [
                'as_of_date' => 'not-a-date',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['as_of_date']);
    });

    it('requires authentication', function () {
        $this->get(route('finance.reports.balance-sheet.index'))
            ->assertRedirect(route('login'));
    });
});
