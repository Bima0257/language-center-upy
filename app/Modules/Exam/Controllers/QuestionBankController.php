<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class QuestionBankController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Instructor/QuestionBankManager', [
            'questionBanks' => QuestionBank::with('examType')->withCount('questions')->orderBy('name')->get(),
            'examTypes' => ExamType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        QuestionBank::create($validated);

        return back()->with('success', 'Bank soal berhasil dibuat.');
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['description'] = $validated['description'] ?? null;
        if ($validated['description']) {
            $validated['description'] = Purifier::clean($validated['description']);
        }

        $questionBank->update($validated);

        return back()->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        $questionBank->delete();

        return back()->with('success', 'Bank soal berhasil dihapus.');
    }
}
