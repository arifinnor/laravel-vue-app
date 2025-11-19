<?php

namespace Database\Seeders\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\NormalBalance;
use App\Enums\Finance\ReportType;
use App\Models\Finance\AccountCategory;
use App\Models\Finance\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create Account Categories
        $categories = $this->createAccountCategories();

        // Step 2: Create Chart of Accounts with hierarchy
        $this->createChartOfAccounts($categories);
    }

    /**
     * @return array<string, AccountCategory>
     */
    private function createAccountCategories(): array
    {
        $categoryDefinitions = [
            ['name' => 'Current Assets', 'report_type' => ReportType::BalanceSheet, 'sequence' => 100],
            ['name' => 'Fixed Assets', 'report_type' => ReportType::BalanceSheet, 'sequence' => 200],
            ['name' => 'Current Liabilities', 'report_type' => ReportType::BalanceSheet, 'sequence' => 300],
            ['name' => 'Long Term Liabilities', 'report_type' => ReportType::BalanceSheet, 'sequence' => 400],
            ['name' => 'Equity', 'report_type' => ReportType::BalanceSheet, 'sequence' => 500],
            ['name' => 'Operational Revenue', 'report_type' => ReportType::IncomeStatement, 'sequence' => 600],
            ['name' => 'Other Revenue', 'report_type' => ReportType::IncomeStatement, 'sequence' => 650],
            ['name' => 'Operational Expenses', 'report_type' => ReportType::IncomeStatement, 'sequence' => 700],
            ['name' => 'Admin Expenses', 'report_type' => ReportType::IncomeStatement, 'sequence' => 800],
        ];

        $categories = [];

        foreach ($categoryDefinitions as $definition) {
            $category = AccountCategory::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'report_type' => $definition['report_type']->value,
                    'sequence' => $definition['sequence'],
                ]
            );

            $categories[$definition['name']] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, AccountCategory>  $categories
     */
    private function createChartOfAccounts(array $categories): void
    {
        $accounts = [];

        // Define all accounts with their hierarchy
        $accountDefinitions = [
            // ASSETS - Level 1 Header
            [
                'code' => '1-0000',
                'name' => 'ASSETS',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => null,
            ],

            // Current Assets - Level 2 Header
            [
                'code' => '1-1000',
                'name' => 'Current Assets',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '1-0000',
            ],

            // Current Assets - Detail Accounts
            [
                'code' => '1-1101',
                'name' => 'Kas Tunai',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => true,
                'parent_code' => '1-1000',
            ],
            [
                'code' => '1-1102',
                'name' => 'Bank Operasional',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => true,
                'parent_code' => '1-1000',
            ],
            [
                'code' => '1-1103',
                'name' => 'Piutang Siswa',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '1-1000',
            ],
            [
                'code' => '1-1104',
                'name' => 'Perlengkapan/Inventory',
                'category' => 'Current Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '1-1000',
            ],

            // Fixed Assets - Level 2 Header
            [
                'code' => '1-2000',
                'name' => 'Fixed Assets',
                'category' => 'Fixed Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '1-0000',
            ],

            // Fixed Assets - Detail Accounts
            [
                'code' => '1-2101',
                'name' => 'Gedung Sekolah',
                'category' => 'Fixed Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '1-2000',
            ],
            [
                'code' => '1-2102',
                'name' => 'Peralatan Lab & Komputer',
                'category' => 'Fixed Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '1-2000',
            ],
            [
                'code' => '1-2103',
                'name' => 'Akumulasi Penyusutan',
                'category' => 'Fixed Assets',
                'account_type' => AccountType::Asset,
                'normal_balance' => NormalBalance::Credit, // Contra account
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '1-2000',
            ],

            // LIABILITIES - Level 1 Header
            [
                'code' => '2-0000',
                'name' => 'LIABILITIES',
                'category' => 'Current Liabilities',
                'account_type' => AccountType::Liability,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => null,
            ],

            // Current Liabilities - Level 2 Header
            [
                'code' => '2-1000',
                'name' => 'Current Liabilities',
                'category' => 'Current Liabilities',
                'account_type' => AccountType::Liability,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '2-0000',
            ],

            // Current Liabilities - Detail Accounts
            [
                'code' => '2-1101',
                'name' => 'Utang Usaha',
                'category' => 'Current Liabilities',
                'account_type' => AccountType::Liability,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '2-1000',
            ],
            [
                'code' => '2-1102',
                'name' => 'Utang Gaji Guru',
                'category' => 'Current Liabilities',
                'account_type' => AccountType::Liability,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '2-1000',
            ],

            // REVENUE - Level 1 Header
            [
                'code' => '4-0000',
                'name' => 'REVENUE',
                'category' => 'Operational Revenue',
                'account_type' => AccountType::Revenue,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => null,
            ],

            // Operational Revenue - Level 2 Header
            [
                'code' => '4-1000',
                'name' => 'Operational Revenue',
                'category' => 'Operational Revenue',
                'account_type' => AccountType::Revenue,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '4-0000',
            ],

            // Operational Revenue - Detail Accounts
            [
                'code' => '4-1101',
                'name' => 'Pendapatan SPP',
                'category' => 'Operational Revenue',
                'account_type' => AccountType::Revenue,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '4-1000',
            ],
            [
                'code' => '4-1102',
                'name' => 'Pendapatan Uang Pangkal',
                'category' => 'Operational Revenue',
                'account_type' => AccountType::Revenue,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '4-1000',
            ],
            [
                'code' => '4-1103',
                'name' => 'Pendapatan Denda',
                'category' => 'Operational Revenue',
                'account_type' => AccountType::Revenue,
                'normal_balance' => NormalBalance::Credit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '4-1000',
            ],

            // EXPENSES - Level 1 Header
            [
                'code' => '6-0000',
                'name' => 'EXPENSES',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => null,
            ],

            // Academic Expenses - Level 2 Header
            [
                'code' => '6-1000',
                'name' => 'Academic Expenses',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '6-0000',
            ],

            // Academic Expenses - Detail Accounts
            [
                'code' => '6-1101',
                'name' => 'Beban Gaji Guru',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '6-1000',
            ],
            [
                'code' => '6-1102',
                'name' => 'Beban Alat Tulis Sekolah',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '6-1000',
            ],

            // General Expenses - Level 2 Header
            [
                'code' => '6-2000',
                'name' => 'General Expenses',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => false,
                'is_cash' => false,
                'parent_code' => '6-0000',
            ],

            // General Expenses - Detail Accounts
            [
                'code' => '6-2101',
                'name' => 'Beban Listrik & Air',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '6-2000',
            ],
            [
                'code' => '6-2102',
                'name' => 'Beban Admin Bank',
                'category' => 'Operational Expenses',
                'account_type' => AccountType::Expense,
                'normal_balance' => NormalBalance::Debit,
                'is_posting' => true,
                'is_cash' => false,
                'parent_code' => '6-2000',
            ],
        ];

        // Create accounts in order, handling parent relationships
        foreach ($accountDefinitions as $definition) {
            $parentId = null;
            if ($definition['parent_code'] !== null) {
                $parent = $accounts[$definition['parent_code']] ?? ChartOfAccount::query()
                    ->where('code', $definition['parent_code'])
                    ->first();
                $parentId = $parent?->getKey();
            }

            $account = ChartOfAccount::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'category_id' => $categories[$definition['category']]->getKey(),
                    'name' => $definition['name'],
                    'description' => null,
                    'parent_id' => $parentId,
                    'account_type' => $definition['account_type']->value,
                    'normal_balance' => $definition['normal_balance']->value,
                    'is_posting' => $definition['is_posting'],
                    'is_cash' => $definition['is_cash'],
                    'is_active' => true,
                ]
            );

            $accounts[$definition['code']] = $account;
        }
    }
}
