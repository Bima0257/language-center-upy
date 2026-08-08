<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Passage;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class PassageController extends Controller
{
    public function __construct(
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function index(Request $request): Response
    {
        $passages = Passage::withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Instructor/PassageView', [
            'passages' => $passages,
            'questionBanks' => QuestionBank::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill_id')->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,audio,image,prompt'],
            'content_text' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $validated['content_text'] = $this->sanitizeContent($validated['content_text'] ?? null);

        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('passages/audio', 'public');
            $compressedPath = $this->audioCompression->compress('public', $audioPath);
            $validated['audio_url'] = $compressedPath ?? $audioPath;
        } else {
            $validated['audio_url'] = null;
        }

        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('passages/images', 'public');
            $compressedPath = $this->imageCompression->compress('public', $imagePath);
            $validated['image_url'] = $compressedPath ?? $imagePath;
        } else {
            $validated['image_url'] = null;
        }

        unset($validated['audio_file'], $validated['image_file']);

        Passage::create($validated);

        return back()->with('success', 'Passage berhasil dibuat.');
    }

    public function update(Request $request, Passage $passage): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,audio,image,prompt'],
            'content_text' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $validated['content_text'] = $this->sanitizeContent($validated['content_text'] ?? null);

        if ($request->hasFile('audio_file')) {
            if ($passage->audio_url) {
                Storage::disk('public')->delete($passage->audio_url);
            }
            $audioPath = $request->file('audio_file')->store('passages/audio', 'public');
            $compressedPath = $this->audioCompression->compress('public', $audioPath);
            $validated['audio_url'] = $compressedPath ?? $audioPath;
        }

        if ($request->hasFile('image_file')) {
            if ($passage->image_url) {
                Storage::disk('public')->delete($passage->image_url);
            }
            $imagePath = $request->file('image_file')->store('passages/images', 'public');
            $compressedPath = $this->imageCompression->compress('public', $imagePath);
            $validated['image_url'] = $compressedPath ?? $imagePath;
        }

        unset($validated['audio_file'], $validated['image_file']);

        $passage->update($validated);

        return back()->with('success', 'Passage berhasil diperbarui.');
    }

    public function destroy(Passage $passage): RedirectResponse
    {
        if ($passage->questions()->exists()) {
            return back()->with('error', 'Passage tidak bisa dihapus karena masih dipakai oleh soal.');
        }

        if ($passage->audio_url) {
            Storage::disk('public')->delete($passage->audio_url);
        }
        if ($passage->image_url) {
            Storage::disk('public')->delete($passage->image_url);
        }

        $passage->delete();

        return back()->with('success', 'Passage berhasil dihapus.');
    }

    private function sanitizeContent(?string $content): ?string
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        return Purifier::clean($content);
    }
}
