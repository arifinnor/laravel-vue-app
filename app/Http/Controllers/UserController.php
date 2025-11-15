<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Requests\Users\UserStoreRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(UserIndexRequest $request): Response
    {
        $validated = $request->validated();

        $users = User::query()
            ->when($validated['with_trashed'] === 'only', fn ($query) => $query->onlyTrashed())
            ->when($validated['with_trashed'] === 'all', fn ($query) => $query->withTrashed())
            ->when($validated['search'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->cursorPaginate($validated['per_page'])
            ->withQueryString()
            ->through(fn (User $user) => UserResource::make($user)->resolve());

        return Inertia::render('Users/Index', [
            'users' => $users,
            'perPageOptions' => config('pagination.per_page_options'),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return to_route('users.index')->with('success', 'User created.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        return Inertia::render('Users/Show', [
            'user' => UserResource::make($user)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => UserResource::make($user)->resolve(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return to_route('users.edit', $user)->with('success', 'User updated.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return to_route('users.index')->with('success', 'User deleted.');
    }

    /**
     * Restore the specified soft-deleted user.
     */
    public function restore(string $user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);
        $user->restore();

        return to_route('users.index')->with('success', 'User restored.');
    }

    /**
     * Permanently delete the specified user.
     */
    public function forceDelete(string $user): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($user);
        $user->forceDelete();

        return to_route('users.index')->with('success', 'User permanently deleted.');
    }
}
