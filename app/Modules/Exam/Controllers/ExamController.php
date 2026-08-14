<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSectionPart;
use App\Models\ExamSectionQuestion;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Modules\Exam\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        private ExamService $examService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Exams/Index', [
            'exams' => $this->examService->paginated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Exams/Create', [
            'examTypes' => ExamType::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::with('skillParts')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = DB::transaction(function () use ($request) {
            $exam = $this->examService->create($request->validated());

            // Auto-create sections dari skills exam_type + auto-fill approved questions
            $skills = Skill::where('exam_type_id', $exam->exam_type_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            foreach ($skills as $index => $skill) {
                $section = ExamSection::create([
                    'exam_id' => $exam->id,
                    'skill_id' => $skill->id,
                    'title' => $skill->name,
                    'order' => $index + 1,
                ]);

                // Populate urutan part dari skill_parts (unik per skill/section)
                foreach (SkillPart::where('skill_id', $skill->id)->where('is_active', true)->get() as $part) {
                    ExamSectionPart::create([
                        'exam_section_id' => $section->id,
                        'skill_part_id' => $part->id,
                        'order' => $part->order,
                    ]);
                }

                $number = 1;
                $approved = Question::where('skill_id', $skill->id)
                    ->where('status', 'approved')
                    ->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $exam->exam_type_id))
                    ->orderBy('id')
                    ->get();

                foreach ($approved as $question) {
                    ExamSectionQuestion::create([
                        'exam_section_id' => $section->id,
                        'question_id' => $question->id,
                        'order' => $number++,
                    ]);
                }
            }

            return $exam;
        });

        return to_route('admin.exams.show', $exam)
            ->with('success', "Ujian berhasil dibuat. {$exam->sections->count()} section otomatis dibuat.");
    }

    public function show(Exam $exam): Response
    {
        $exam->load('sections');

        $sectionIds = $exam->sections->pluck('id');

        return Inertia::render('Admin/Exams/Show', [
            'exam' => $exam,
            'skills' => Skill::where('exam_type_id', $exam->exam_type_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'parts' => SkillPart::with('skill')->where('is_active', true)->orderBy('skill_id')->orderBy('order')->get(),
            'sectionQuestions' => ExamSectionQuestion::with(['question.passage', 'question.skill', 'question.skillPart'])
                ->whereIn('exam_section_id', $sectionIds)
                ->get()
                ->groupBy('exam_section_id'),
            'sectionParts' => ExamSectionPart::whereIn('exam_section_id', $sectionIds)
                ->get()
                ->groupBy('exam_section_id'),
        ]);
    }

    public function edit(Exam $exam): Response
    {
        return Inertia::render('Admin/Exams/Edit', [
            'exam' => $this->examService->findWithRelations($exam->id),
            'examTypes' => ExamType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->examService->update($exam, $request->validated());

        return back()->with('success', 'Ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->examService->delete($exam);

        return to_route('admin.exams.index')
            ->with('success', 'Ujian dihapus.');
    }
}
