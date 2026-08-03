<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\ScoreInterpretation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreInterpretationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MasterData/ScoreInterpretations', [
            'examTypes' => ExamType::orderBy('name')->get(),
            'interpretations' => ScoreInterpretation::with('examType')
                ->orderBy('min_score')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'gte:min_score'],
            'cefr_level' => ['required', 'string', 'max:10'],
            'level_label' => ['required', 'string', 'max:50'],
            'is_passing' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        ScoreInterpretation::create($validated);

        return back()->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function update(Request $request, ScoreInterpretation $scoreInterpretation): RedirectResponse
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'gte:min_score'],
            'cefr_level' => ['required', 'string', 'max:10'],
            'level_label' => ['required', 'string', 'max:50'],
            'is_passing' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $scoreInterpretation->update($validated);

        return back()->with('success', 'Interpretasi skor berhasil diperbarui.');
    }

    public function destroy(ScoreInterpretation $scoreInterpretation): RedirectResponse
    {
        $scoreInterpretation->delete();

        return back()->with('success', 'Interpretasi skor berhasil dihapus.');
    }
}
