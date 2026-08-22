<?php

namespace App\Modules\Exam\Services;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Question;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSectionService
{
    public function __construct(
        private ExamSectionRepositoryInterface $sections,
        private QuestionRepositoryInterface $questions,
        private SkillPartRepositoryInterface $skillParts,
    ) {}

    public function create(Exam $exam, array $data): ExamSection
    {
        return $this->sections->create([...$data, 'exam_id' => $exam->id]);
    }

    public function createWithAutoFill(Exam $exam, array $data): int
    {
        return DB::transaction(function () use ($exam, $data) {
            $section = $this->create($exam, $data);

            return $this->attachApprovedQuestions($exam, $section);
        });
    }

    public function update(ExamSection $section, array $data): ExamSection
    {
        return $this->sections->update($section, $data);
    }

    public function delete(ExamSection $section): void
    {
        $this->sections->delete($section);
    }

    public function attachQuestions(Exam $exam, ExamSection $section, array $questionIds): int
    {
        return DB::transaction(function () use ($exam, $section, $questionIds) {
            $allowed = $this->questions->approvedIdsWhereIn(
                $questionIds,
                $section->skill->code,
                $exam->exam_type_id,
                $section->question_bank_id,
            );

            $existing = array_intersect($this->sections->questionIdsInSection($section->id), $allowed);

            $nextOrder = $this->sections->nextQuestionOrder($section->id);

            $attached = 0;
            foreach ($allowed as $questionId) {
                if (in_array($questionId, $existing, true)) {
                    continue;
                }

                $this->sections->createQuestionPivot($section->id, $questionId, $nextOrder++);
                $attached++;
            }

            return $attached;
        });
    }

    public function detachQuestion(ExamSection $section, Question $question): void
    {
        $this->sections->deleteQuestionPivot($section->id, $question->id);
    }

    public function saveArrangement(ExamSection $section, array $partOrders, array $questionOrders): void
    {
        DB::transaction(function () use ($section, $partOrders, $questionOrders) {
            if (! empty($partOrders)) {
                $skillPartIds = $this->skillParts->idsByBankAndSkill($section->question_bank_id, $section->skill_id);

                foreach ($partOrders as $part) {
                    if (! in_array($part['skill_part_id'], $skillPartIds, true)) {
                        throw ValidationException::withMessages(['part_order' => 'Part tidak valid untuk section ini.']);
                    }

                    $this->sections->updateOrCreatePartPivot($section->id, $part['skill_part_id'], $part['order']);
                }

                $this->renumberSection($section);
            }

            if (! empty($questionOrders)) {
                $this->assertUniqueNumbers($questionOrders, 'question_orders');
                $this->assertQuestionsInSection($section, $questionOrders, 'question_orders');

                foreach ($questionOrders as $order) {
                    $this->sections->updateQuestionOrder($section->id, $order['question_id'], $order['number']);
                }
            }
        });
    }

    public function reorderQuestions(ExamSection $section, array $orders): void
    {
        $this->assertUniqueNumbers($orders, 'orders');
        $this->assertQuestionsInSection($section, $orders, 'orders');

        DB::transaction(function () use ($section, $orders) {
            foreach ($orders as $order) {
                $this->sections->updateQuestionOrder($section->id, $order['question_id'], $order['number']);
            }
        });
    }

    public function reorderParts(ExamSection $section, array $orders): void
    {
        $skillPartIds = $this->skillParts->idsByBankAndSkill($section->question_bank_id, $section->skill_id);

        foreach ($orders as $part) {
            if (! in_array($part['skill_part_id'], $skillPartIds, true)) {
                throw ValidationException::withMessages(['order' => 'Part tidak valid untuk section ini.']);
            }
        }

        DB::transaction(function () use ($section, $orders) {
            foreach ($orders as $part) {
                $this->sections->updateOrCreatePartPivot($section->id, $part['skill_part_id'], $part['order']);
            }

            $this->renumberSection($section);
        });
    }

    private function attachApprovedQuestions(Exam $exam, ExamSection $section): int
    {
        $approved = $this->questions->approvedBySkillForExamType($section->skill->code, $exam->exam_type_id, $section->question_bank_id);

        $number = 1;
        foreach ($approved as $question) {
            $this->sections->firstOrCreateQuestionPivot($section->id, $question->id, $number++);
        }

        return $approved->count();
    }

    private function assertUniqueNumbers(array $items, string $errorKey): void
    {
        $numbers = array_column($items, 'number');
        if (count($numbers) !== count(array_unique($numbers))) {
            throw ValidationException::withMessages([$errorKey => 'Nomor soal harus unik di seluruh section.']);
        }
    }

    private function assertQuestionsInSection(ExamSection $section, array $items, string $errorKey): void
    {
        $sectionQuestionIds = $this->sections->questionIdsInSection($section->id);

        foreach ($items as $order) {
            if (! in_array($order['question_id'], $sectionQuestionIds, true)) {
                throw ValidationException::withMessages([$errorKey => 'Soal tidak terpasang di section ini.']);
            }
        }
    }

    private function renumberSection(ExamSection $section): void
    {
        $sectionPartIds = $this->sections->partIdsInSectionOrdered($section->id);
        $defaultPartIds = $this->skillParts->orderedIdsByBankAndSkill($section->question_bank_id, $section->skill_id);

        $partSequence = array_values(array_unique(array_merge($sectionPartIds, $defaultPartIds)));

        $rows = $this->sections->questionRowsWithQuestion($section->id);

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
            $this->sections->updateQuestionPivotOrder($row->id, $number++);
        }
    }
}
