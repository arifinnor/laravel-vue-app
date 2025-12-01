<?php

use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\ReportType;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\User;
use App\Services\Reporting\IncomeStatementService;

beforeEach(function () {
    $this->service = app(IncomeStatementService::class);
});

describe('IncomeStatementService', function () {
    it('returns empty report when no categories exist', function () {
        $result = $this->service->getReport('2025-01-01', '2025-12-31');

        expect($result['categories'])->toBeEmpty()
            ->and($result['total_revenue'])->toBe('0.00')
            ->and($result['total_expense'])->toBe('0.00')
            ->and($result['net_surplus'])->toBe('0.00');
    });

    it('returns structured report with revenue and expense categories', function () {
        $revenueCategory = AccountCategory::factory()->create([
            'name' => 'Operational Revenue',
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $revenueCategory->id,
            'name' => 'Service Revenue',
        ]);

        $expenseCategory = AccountCategory::factory()->create([
            'name' => 'Operating Expenses',
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 2,
        ]);
        $expenseAccount = ChartOfAccount::factory()->expense()->create([
            'category_id' => $expenseCategory->id,
            'name' => 'Office Supplies',
        ]);

        // Revenue transaction: Credit 5000
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($revenueAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        // Expense transaction: Debit 2000
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-20',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($expenseAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 2000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['categories'])->toHaveCount(2)
            ->and($result['total_revenue'])->toBe('5000.00')
            ->and($result['total_expense'])->toBe('2000.00')
            ->and($result['net_surplus'])->toBe('3000.00');
    });

    it('calculates revenue as credit minus debit', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
        ]);

        // Credit 10000 (revenue increase)
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 10000.00,
        ]);

        // Debit 3000 (revenue decrease/refund)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 3000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        // Revenue: 10000 - 3000 = 7000
        expect($result['total_revenue'])->toBe('7000.00');
    });

    it('calculates expense as debit minus credit', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->expense()->create([
            'category_id' => $category->id,
        ]);

        // Debit 8000 (expense increase)
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 8000.00,
        ]);

        // Credit 1500 (expense decrease/reimbursement)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 1500.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        // Expense: 8000 - 1500 = 6500
        expect($result['total_expense'])->toBe('6500.00');
    });

    it('excludes voided transactions', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
        ]);

        // Posted transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        // Voided transaction - should be excluded
        $journal2 = JournalEntry::factory()->void()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Void,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 10000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['total_revenue'])->toBe('5000.00');
    });

    it('excludes draft transactions', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
        ]);

        // Posted transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 3000.00,
        ]);

        // Draft transaction - should be excluded
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Draft,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 7000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['total_revenue'])->toBe('3000.00');
    });

    it('excludes transactions outside date range', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
        ]);

        // Transaction in range
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        // Transaction before range
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-31',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 2000.00,
        ]);

        // Transaction after range
        $journal3 = JournalEntry::factory()->create([
            'transaction_date' => '2025-02-01',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal3)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 3000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['total_revenue'])->toBe('5000.00');
    });

    it('excludes categories from balance sheet report type', function () {
        // Income statement category
        $incomeCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $incomeCategory->id,
        ]);

        // Balance sheet category (should be excluded)
        $balanceCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::BalanceSheet,
            'sequence' => 2,
        ]);
        $assetAccount = ChartOfAccount::factory()->asset()->create([
            'category_id' => $balanceCategory->id,
        ]);

        // Revenue transaction
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($revenueAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        // Asset transaction (should not affect income statement)
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($assetAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 10000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['categories'])->toHaveCount(1)
            ->and($result['total_revenue'])->toBe('5000.00');
    });

    it('calculates net deficit when expenses exceed revenue', function () {
        $revenueCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $revenueAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $revenueCategory->id,
        ]);

        $expenseCategory = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 2,
        ]);
        $expenseAccount = ChartOfAccount::factory()->expense()->create([
            'category_id' => $expenseCategory->id,
        ]);

        // Revenue: 3000
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($revenueAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 3000.00,
        ]);

        // Expense: 8000
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-20',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($expenseAccount)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 8000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        // Net: 3000 - 8000 = -5000
        expect($result['net_surplus'])->toBe('-5000.00');
    });

    it('orders categories by sequence', function () {
        $category2 = AccountCategory::factory()->create([
            'name' => 'Other Revenue',
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 2,
        ]);
        ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category2->id,
        ]);

        $category1 = AccountCategory::factory()->create([
            'name' => 'Operating Revenue',
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category1->id,
        ]);

        // Create transactions for both
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($category1->accounts->first())->create([
            'direction' => EntryDirection::Credit,
            'amount' => 1000.00,
        ]);

        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-16',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($category2->accounts->first())->create([
            'direction' => EntryDirection::Credit,
            'amount' => 500.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        expect($result['categories']->first()->name)->toBe('Operating Revenue')
            ->and($result['categories']->last()->name)->toBe('Other Revenue');
    });

    it('skips accounts with zero movement', function () {
        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $activeAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
            'name' => 'Active Revenue',
        ]);
        $inactiveAccount = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
            'name' => 'Inactive Revenue',
        ]);

        // Only create transaction for active account
        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($activeAccount)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        $result = $this->service->getReport('2025-01-01', '2025-01-31');

        $accounts = $result['categories']->first()->accounts;
        expect($accounts)->toHaveCount(1)
            ->and($accounts->first()->name)->toBe('Active Revenue');
    });
});

describe('Income Statement Controller', function () {
    it('shows income statement index page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.income-statement.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Finance/Reports/IncomeStatement')
                ->has('filters.start_date')
                ->has('filters.end_date')
                ->where('total_revenue', '0.00')
                ->where('total_expense', '0.00')
                ->where('net_surplus', '0.00')
            );
    });

    it('shows income statement report with valid filters', function () {
        $user = User::factory()->create();

        $category = AccountCategory::factory()->create([
            'report_type' => ReportType::IncomeStatement,
            'sequence' => 1,
        ]);
        $account = ChartOfAccount::factory()->revenue()->create([
            'category_id' => $category->id,
        ]);

        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 5000.00,
        ]);

        $this->actingAs($user)
            ->get(route('finance.reports.income-statement.show', [
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Finance/Reports/IncomeStatement')
                ->has('categories', 1)
                ->where('total_revenue', '5000.00')
                ->where('total_expense', '0.00')
                ->where('net_surplus', '5000.00')
            );
    });

    it('validates required date fields', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.income-statement.show'))
            ->assertRedirect()
            ->assertSessionHasErrors(['start_date', 'end_date']);
    });

    it('validates start date is before end date', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports.income-statement.show', [
                'start_date' => '2025-01-31',
                'end_date' => '2025-01-01',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['start_date', 'end_date']);
    });

    it('requires authentication', function () {
        $this->get(route('finance.reports.income-statement.index'))
            ->assertRedirect(route('login'));
    });
});
