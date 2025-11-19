<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\TransactionCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TransactionEntryConfigUpdateRequest;
use App\Http\Requests\Finance\TransactionTypeStoreRequest;
use App\Http\Requests\Finance\TransactionTypeUpdateRequest;
use App\Http\Resources\Finance\TransactionTypeResource;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\TransactionEntryConfig;
use App\Models\Finance\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
            ->with(['configs.account:id,code,name,account_type'])
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
        return Inertia::render('Finance/TransactionTypes/Create', [
            'transactionCategories' => TransactionCategory::values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionTypeStoreRequest $request): RedirectResponse
    {
        $transactionType = TransactionType::create([
            ...$request->validated(),
            'is_system' => false,
            'is_active' => $request->validated('is_active', true),
        ]);

        return to_route('finance.transaction-types.index')
            ->with('success', 'Transaction type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionType $transactionType): Response
    {
        $transactionType->load(['configs.account:id,code,name,account_type']);

        return Inertia::render('Finance/TransactionTypes/Show', [
            'transactionType' => TransactionTypeResource::make($transactionType)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionType $transactionType): Response
    {
        if ($transactionType->is_system) {
            return to_route('finance.transaction-types.show', $transactionType)
                ->with('error', 'System transaction types cannot be edited.');
        }

        return Inertia::render('Finance/TransactionTypes/Edit', [
            'transactionType' => TransactionTypeResource::make($transactionType)->resolve(),
            'transactionCategories' => TransactionCategory::values(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionTypeUpdateRequest $request, TransactionType $transactionType): RedirectResponse
    {
        if ($transactionType->is_system) {
            return to_route('finance.transaction-types.show', $transactionType)
                ->with('error', 'System transaction types cannot be updated.');
        }

        $transactionType->update($request->validated());

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
     * Update a specific entry config's account mapping.
     */
    public function updateConfig(
        TransactionEntryConfigUpdateRequest $request,
        TransactionType $transactionType,
        TransactionEntryConfig $config
    ): RedirectResponse {
        if ($config->transaction_type_id !== $transactionType->id) {
            abort(404);
        }

        $config->update($request->validated());

        return back()->with('success', 'Account mapping updated successfully.');
    }
}
