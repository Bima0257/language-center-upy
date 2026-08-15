<?php

namespace App\Modules\Exam\Controllers;

use App\Enums\SkillCode;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSectionPart;
use App\Models\ExamSectionQuestion;
use App\Models\Question;
use App\Models\SkillPart;
use App\Modules\Exam\Services\ExamSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamSectionController extends Controller
{
    public function __construct(
        private ExamSectionService $sectionService,
    ) {}

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'title' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer'],
            'total_questions' => ['nullable', 'integer'],
        ]);

        $attached = DB::transaction(function () use ($validated, $exam) {
            $section = $this->sectionService->create($exam, $validated);

            // Auto-fill: semua approved soal skill tsb dari bank kategori exam langsung terpasang
            $approvedQuestions = Question::where('skill', $validated['skill'])
                ->where('status', 'approved')
                ->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $exam->exam_type_id))
                ->orderBy('id')
                ->get();

            $number = 1;
            foreach ($approvedQuestions as $question) {
                ExamSectionQuestion::firstOrCreate(
                    ['exam_section_id' => $section->id, 'question_id' => $question->id],
                    ['order' => $number++],
                );
            }

            return $approvedQuestions->count();
        });

        return back()->with('success', "Section berhasil dibuat. {$attached} soal approved otomatis terpasang.");
    }

    public function update(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'skill' => [Rule::enum(SkillCode::class)],
            'title' => ['string', 'max:255'],
            'order' => ['integer'],
            'total_questions' => ['nullable', 'integer'],
        ]);

        $this->sectionService->update($section, $validated);

        return back()->with('success', 'Section diperbarui.');
    }

    public function destroy(Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->delete($section);

        return back()->with('success', 'Section dihapus.');
    }

    public function attachQuestions(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'distinct'],
        ]);

        $attached = DB::transaction(function () use ($validated, $exam, $section) {
            $allowed = Question::whereIn('id', $validated['question_ids'])
                ->where('status', 'approved')
                ->where('skill', $section->skill)
                ->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $exam->exam_type_id))
                ->pluck('id');

            $existing = ExamSectionQuestion::where('exam_section_id', $section->id)
                ->whereIn('question_id', $allowed)
                ->pluck('question_id');

            $nextOrder = (ExamSectionQuestion::where('exam_section_id', $section->id)->max('order') ?? 0) + 1;

            $attached = 0;
            foreach ($allowed as $questionId) {
                if ($existing->contains($questionId)) {
                    continue;
                }

                ExamSectionQuestion::create([
                    'exam_section_id' => $section->id,
                    'question_id' => $questionId,
                    'order' => $nextOrder++,
                ]);
                $attached++;
            }

            return $attached;
        });

        return back()->with('success', "{$attached} soal berhasil ditambahkan ke section.");
    }

    public function detachQuestion(Exam $exam, ExamSection $section, Question $question): RedirectResponse
    {
        ExamSectionQuestion::where('exam_section_id', $section->id)
            ->where('question_id', $question->id)
            ->delete();

        return back()->with('success', 'Soal dilepas dari section.');
    }

    public function saveArrangement(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        DB::transaction(function () use ($request, $section) {
            if ($request->filled('part_order')) {
                $validated = $request->validate([
                    'part_order' => ['required', 'array'],
                    'part_order.*.skill_part_id' => ['required', 'integer', 'distinct'],
                    'part_order.*.order' => ['required', 'integer', 'min:1'],
                ]);

                $skillPartIds = SkillPart::where('skill', $section->skill)->pluck('id');

                foreach ($validated['part_order'] as $part) {
                    if (! $skillPartIds->contains($part['skill_part_id'])) {
                        throw ValidationException::withMessages(['part_order' => 'Part tidak valid untuk section ini.']);
                    }

                    ExamSectionPart::updateOrCreate(
                        ['exam_section_id' => $section->id, 'skill_part_id' => $part['skill_part_id']],
                        ['order' => $part['order']],
                    );
                }

                $this->renumberSection($section);
            }

            if ($request->filled('question_orders')) {
                $validated = $request->validate([
                    'question_orders' => ['required', 'array'],
                    'question_orders.*.question_id' => ['required', 'integer', 'distinct'],
                    'question_orders.*.number' => ['required', 'integer', 'min:1'],
                ]);

                $numbers = array_column($validated['question_orders'], 'number');
                if (count($numbers) !== count(array_unique($numbers))) {
                    throw ValidationException::withMessages(['question_orders' => 'Nomor soal harus unik di seluruh section.']);
                }

                $sectionQuestionIds = ExamSectionQuestion::where('exam_section_id', $section->id)
                    ->pluck('question_id');

                foreach ($validated['question_orders'] as $order) {
                    if (! $sectionQuestionIds->contains($order['question_id'])) {
                        throw ValidationException::withMessages(['question_orders' => 'Soal tidak terpasang di section ini.']);
                    }
                }

                foreach ($validated['question_orders'] as $order) {
                    ExamSectionQuestion::where('exam_section_id', $section->id)
                        ->where('question_id', $order['question_id'])
                        ->update(['order' => $order['number']]);
                }
            }
        });

        return back()->with('success', 'Susunan section disimpan.');
    }

    public function reorderQuestions(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*.question_id' => ['required', 'integer', 'distinct'],
            'orders.*.number' => ['required', 'integer', 'min:1'],
        ]);

        $numbers = array_column($validated['orders'], 'number');
        if (count($numbers) !== count(array_unique($numbers))) {
            throw ValidationException::withMessages(['orders' => 'Nomor soal harus unik di seluruh section.']);
        }

        $sectionQuestionIds = ExamSectionQuestion::where('exam_section_id', $section->id)
            ->pluck('question_id');

        foreach ($validated['orders'] as $order) {
            if (! $sectionQuestionIds->contains($order['question_id'])) {
                throw ValidationException::withMessages(['orders' => 'Soal tidak terpasang di section ini.']);
            }
        }

        DB::transaction(function () use ($validated, $section) {
            foreach ($validated['orders'] as $order) {
                ExamSectionQuestion::where('exam_section_id', $section->id)
                    ->where('question_id', $order['question_id'])
                    ->update(['order' => $order['number']]);
            }
        });

        return back()->with('success', 'Nomor soal diperbarui.');
    }

    public function reorderParts(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.skill_part_id' => ['required', 'integer', 'distinct'],
            'order.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $skillPartIds = SkillPart::where('skill', $section->skill)->pluck('id');

        foreach ($validated['order'] as $part) {
            if (! $skillPartIds->contains($part['skill_part_id'])) {
                throw ValidationException::withMessages(['order' => 'Part tidak valid untuk section ini.']);
            }
        }

        DB::transaction(function () use ($validated, $section) {
            foreach ($validated['order'] as $part) {
                ExamSectionPart::updateOrCreate(
                    ['exam_section_id' => $section->id, 'skill_part_id' => $part['skill_part_id']],
                    ['order' => $part['order']],
                );
            }

            $this->renumberSection($section);
        });

        return back()->with('success', 'Urutan part diperbarui dan nomor soal disusun ulang.');
    }

    private function renumberSection(ExamSection $section): void
    {
        $sectionPartIds = ExamSectionPart::where('exam_section_id', $section->id)
            ->orderBy('order')
            ->pluck('skill_part_id')
            ->toArray();

        $defaultPartIds = SkillPart::where('skill', $section->skill)
            ->orderBy('order')
            ->pluck('id')
            ->toArray();

        $partSequence = array_values(array_unique(array_merge($sectionPartIds, $defaultPartIds)));

        $rows = ExamSectionQuestion::with('question')
            ->where('exam_section_id', $section->id)
            ->get();

        $rank = function ($row) use ($partSequence) {
            $partId = $row->question->skill_part_id;
            if (! $partId) {
                return 999999;
            }

            $index = array_search($partId, $partSequence);

            return $index === false ? 999998 : $index;
        };

        $rows = $rows->sort(function ($a, $b) use ($rank) {
            $rankA = $rank($a);
            $rankB = $rank($b);

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return $a->order <=> $b->order;
        });

        $number = 1;
        foreach ($rows as $row) {
            $row->update(['order' => $number++]);
        }
    }
}
