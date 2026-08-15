<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreScoreInterpretationRequest;
use App\Http\Requests\MasterData\UpdateScoreInterpretationRequest;
use App\Models\ScoreInterpretation;
use App\Modules\MasterData\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScoreInterpretationController extends Controller
{
    public function __construct(
        private MasterDataService $masterData,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/MasterData/ScoreInterpretations', $this->masterData->scoreInterpretationsIndexData());
    }

    public function store(StoreScoreInterpretationRequest $request): RedirectResponse
    {
        $this->masterData->createScoreInterpretation($request->validated());

        return back()->with('success', 'Interpretasi skor berhasil ditambahkan.');
    }

    public function update(UpdateScoreInterpretationRequest $request, ScoreInterpretation $scoreInterpretation): RedirectResponse
    {
        $this->masterData->updateScoreInterpretation($scoreInterpretation, $request->validated());

        return back()->with('success', 'Interpretasi skor berhasil diperbarui.');
    }

    public function destroy(ScoreInterpretation $scoreInterpretation): RedirectResponse
    {
        $this->masterData->deleteScoreInterpretation($scoreInterpretation);

        return back()->with('success', 'Interpretasi skor berhasil dihapus.');
    }
}
