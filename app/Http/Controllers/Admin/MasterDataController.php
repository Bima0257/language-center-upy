<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillCode;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\SkillPart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class MasterDataController extends Controller
{
    // ===== Exam Types =====
    public function examTypesIndex(): Response
    {
        return Inertia::render('Admin/MasterData/ExamTypes', [
            'examTypes' => ExamType::withCount('questionBanks')->orderBy('name')->get(),
        ]);
    }

    public function examTypeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:exam_types,name'],
            'max_strikes' => ['required', 'integer', 'min:0', 'max:10'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        ExamType::create($validated);

        return back()->with('success', 'Jenis tes berhasil ditambahkan.');
    }

    public function examTypeUpdate(Request $request, ExamType $examType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:exam_types,name,'.$examType->id],
            'max_strikes' => ['required', 'integer', 'min:0', 'max:10'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        $examType->update($validated);

        return back()->with('success', 'Jenis tes berhasil diperbarui.');
    }

    public function examTypeDestroy(ExamType $examType): RedirectResponse
    {
        $examType->delete();

        return back()->with('success', 'Jenis tes berhasil dihapus.');
    }

    // ===== Skill Parts =====
    public function partsIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Parts', [
            'parts' => SkillPart::withCount('questions')->orderBy('skill')->orderBy('order')->get(),
            'skillOptions' => SkillCode::options(),
        ]);
    }

    public function partStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['required', 'integer', 'min:1'],
            'directions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['directions'] = $validated['directions'] ?? null;
        if ($validated['directions']) {
            $validated['directions'] = Purifier::clean($validated['directions']);
        }

        SkillPart::create($validated);

        return back()->with('success', 'Part berhasil ditambahkan.');
    }

    public function partUpdate(Request $request, SkillPart $skillPart): RedirectResponse
    {
        $validated = $request->validate([
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['required', 'integer', 'min:1'],
            'directions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['directions'] = $validated['directions'] ?? null;
        if ($validated['directions']) {
            $validated['directions'] = Purifier::clean($validated['directions']);
        }

        $skillPart->update($validated);

        return back()->with('success', 'Part berhasil diperbarui.');
    }

    public function partDestroy(SkillPart $skillPart): RedirectResponse
    {
        $skillPart->delete();

        return back()->with('success', 'Part berhasil dihapus.');
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
