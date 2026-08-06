<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class MasterDataController extends Controller
{
    // ===== Skills =====
    public function skillsIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Skills', [
            'skills' => Skill::withCount('questions')->orderBy('name')->get(),
        ]);
    }

    public function skillStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        Skill::create($validated);

        return back()->with('success', 'Skill berhasil ditambahkan.');
    }

    public function skillUpdate(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        $skill->update($validated);

        return back()->with('success', 'Skill berhasil diperbarui.');
    }

    public function skillDestroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return back()->with('success', 'Skill berhasil dihapus.');
    }

    // ===== Faculties =====
    public function facultiesIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Faculties', [
            'faculties' => Faculty::withCount('departments')->orderBy('name')->get(),
        ]);
    }

    public function facultyStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        Faculty::create($validated);

        return back()->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function facultyUpdate(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $faculty->update($validated);

        return back()->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function facultyDestroy(Faculty $faculty): RedirectResponse
    {
        $faculty->delete();

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }

    // ===== Departments =====
    public function departmentsIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Departments', [
            'departments' => Department::with('faculty')->orderBy('name')->get(),
            'faculties' => Faculty::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function departmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        Department::create($validated);

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function departmentUpdate(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $department->update($validated);

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function departmentDestroy(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with('success', 'Program studi berhasil dihapus.');
    }
}
