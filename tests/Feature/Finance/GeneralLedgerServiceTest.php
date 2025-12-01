<?php

use App\Enums\Finance\EntryDirection;
use App\Enums\Finance\JournalStatus;
use App\Enums\Finance\NormalBalance;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Services\Reporting\GeneralLedgerService;

beforeEach(function () {
    $this->service = app(GeneralLedgerService::class);
});

describe('getLedger', function () {
    it('returns account details with opening and closing balances', function () {
        $account = ChartOfAccount::factory()->asset()->create();

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-12-31');

        expect($result)->toHaveKeys(['account', 'opening_balance', 'transactions', 'closing_balance'])
            ->and($result['account']->id)->toBe($account->id)
            ->and($result['opening_balance'])->toBe('0.00');
    });

    it('calculates opening balance from transactions before start date for debit normal account', function () {
        $account = ChartOfAccount::factory()->asset()->create([
            'normal_balance' => NormalBalance::Debit,
        ]);

        // Transaction before start date: Debit 1000
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 1000.00,
        ]);

        // Transaction before start date: Credit 400
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-20',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 400.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-12-31');

        // Debit normal: 1000 - 400 = 600
        expect($result['opening_balance'])->toBe('600.00');
    });

    it('calculates opening balance from transactions before start date for credit normal account', function () {
        $account = ChartOfAccount::factory()->liability()->create([
            'normal_balance' => NormalBalance::Credit,
        ]);

        // Transaction before start date: Credit 2000
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->create([
            'direction' => EntryDirection::Credit,
            'amount' => 2000.00,
        ]);

        // Transaction before start date: Debit 500
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-20',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->create([
            'direction' => EntryDirection::Debit,
            'amount' => 500.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-12-31');

        // Credit normal: 2000 - 500 = 1500
        expect($result['opening_balance'])->toBe('1500.00');
    });

    it('fetches transactions within date range ordered by date', function () {
        $account = ChartOfAccount::factory()->asset()->create();

        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'reference_number' => 'TRX-001',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->debit()->create([
            'amount' => 500.00,
        ]);

        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-05',
            'reference_number' => 'TRX-002',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->debit()->create([
            'amount' => 300.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['transactions'])->toHaveCount(2)
            ->and($result['transactions']->first()->reference_number)->toBe('TRX-002')
            ->and($result['transactions']->last()->reference_number)->toBe('TRX-001');
    });

    it('excludes voided transactions from opening balance and transactions list', function () {
        $account = ChartOfAccount::factory()->asset()->create();

        // Posted transaction in period
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->debit()->create([
            'amount' => 500.00,
        ]);

        // Voided transaction in period - should be excluded
        $journal2 = JournalEntry::factory()->void()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Void,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->debit()->create([
            'amount' => 1000.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['transactions'])->toHaveCount(1);
    });

    it('calculates running balance correctly for debit normal account', function () {
        $account = ChartOfAccount::factory()->asset()->create([
            'normal_balance' => NormalBalance::Debit,
        ]);

        // Opening balance before period: 1000
        $journal0 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-31',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal0)->forAccount($account)->debit()->create([
            'amount' => 1000.00,
        ]);

        // Transaction 1: Debit 500 -> balance 1500
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->debit()->create([
            'amount' => 500.00,
        ]);

        // Transaction 2: Credit 200 -> balance 1300
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->credit()->create([
            'amount' => 200.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['opening_balance'])->toBe('1000.00')
            ->and($result['transactions'])->toHaveCount(2)
            ->and($result['transactions'][0]->running_balance)->toBe('1500.00')
            ->and($result['transactions'][1]->running_balance)->toBe('1300.00')
            ->and($result['closing_balance'])->toBe('1300.00');
    });

    it('calculates running balance correctly for credit normal account', function () {
        $account = ChartOfAccount::factory()->liability()->create([
            'normal_balance' => NormalBalance::Credit,
        ]);

        // Opening balance before period: 2000
        $journal0 = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-31',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal0)->forAccount($account)->credit()->create([
            'amount' => 2000.00,
        ]);

        // Transaction 1: Credit 800 -> balance 2800
        $journal1 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal1)->forAccount($account)->credit()->create([
            'amount' => 800.00,
        ]);

        // Transaction 2: Debit 300 -> balance 2500
        $journal2 = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-15',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal2)->forAccount($account)->debit()->create([
            'amount' => 300.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['opening_balance'])->toBe('2000.00')
            ->and($result['transactions'])->toHaveCount(2)
            ->and($result['transactions'][0]->running_balance)->toBe('2800.00')
            ->and($result['transactions'][1]->running_balance)->toBe('2500.00')
            ->and($result['closing_balance'])->toBe('2500.00');
    });

    it('returns correct transaction structure with debit and credit columns', function () {
        $account = ChartOfAccount::factory()->asset()->create();

        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'reference_number' => 'TRX-TEST-001',
            'description' => 'Test journal description',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($account)->debit()->create([
            'amount' => 1500.50,
            'description' => 'Test line description',
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        $transaction = $result['transactions']->first();
        expect($transaction)
            ->toHaveProperty('id')
            ->toHaveProperty('journal_entry_id', $journal->id)
            ->toHaveProperty('transaction_date')
            ->toHaveProperty('reference_number', 'TRX-TEST-001')
            ->toHaveProperty('description', 'Test line description')
            ->toHaveProperty('debit', '1500.50')
            ->toHaveProperty('credit', null)
            ->toHaveProperty('running_balance', '1500.50');
    });

    it('uses journal description when line description is null', function () {
        $account = ChartOfAccount::factory()->asset()->create();

        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2025-01-10',
            'description' => 'Journal level description',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($account)->debit()->create([
            'amount' => 100.00,
            'description' => null,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['transactions']->first()->description)->toBe('Journal level description');
    });

    it('returns closing balance equal to opening balance when no transactions in period', function () {
        $account = ChartOfAccount::factory()->asset()->create([
            'normal_balance' => NormalBalance::Debit,
        ]);

        // Only transaction before the period
        $journal = JournalEntry::factory()->create([
            'transaction_date' => '2024-12-31',
            'status' => JournalStatus::Posted,
        ]);
        JournalEntryLine::factory()->forJournal($journal)->forAccount($account)->debit()->create([
            'amount' => 5000.00,
        ]);

        $result = $this->service->getLedger($account->id, '2025-01-01', '2025-01-31');

        expect($result['opening_balance'])->toBe('5000.00')
            ->and($result['transactions'])->toHaveCount(0)
            ->and($result['closing_balance'])->toBe('5000.00');
    });

    it('throws exception for non-existent account', function () {
        $this->service->getLedger('non-existent-uuid', '2025-01-01', '2025-12-31');
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
