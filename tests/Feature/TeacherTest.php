<?php

use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
});

test('teachers index page is displayed', function () {
    $actingUser = User::factory()->create();
    Teacher::factory(3)->create();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('teachers.index'));

    $response->assertOk();
});

test('teacher can be created', function () {
    $actingUser = User::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.create'))
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-00001',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teachers.index'));

    $this->assertDatabaseHas('teachers', [
        'email' => 'jane@example.com',
        'teacher_number' => 'TCH-00001',
    ]);
});

test('teacher creation validates unique email', function () {
    $actingUser = User::factory()->create();
    $existingTeacher = Teacher::factory()->create(['email' => 'jane@example.com']);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.create'))
        ->post(route('teachers.store'), [
            'teacher_number' => 'TCH-00002',
            'name' => 'Jane Duplicate',
            'email' => $existingTeacher->email,
            'phone' => '+1234567890',
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('teachers.create'));

    $this->assertDatabaseCount('teachers', 1);
});

test('teacher creation validates unique teacher number', function () {
    $actingUser = User::factory()->create();
    $existingTeacher = Teacher::factory()->create(['teacher_number' => 'TCH-00001']);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.create'))
        ->post(route('teachers.store'), [
            'teacher_number' => $existingTeacher->teacher_number,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
        ]);

    $response
        ->assertSessionHasErrors('teacher_number')
        ->assertRedirect(route('teachers.create'));

    $this->assertDatabaseCount('teachers', 1);
});

test('teacher can be updated', function () {
    $actingUser = User::factory()->create();
    $teacher = Teacher::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.edit', $teacher))
        ->put(route('teachers.update', $teacher), [
            'teacher_number' => 'TCH-00099',
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+9876543210',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teachers.edit', $teacher));

    $teacher->refresh();

    expect($teacher->name)->toBe('Updated Name')
        ->and($teacher->email)->toBe('updated@example.com')
        ->and($teacher->teacher_number)->toBe('TCH-00099')
        ->and($teacher->phone)->toBe('+9876543210');
});

test('teacher can be deleted', function () {
    $actingUser = User::factory()->create();
    $teacher = Teacher::factory()->create();

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.index'))
        ->delete(route('teachers.destroy', $teacher));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teachers.index'));

    $this->assertSoftDeleted('teachers', [
        'id' => $teacher->id,
    ]);
});

test('soft deleted teachers do not appear in index', function () {
    $actingUser = User::factory()->create();
    $activeTeacher = Teacher::factory()->create();
    $deletedTeacher = Teacher::factory()->create();
    $deletedTeacher->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('teachers.index'));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers.data', 1)
    );

    $teacherIds = collect($response->viewData('page')['props']['teachers']['data'])->pluck('id')->toArray();
    expect($teacherIds)->toContain($activeTeacher->id)
        ->not->toContain($deletedTeacher->id);
});

test('teachers index can filter to show only deleted teachers', function () {
    $actingUser = User::factory()->create();
    $activeTeacher = Teacher::factory()->create();
    $deletedTeacher = Teacher::factory()->create();
    $deletedTeacher->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('teachers.index', ['with_trashed' => 'only']));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers.data', 1)
    );

    $teacherIds = collect($response->viewData('page')['props']['teachers']['data'])->pluck('id')->toArray();
    expect($teacherIds)->toContain($deletedTeacher->id)
        ->not->toContain($activeTeacher->id);
});

test('teachers index can filter to show all teachers including deleted', function () {
    $actingUser = User::factory()->create();
    $activeTeacher = Teacher::factory()->create();
    $deletedTeacher = Teacher::factory()->create();
    $deletedTeacher->delete();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('teachers.index', ['with_trashed' => 'all']));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers.data', 2)
    );

    $teacherIds = collect($response->viewData('page')['props']['teachers']['data'])->pluck('id')->toArray();
    expect($teacherIds)->toContain($activeTeacher->id)
        ->toContain($deletedTeacher->id);
});

test('teacher can be restored', function () {
    $actingUser = User::factory()->create();
    $teacher = Teacher::factory()->create();
    $teacher->delete();

    $this->assertSoftDeleted('teachers', [
        'id' => $teacher->id,
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.index'))
        ->post(route('teachers.restore', $teacher));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teachers.index'));

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'deleted_at' => null,
    ]);
});

test('restored teacher appears in active teachers list', function () {
    $actingUser = User::factory()->create();
    $teacher = Teacher::factory()->create();
    $teacher->delete();
    $teacher->restore();

    $response = $this
        ->actingAs($actingUser)
        ->get(route('teachers.index'));

    $response->assertOk();

    $teacherIds = collect($response->viewData('page')['props']['teachers']['data'])->pluck('id')->toArray();
    expect($teacherIds)->toContain($teacher->id);
});

test('teacher can be permanently deleted', function () {
    $actingUser = User::factory()->create();
    $teacher = Teacher::factory()->create();
    $teacher->delete();

    $this->assertSoftDeleted('teachers', [
        'id' => $teacher->id,
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->from(route('teachers.index'))
        ->delete(route('teachers.force-delete', $teacher));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teachers.index'));

    $this->assertDatabaseMissing('teachers', [
        'id' => $teacher->id,
    ]);
});
