<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreDepartmentRequest;
use App\Http\Requests\MasterData\StoreExamTypeRequest;
use App\Http\Requests\MasterData\StoreFacultyRequest;
use App\Http\Requests\MasterData\StoreSkillPartRequest;
use App\Http\Requests\MasterData\UpdateDepartmentRequest;
use App\Http\Requests\MasterData\UpdateExamTypeRequest;
use App\Http\Requests\MasterData\UpdateFacultyRequest;
use App\Http\Requests\MasterData\UpdateSkillPartRequest;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\SkillPart;
use App\Modules\MasterData\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function __construct(
        private MasterDataService $masterData,
    ) {}

    // ===== Exam Types =====
    public function examTypesIndex(): Response
    {
        return Inertia::render('Admin/MasterData/ExamTypes', $this->masterData->examTypesIndexData());
    }

    public function examTypeStore(StoreExamTypeRequest $request): RedirectResponse
    {
        $this->masterData->createExamType($request->validated());

        return back()->with('success', 'Jenis tes berhasil ditambahkan.');
    }

    public function examTypeUpdate(UpdateExamTypeRequest $request, ExamType $examType): RedirectResponse
    {
        $this->masterData->updateExamType($examType, $request->validated());

        return back()->with('success', 'Jenis tes berhasil diperbarui.');
    }

    public function examTypeDestroy(ExamType $examType): RedirectResponse
    {
        $this->masterData->deleteExamType($examType);

        return back()->with('success', 'Jenis tes berhasil dihapus.');
    }

    // ===== Skill Parts =====
    public function partsIndex(Request $request): Response
    {
        $bankId = $request->integer('bank_id') ?: null;

        return Inertia::render('Admin/MasterData/Parts', $this->masterData->skillPartsIndexData($bankId) + [
            'skillOptions' => SkillCode::options(),
        ]);
    }

    public function partStore(StoreSkillPartRequest $request): RedirectResponse
    {
        $this->masterData->createSkillPart($request->validated());

        return back()->with('success', 'Part berhasil ditambahkan.');
    }

    public function partUpdate(UpdateSkillPartRequest $request, SkillPart $skillPart): RedirectResponse
    {
        $this->masterData->updateSkillPart($skillPart, $request->validated());

        return back()->with('success', 'Part berhasil diperbarui.');
    }

    public function partDestroy(SkillPart $skillPart): RedirectResponse
    {
        $this->masterData->deleteSkillPart($skillPart);

        return back()->with('success', 'Part berhasil dihapus.');
    }

    // ===== Faculties =====
    public function facultiesIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Faculties', $this->masterData->facultiesIndexData());
    }

    public function facultyStore(StoreFacultyRequest $request): RedirectResponse
    {
        $this->masterData->createFaculty($request->validated());

        return back()->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function facultyUpdate(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $this->masterData->updateFaculty($faculty, $request->validated());

        return back()->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function facultyDestroy(Faculty $faculty): RedirectResponse
    {
        $this->masterData->deleteFaculty($faculty);

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }

    // ===== Departments =====
    public function departmentsIndex(): Response
    {
        return Inertia::render('Admin/MasterData/Departments', $this->masterData->departmentsIndexData());
    }

    public function departmentStore(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->masterData->createDepartment($request->validated());

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function departmentUpdate(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->masterData->updateDepartment($department, $request->validated());

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function departmentDestroy(Department $department): RedirectResponse
    {
        $this->masterData->deleteDepartment($department);

        return back()->with('success', 'Program studi berhasil dihapus.');
    }
}
