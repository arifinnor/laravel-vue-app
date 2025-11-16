<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teachers\TeacherIndexRequest;
use App\Http\Requests\Teachers\TeacherStoreRequest;
use App\Http\Requests\Teachers\TeacherUpdateRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display a listing of the teachers.
     */
    public function index(TeacherIndexRequest $request): Response
    {
        $validated = $request->validated();

        $teachers = Teacher::query()
            ->when($validated['with_trashed'] === 'only', fn ($query) => $query->onlyTrashed())
            ->when($validated['with_trashed'] === 'all', fn ($query) => $query->withTrashed())
            ->when($validated['search'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('teacher_number', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->cursorPaginate($validated['per_page'])
            ->withQueryString()
            ->through(fn (Teacher $teacher) => TeacherResource::make($teacher)->resolve());

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
            'perPageOptions' => config('pagination.per_page_options'),
        ]);
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create(): Response
    {
        return Inertia::render('Teachers/Create');
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(TeacherStoreRequest $request): RedirectResponse
    {
        Teacher::create($request->validated());

        return to_route('teachers.index')->with('success', 'Teacher created.');
    }

    /**
     * Display the specified teacher.
     */
    public function show(Teacher $teacher): Response
    {
        return Inertia::render('Teachers/Show', [
            'teacher' => TeacherResource::make($teacher)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(Teacher $teacher): Response
    {
        return Inertia::render('Teachers/Edit', [
            'teacher' => TeacherResource::make($teacher)->resolve(),
        ]);
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(TeacherUpdateRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update($request->validated());

        return to_route('teachers.edit', $teacher)->with('success', 'Teacher updated.');
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return to_route('teachers.index')->with('success', 'Teacher deleted.');
    }

    /**
     * Restore the specified soft-deleted teacher.
     */
    public function restore(string $teacher): RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($teacher);
        $teacher->restore();

        return to_route('teachers.index')->with('success', 'Teacher restored.');
    }

    /**
     * Permanently delete the specified teacher.
     */
    public function forceDelete(string $teacher): RedirectResponse
    {
        $teacher = Teacher::withTrashed()->findOrFail($teacher);
        $teacher->forceDelete();

        return to_route('teachers.index')->with('success', 'Teacher permanently deleted.');
    }
}
