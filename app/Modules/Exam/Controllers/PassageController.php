<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StorePassageRequest;
use App\Http\Requests\Exam\UpdatePassageRequest;
use App\Models\Passage;
use App\Modules\Exam\Services\PassageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PassageController extends Controller
{
    public function __construct(
        private PassageService $passageService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Instructor/PassageView', $this->passageService->indexData());
    }

    public function store(StorePassageRequest $request): RedirectResponse
    {
        try {
            $this->passageService->store($request->validated(), $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Passage berhasil dibuat.');
    }

    public function update(UpdatePassageRequest $request, Passage $passage): RedirectResponse
    {
        try {
            $this->passageService->update($passage, $request->validated(), $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Passage berhasil diperbarui.');
    }

    public function destroy(Passage $passage): RedirectResponse
    {
        try {
            $this->passageService->destroy($passage);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Passage berhasil dihapus.');
    }
}
