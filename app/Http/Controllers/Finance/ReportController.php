<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BalanceSheetRequest;
use App\Http\Requests\Finance\GeneralLedgerRequest;
use App\Http\Requests\Finance\IncomeStatementRequest;
use App\Http\Requests\Finance\TrialBalanceRequest;
use App\Models\Finance\ChartOfAccount;
use App\Services\Reporting\BalanceSheetService;
use App\Services\Reporting\GeneralLedgerService;
use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\TrialBalanceService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display the reports index page.
     */
    public function index(): Response
    {
        return Inertia::render('Finance/Reports/Index');
    }

    /**
     * Display the General Ledger report.
     */
    public function generalLedger(GeneralLedgerRequest $request, GeneralLedgerService $service): Response
    {
        $validated = $request->validated();

        $ledgerData = $service->getLedger(
            $validated['account_id'],
            $validated['start_date'],
            $validated['end_date']
        );

        $accounts = ChartOfAccount::query()
            ->posting()
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Finance/Reports/GeneralLedger', [
            'account' => [
                'id' => $ledgerData['account']->id,
                'code' => $ledgerData['account']->code,
                'name' => $ledgerData['account']->name,
                'normal_balance' => $ledgerData['account']->normal_balance->value,
            ],
            'opening_balance' => $ledgerData['opening_balance'],
            'transactions' => $ledgerData['transactions'],
            'closing_balance' => $ledgerData['closing_balance'],
            'filters' => [
                'account_id' => $validated['account_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
            'accounts' => $accounts,
        ]);
    }

    /**
     * Display the General Ledger report index (filter form).
     */
    public function generalLedgerIndex(): Response
    {
        $accounts = ChartOfAccount::query()
            ->posting()
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Finance/Reports/GeneralLedger', [
            'accounts' => $accounts,
            'account' => null,
            'opening_balance' => null,
            'transactions' => [],
            'closing_balance' => null,
            'filters' => [
                'account_id' => null,
                'start_date' => null,
                'end_date' => null,
            ],
        ]);
    }

    /**
     * Display the Trial Balance report.
     */
    public function trialBalance(TrialBalanceRequest $request, TrialBalanceService $service): Response
    {
        $validated = $request->validated();

        $accounts = $service->getReport(
            $validated['start_date'],
            $validated['end_date']
        );

        // Calculate grand totals
        $totalDebitMutation = $accounts->sum(fn ($account) => (float) $account->debit_mutation);
        $totalCreditMutation = $accounts->sum(fn ($account) => (float) $account->credit_mutation);

        // Verify totals match (double-entry bookkeeping principle)
        $difference = abs($totalDebitMutation - $totalCreditMutation);
        if ($difference > 0.01) {
            Log::warning('Trial Balance totals mismatch', [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_debit' => $totalDebitMutation,
                'total_credit' => $totalCreditMutation,
                'difference' => $difference,
            ]);
        }

        return Inertia::render('Finance/Reports/TrialBalance', [
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'normal_balance' => $account->normal_balance,
                    'category_id' => $account->category_id,
                    'category_name' => $account->category_name,
                    'category_sequence' => $account->category_sequence,
                    'opening_balance' => $account->opening_balance,
                    'debit_mutation' => $account->debit_mutation,
                    'credit_mutation' => $account->credit_mutation,
                    'closing_balance' => $account->closing_balance,
                ];
            })->values(),
            'total_debit_mutation' => number_format($totalDebitMutation, 2, '.', ''),
            'total_credit_mutation' => number_format($totalCreditMutation, 2, '.', ''),
            'filters' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
        ]);
    }

    /**
     * Display the Trial Balance report index (filter form).
     */
    public function trialBalanceIndex(): Response
    {
        $dateRange = $this->getCurrentMonthRange();

        return Inertia::render('Finance/Reports/TrialBalance', [
            'accounts' => [],
            'total_debit_mutation' => '0.00',
            'total_credit_mutation' => '0.00',
            'filters' => [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
            ],
        ]);
    }

    /**
     * Display the Income Statement report index (filter form).
     */
    public function incomeStatementIndex(): Response
    {
        $dateRange = $this->getCurrentMonthRange();

        return Inertia::render('Finance/Reports/IncomeStatement', [
            'categories' => [],
            'total_revenue' => '0.00',
            'total_expense' => '0.00',
            'net_surplus' => '0.00',
            'filters' => [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
            ],
        ]);
    }

    /**
     * Display the Income Statement report.
     */
    public function incomeStatement(IncomeStatementRequest $request, IncomeStatementService $service): Response
    {
        $validated = $request->validated();

        $report = $service->getReport(
            $validated['start_date'],
            $validated['end_date']
        );

        return Inertia::render('Finance/Reports/IncomeStatement', [
            'categories' => $report['categories']->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'sequence' => $category->sequence,
                    'accounts' => $category->accounts->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->name,
                            'account_type' => $account->account_type,
                            'amount' => $account->amount,
                        ];
                    })->values(),
                    'total' => $category->total,
                ];
            })->values(),
            'total_revenue' => $report['total_revenue'],
            'total_expense' => $report['total_expense'],
            'net_surplus' => $report['net_surplus'],
            'filters' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
        ]);
    }

    /**
     * Display the Balance Sheet report index (filter form).
     */
    public function balanceSheetIndex(): Response
    {
        $asOfDate = date('Y-m-d');

        return Inertia::render('Finance/Reports/BalanceSheet', [
            'asset_categories' => [],
            'liability_categories' => [],
            'equity_categories' => [],
            'total_assets' => '0.00',
            'total_liabilities' => '0.00',
            'total_equity' => '0.00',
            'current_year_earnings' => '0.00',
            'total_liabilities_equity' => '0.00',
            'is_balanced' => true,
            'filters' => [
                'as_of_date' => $asOfDate,
            ],
        ]);
    }

    /**
     * Display the Balance Sheet report.
     */
    public function balanceSheet(BalanceSheetRequest $request, BalanceSheetService $service): Response
    {
        $validated = $request->validated();

        $report = $service->getReport($validated['as_of_date']);

        // Log if balance sheet is not balanced
        if (! $report['is_balanced']) {
            Log::warning('Balance Sheet is not balanced', [
                'as_of_date' => $validated['as_of_date'],
                'total_assets' => $report['total_assets'],
                'total_liabilities_equity' => $report['total_liabilities_equity'],
            ]);
        }

        return Inertia::render('Finance/Reports/BalanceSheet', [
            'asset_categories' => $report['asset_categories']->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'sequence' => $category->sequence,
                    'accounts' => $category->accounts->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->name,
                            'account_type' => $account->account_type,
                            'balance' => $account->balance,
                        ];
                    })->values(),
                    'total' => $category->total,
                ];
            })->values(),
            'liability_categories' => $report['liability_categories']->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'sequence' => $category->sequence,
                    'accounts' => $category->accounts->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->name,
                            'account_type' => $account->account_type,
                            'balance' => $account->balance,
                        ];
                    })->values(),
                    'total' => $category->total,
                ];
            })->values(),
            'equity_categories' => $report['equity_categories']->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'sequence' => $category->sequence,
                    'accounts' => $category->accounts->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->name,
                            'account_type' => $account->account_type,
                            'balance' => $account->balance,
                        ];
                    })->values(),
                    'total' => $category->total,
                ];
            })->values(),
            'total_assets' => $report['total_assets'],
            'total_liabilities' => $report['total_liabilities'],
            'total_equity' => $report['total_equity'],
            'current_year_earnings' => $report['current_year_earnings'],
            'total_liabilities_equity' => $report['total_liabilities_equity'],
            'is_balanced' => $report['is_balanced'],
            'filters' => [
                'as_of_date' => $validated['as_of_date'],
            ],
        ]);
    }

    /**
     * Get current month date range.
     *
     * @return array{start: string, end: string}
     */
    private function getCurrentMonthRange(): array
    {
        $now = new \DateTime;
        $start = new \DateTime($now->format('Y-m-01'));
        $end = new \DateTime($now->format('Y-m-t'));

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
    }
}
