<?php

namespace Database\Seeders\Finance;

use App\Enums\Finance\EntryPosition;
use App\Enums\Finance\TransactionCategory;
use App\Models\Finance\TransactionEntryConfig;
use App\Models\Finance\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. TUITION_BILLING
        $tuitionBilling = TransactionType::query()->updateOrCreate(
            ['code' => 'TUITION_BILLING'],
            [
                'is_system' => true,
                'name' => 'Tagihan SPP Bulanan',
                'category' => TransactionCategory::Billing,
                'is_active' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $tuitionBilling->id, 'config_key' => 'receivable_debit'],
            [
                'ui_label' => 'Akun Piutang Siswa',
                'position' => EntryPosition::Debit,
                'account_type_filter' => 'ASSET',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $tuitionBilling->id, 'config_key' => 'revenue_credit'],
            [
                'ui_label' => 'Akun Pendapatan SPP',
                'position' => EntryPosition::Credit,
                'account_type_filter' => 'REVENUE',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        // 2. TUITION_PAYMENT
        $tuitionPayment = TransactionType::query()->updateOrCreate(
            ['code' => 'TUITION_PAYMENT'],
            [
                'is_system' => true,
                'name' => 'Pembayaran SPP',
                'category' => TransactionCategory::Income,
                'is_active' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $tuitionPayment->id, 'config_key' => 'receivable_credit'],
            [
                'ui_label' => 'Akun Piutang Siswa',
                'position' => EntryPosition::Credit,
                'account_type_filter' => 'ASSET',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        // 3. SALARY_PAYROLL
        $salaryPayroll = TransactionType::query()->updateOrCreate(
            ['code' => 'SALARY_PAYROLL'],
            [
                'is_system' => true,
                'name' => 'Penggajian Guru',
                'category' => TransactionCategory::Payroll,
                'is_active' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $salaryPayroll->id, 'config_key' => 'salary_expense_debit'],
            [
                'ui_label' => 'Beban Gaji Guru',
                'position' => EntryPosition::Debit,
                'account_type_filter' => 'EXPENSE',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $salaryPayroll->id, 'config_key' => 'tax_payable_credit'],
            [
                'ui_label' => 'Utang PPh 21',
                'position' => EntryPosition::Credit,
                'account_type_filter' => 'LIABILITY',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        // 4. LATE_FINE
        $lateFine = TransactionType::query()->updateOrCreate(
            ['code' => 'LATE_FINE'],
            [
                'is_system' => true,
                'name' => 'Denda Keterlambatan',
                'category' => TransactionCategory::Income,
                'is_active' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $lateFine->id, 'config_key' => 'fine_revenue_credit'],
            [
                'ui_label' => 'Pendapatan Denda',
                'position' => EntryPosition::Credit,
                'account_type_filter' => 'REVENUE',
                'account_id' => null,
                'is_required' => true,
            ]
        );

        TransactionEntryConfig::query()->updateOrCreate(
            ['transaction_type_id' => $lateFine->id, 'config_key' => 'receivable_debit'],
            [
                'ui_label' => 'Piutang Denda',
                'position' => EntryPosition::Debit,
                'account_type_filter' => 'ASSET',
                'account_id' => null,
                'is_required' => true,
            ]
        );
    }
}
