<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreQuestionBankRequest;
use App\Http\Requests\Exam\UpdateQuestionBankRequest;
use App\Models\QuestionBank;
use App\Modules\Exam\Services\QuestionBankService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    public function __construct(
        private QuestionBankService $questionBankService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Instructor/QuestionBankManager', $this->questionBankService->indexData());
    }

    public function store(StoreQuestionBankRequest $request): RedirectResponse
    {
        $this->questionBankService->store($request->validated());

        return back()->with('success', 'Bank soal berhasil dibuat.');
    }

    public function update(UpdateQuestionBankRequest $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->questionBankService->update($questionBank, $request->validated());

        return back()->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        try {
            $this->questionBankService->destroy($questionBank);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bank soal berhasil dihapus.');
    }
}
