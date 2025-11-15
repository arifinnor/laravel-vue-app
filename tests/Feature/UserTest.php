<?php

use App\Models\User;

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
});

test('users index page is displayed', function () {
    $actingUser = User::factory()->create();
    User::factory(3)->create();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('users.index'));

    $response->assertOk();
});

test('user can be created', function () {
    $actingUser = User::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.create'))
        ->post(route('users.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
    ]);
});

test('user creation validates unique email', function () {
    $actingUser = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.create'))
        ->post(route('users.store'), [
            'name' => 'Jane Duplicate',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('users.create'));

    $this->assertDatabaseCount('users', 2);
});

test('user can be updated without changing password', function () {
    $actingUser = User::factory()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.edit', $user))
        ->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.edit', $user));

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

test('user can be deleted', function () {
    $actingUser = User::factory()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.index'))
        ->delete(route('users.destroy', $user));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
});

test('soft deleted users do not appear in index', function () {
    $actingUser = User::factory()->create();
    $activeUser = User::factory()->create();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('users.index'));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users.data', 2)
    );

    $userIds = collect($response->viewData('page')['props']['users']['data'])->pluck('id')->toArray();
    expect($userIds)->toContain($actingUser->id)
        ->toContain($activeUser->id)
        ->not->toContain($deletedUser->id);
});

test('users index can filter to show only deleted users', function () {
    $actingUser = User::factory()->create();
    $activeUser = User::factory()->create();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('users.index', ['with_trashed' => 'only']));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users.data', 1)
    );

    $userIds = collect($response->viewData('page')['props']['users']['data'])->pluck('id')->toArray();
    expect($userIds)->toContain($deletedUser->id)
        ->not->toContain($actingUser->id)
        ->not->toContain($activeUser->id);
});

test('users index can filter to show all users including deleted', function () {
    $actingUser = User::factory()->create();
    $activeUser = User::factory()->create();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('users.index', ['with_trashed' => 'all']));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users.data', 3)
    );

    $userIds = collect($response->viewData('page')['props']['users']['data'])->pluck('id')->toArray();
    expect($userIds)->toContain($actingUser->id)
        ->toContain($activeUser->id)
        ->toContain($deletedUser->id);
});

test('user can be restored', function () {
    $actingUser = User::factory()->create();
    $user = User::factory()->create();
    $user->delete();

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.index'))
        ->post(route('users.restore', $user));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
});

test('restored user appears in active users list', function () {
    $actingUser = User::factory()->create();
    $user = User::factory()->create();
    $user->delete();
    $user->restore();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('users.index'));

    $response->assertOk();

    $userIds = collect($response->viewData('page')['props']['users']['data'])->pluck('id')->toArray();
    expect($userIds)->toContain($user->id);
});

test('user can be permanently deleted', function () {
    $actingUser = User::factory()->create();
    $user = User::factory()->create();
    $user->delete();

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('users.index'))
        ->delete(route('users.force-delete', $user));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
