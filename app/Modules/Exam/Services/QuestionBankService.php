<?php

namespace App\Modules\Exam\Services;

use App\Models\QuestionBank;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use Mews\Purifier\Facades\Purifier;

class QuestionBankService
{
    public function __construct(
        private QuestionBankRepositoryInterface $questionBanks,
        private ExamTypeRepositoryInterface $examTypes,
    ) {}

    public function indexData(): array
    {
        return [
            'questionBanks' => $this->questionBanks->allWithExamTypeAndCounts(),
            'examTypes' => $this->examTypes->allActiveOrdered(),
        ];
    }

    public function store(array $validated): void
    {
        $this->questionBanks->create($this->sanitize($validated));
    }

    public function update(QuestionBank $questionBank, array $validated): void
    {
        $this->questionBanks->update($questionBank, $this->sanitize($validated));
    }

    public function destroy(QuestionBank $questionBank): void
    {
        $this->questionBanks->delete($questionBank);
    }

    private function sanitize(array $data): array
    {
        $data['description'] = $data['description'] ?? null;
        if ($data['description']) {
            $data['description'] = Purifier::clean($data['description']);
        }

        return $data;
    }
}
