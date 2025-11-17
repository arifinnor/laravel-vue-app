<?php

namespace App\Http\Controllers;

use App\Http\Requests\Guardians\GuardianIndexRequest;
use App\Http\Requests\Guardians\GuardianSearchRequest;
use App\Http\Requests\Guardians\GuardianStoreRequest;
use App\Http\Requests\Guardians\GuardianUpdateRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    /**
     * Display a listing of the guardians.
     */
    public function index(GuardianIndexRequest $request): Response
    {
        $validated = $request->validated();

        $guardians = Guardian::query()
            ->when($validated['with_trashed'] === 'only', fn ($query) => $query->onlyTrashed())
            ->when($validated['with_trashed'] === 'all', fn ($query) => $query->withTrashed())
            ->when($validated['search'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('relationship', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->cursorPaginate($validated['per_page'])
            ->withQueryString()
            ->through(fn (Guardian $guardian) => GuardianResource::make($guardian)->resolve());

        return Inertia::render('Guardians/Index', [
            'guardians' => $guardians,
            'perPageOptions' => config('pagination.per_page_options'),
        ]);
    }

    /**
     * Show the form for creating a new guardian.
     */
    public function create(): Response
    {
        return Inertia::render('Guardians/Create');
    }

    /**
     * Store a newly created guardian in storage.
     */
    public function store(GuardianStoreRequest $request): RedirectResponse
    {
        Guardian::create($request->validated());

        return to_route('guardians.index')->with('success', 'Guardian created.');
    }

    /**
     * Display the specified guardian.
     */
    public function show(Guardian $guardian): Response
    {
        return Inertia::render('Guardians/Show', [
            'guardian' => GuardianResource::make($guardian)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified guardian.
     */
    public function edit(Guardian $guardian): Response
    {
        return Inertia::render('Guardians/Edit', [
            'guardian' => GuardianResource::make($guardian)->resolve(),
        ]);
    }

    /**
     * Update the specified guardian in storage.
     */
    public function update(GuardianUpdateRequest $request, Guardian $guardian): RedirectResponse
    {
        $guardian->update($request->validated());

        return to_route('guardians.edit', $guardian)->with('success', 'Guardian updated.');
    }

    /**
     * Remove the specified guardian from storage.
     */
    public function destroy(Guardian $guardian): RedirectResponse
    {
        $guardian->delete();

        return to_route('guardians.index')->with('success', 'Guardian deleted.');
    }

    /**
     * Restore the specified soft-deleted guardian.
     */
    public function restore(string $guardian): RedirectResponse
    {
        $guardian = Guardian::withTrashed()->findOrFail($guardian);
        $guardian->restore();

        return to_route('guardians.index')->with('success', 'Guardian restored.');
    }

    /**
     * Permanently delete the specified guardian.
     */
    public function forceDelete(string $guardian): RedirectResponse
    {
        $guardian = Guardian::withTrashed()->findOrFail($guardian);
        $guardian->forceDelete();

        return to_route('guardians.index')->with('success', 'Guardian permanently deleted.');
    }

    /**
     * Search guardians for autocomplete.
     */
    public function search(GuardianSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $guardians = Guardian::query()
            ->where('name', 'like', "%{$validated['search']}%")
            ->limit($validated['limit'])
            ->get()
            ->map(fn (Guardian $guardian) => [
                'id' => $guardian->id,
                'name' => $guardian->name,
                'email' => $guardian->email,
                'phone' => $guardian->phone,
            ]);

        return response()->json($guardians);
    }
}
