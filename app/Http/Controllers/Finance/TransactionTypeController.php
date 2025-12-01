<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\TransactionCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TransactionAccountUpdateRequest;
use App\Http\Requests\Finance\TransactionTypeStoreRequest;
use App\Http\Requests\Finance\TransactionTypeUpdateRequest;
use App\Http\Resources\Finance\TransactionTypeResource;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\TransactionAccount;
use App\Models\Finance\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $transactionTypes = TransactionType::query()
            ->with(['accounts.chartOfAccount:id,code,name,account_type'])
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $grouped = $transactionTypes->groupBy('category');

        $chartOfAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type->value,
                'display' => "{$account->code} - {$account->name}",
            ]);

        return Inertia::render('Finance/TransactionTypes/Index', [
            'transactionTypes' => TransactionTypeResource::collection($transactionTypes)->resolve(),
            'groupedTransactionTypes' => $grouped->map(fn (Collection $types) => TransactionTypeResource::collection($types)->resolve())->toArray(),
            'chartOfAccounts' => $chartOfAccounts,
            'categories' => TransactionCategory::values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $chartOfAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type->value,
                'display' => "{$account->code} - {$account->name}",
            ]);

        return Inertia::render('Finance/TransactionTypes/Create', [
            'transactionCategories' => TransactionCategory::values(),
            'chartOfAccounts' => $chartOfAccounts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionTypeStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $accounts = $validated['accounts'] ?? [];
        unset($validated['accounts']);

        $transactionType = DB::transaction(function () use ($validated, $accounts) {
            $transactionType = TransactionType::create([
                ...$validated,
                'is_system' => false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($accounts as $accountData) {
                $chartOfAccount = null;
                if (! empty($accountData['chart_of_account_id'])) {
                    $chartOfAccount = ChartOfAccount::find($accountData['chart_of_account_id']);
                }

                TransactionAccount::create([
                    'transaction_type_id' => $transactionType->id,
                    'role' => $accountData['role'],
                    'label' => $accountData['label'],
                    'direction' => $accountData['direction'],
                    'account_type' => $chartOfAccount?->account_type?->value ?? $accountData['account_type'],
                    'chart_of_account_id' => $accountData['chart_of_account_id'] ?? null,
                ]);
            }

            return $transactionType;
        });

        return to_route('finance.transaction-types.index')
            ->with('success', 'Transaction type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionType $transactionType): Response
    {
        $transactionType->load(['accounts.chartOfAccount:id,code,name,account_type']);

        return Inertia::render('Finance/TransactionTypes/Show', [
            'transactionType' => TransactionTypeResource::make($transactionType)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionType $transactionType): Response
    {
        $transactionType->load(['accounts.chartOfAccount:id,code,name,account_type']);

        $chartOfAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_posting', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type->value,
                'display' => "{$account->code} - {$account->name}",
            ]);

        return Inertia::render('Finance/TransactionTypes/Edit', [
            'transactionType' => TransactionTypeResource::make($transactionType)->resolve(),
            'transactionCategories' => TransactionCategory::values(),
            'chartOfAccounts' => $chartOfAccounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionTypeUpdateRequest $request, TransactionType $transactionType): RedirectResponse
    {
        $validated = $request->validated();
        $accounts = $validated['accounts'] ?? null;
        unset($validated['accounts']);

        DB::transaction(function () use ($validated, $accounts, $transactionType) {
            // For system types, only allow updating accounts COA mapping
            if (! $transactionType->is_system && ! empty($validated)) {
                $transactionType->update($validated);
            }

            if ($accounts !== null) {
                if ($transactionType->is_system) {
                    // System types: ONLY update chart_of_account_id, ignore other changes
                    // Do NOT delete or add rows
                    foreach ($accounts as $accountData) {
                        $existingAccount = $transactionType->accounts()
                            ->where(function ($query) use ($accountData) {
                                if (! empty($accountData['id'])) {
                                    $query->where('id', $accountData['id']);
                                } else {
                                    $query->where('role', $accountData['role']);
                                }
                            })
                            ->first();

                        if ($existingAccount) {
                            $chartOfAccount = null;
                            if (! empty($accountData['chart_of_account_id'])) {
                                $chartOfAccount = ChartOfAccount::find($accountData['chart_of_account_id']);
                            }

                            $existingAccount->update([
                                'chart_of_account_id' => $accountData['chart_of_account_id'] ?? null,
                                'account_type' => $chartOfAccount?->account_type?->value ?? $existingAccount->account_type,
                            ]);
                        }
                    }
                } else {
                    // Custom types: Full sync (update, create, delete)
                    $existingIds = $transactionType->accounts()->pluck('id')->toArray();
                    $inputIds = array_filter(array_column($accounts, 'id'));

                    foreach ($accounts as $accountData) {
                        $chartOfAccount = null;
                        if (! empty($accountData['chart_of_account_id'])) {
                            $chartOfAccount = ChartOfAccount::find($accountData['chart_of_account_id']);
                        }

                        $accountType = $chartOfAccount?->account_type?->value ?? $accountData['account_type'] ?? null;

                        if (! empty($accountData['id'])) {
                            // Update existing account
                            $transactionType->accounts()
                                ->where('id', $accountData['id'])
                                ->update([
                                    'role' => $accountData['role'],
                                    'label' => $accountData['label'],
                                    'direction' => $accountData['direction'],
                                    'account_type' => $accountType,
                                    'chart_of_account_id' => $accountData['chart_of_account_id'] ?? null,
                                ]);
                        } else {
                            // Create new account
                            TransactionAccount::create([
                                'transaction_type_id' => $transactionType->id,
                                'role' => $accountData['role'],
                                'label' => $accountData['label'],
                                'direction' => $accountData['direction'],
                                'account_type' => $accountType,
                                'chart_of_account_id' => $accountData['chart_of_account_id'] ?? null,
                            ]);
                        }
                    }

                    // Delete accounts that are in DB but not in request
                    $idsToDelete = array_diff($existingIds, $inputIds);
                    if (! empty($idsToDelete)) {
                        $transactionType->accounts()->whereIn('id', $idsToDelete)->delete();
                    }
                }
            }
        });

        return to_route('finance.transaction-types.show', $transactionType)
            ->with('success', 'Transaction type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionType $transactionType): RedirectResponse
    {
        if ($transactionType->is_system) {
            return to_route('finance.transaction-types.index')
                ->with('error', 'System transaction types cannot be deleted.');
        }

        $transactionType->delete();

        return to_route('finance.transaction-types.index')
            ->with('success', 'Transaction type deleted successfully.');
    }

    /**
     * Update a specific account mapping.
     */
    public function updateAccount(
        TransactionAccountUpdateRequest $request,
        TransactionType $transactionType,
        TransactionAccount $account
    ): RedirectResponse {
        if ($account->transaction_type_id !== $transactionType->id) {
            abort(404);
        }

        $account->update($request->validated());

        return back()->with('success', 'Account mapping updated successfully.');
    }
}
