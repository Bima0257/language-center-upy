<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ReviewQuestionRequest;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use App\Http\Requests\Exam\UpdateLibraryQuestionRequest;
use App\Models\Question;
use App\Modules\Exam\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentLibraryController extends Controller
{
    public function __construct(
        private QuestionService $questionService,
    ) {}

    public function create(Request $request): Response
    {
        $preselectedBankId = (int) ($request->query('question_bank_id') ?: 0);

        return Inertia::render('Instructor/CreateQuestion', $this->questionService->createData(
            $preselectedBankId,
            $this->returnFilters($request),
        ));
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', $this->questionService->editData($question));
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Instructor/ContentLibrary', $this->questionService->indexData(
            $request->only(['skill', 'question_bank_id', 'status', 'search', 'passage_id', 'part_id'])
        ));
    }

    public function store(StoreLibraryQuestionRequest $request): RedirectResponse
    {
        $count = $this->questionService->storeLibrary($request->validated(), $request);

        return to_route('content-library.index', $this->storeReturnFilters($request))
            ->with('success', "{$count} soal berhasil ditambahkan ke bank soal.");
    }

    public function preview(Question $question): Response
    {
        return Inertia::render('Instructor/QuestionPreview', $this->questionService->previewData($question));
    }

    public function update(UpdateLibraryQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questionService->updateLibrary($question, $request->validated());

        return to_route('content-library.index', [
            'question_bank_id' => $question->question_bank_id,
            'skill' => $question->skill->code,
        ])->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->questionService->delete($question);

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function review(ReviewQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questionService->review($question, $request->validated());

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Soal berhasil {$label}.");
    }

    public function bulkReview(ReviewQuestionRequest $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'Tidak ada soal yang dipilih.');
        }

        $count = $this->questionService->bulkReview($ids, $request->validated());

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "{$count} soal berhasil {$label}.");
    }

    private function returnFilters(Request $request): array
    {
        return array_filter(
            $request->only(['question_bank_id', 'skill', 'part_id', 'status', 'search']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function storeReturnFilters(Request $request): array
    {
        return array_filter([
            'question_bank_id' => $request->input('question_bank_id'),
            'skill' => $request->input('_return_skill') ?: $request->input('questions.0.skill'),
            'part_id' => $request->input('_return_part_id'),
            'status' => $request->input('_return_status'),
            'search' => $request->input('_return_search'),
        ], fn ($value) => ! empty($value));
    }
}
