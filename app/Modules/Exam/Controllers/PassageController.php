<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Passage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PassageController extends Controller
{
    public function index(Request $request): Response
    {
        $passages = Passage::withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Instructor/PassageView', [
            'passages' => $passages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,audio,image,prompt'],
            'content_text' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:20480'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'language' => ['required', 'string', 'max:50'],
        ]);

        $validated['word_count'] = $validated['content_text']
            ? str_word_count(strip_tags($validated['content_text']))
            : null;

        if ($request->hasFile('audio_file')) {
            $validated['audio_url'] = $request->file('audio_file')->store('passages/audio', 'public');
        } else {
            $validated['audio_url'] = null;
        }

        if ($request->hasFile('image_file')) {
            $validated['image_url'] = $request->file('image_file')->store('passages/images', 'public');
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
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:20480'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'language' => ['required', 'string', 'max:50'],
        ]);

        $validated['word_count'] = $validated['content_text']
            ? str_word_count(strip_tags($validated['content_text']))
            : null;

        if ($request->hasFile('audio_file')) {
            if ($passage->audio_url) {
                Storage::disk('public')->delete($passage->audio_url);
            }
            $validated['audio_url'] = $request->file('audio_file')->store('passages/audio', 'public');
        }

        if ($request->hasFile('image_file')) {
            if ($passage->image_url) {
                Storage::disk('public')->delete($passage->image_url);
            }
            $validated['image_url'] = $request->file('image_file')->store('passages/images', 'public');
        }

        unset($validated['audio_file'], $validated['image_file']);

        $passage->update($validated);

        return back()->with('success', 'Passage berhasil diperbarui.');
    }

    public function destroy(Passage $passage): RedirectResponse
    {
        if ($passage->audio_url) {
            Storage::disk('public')->delete($passage->audio_url);
        }
        if ($passage->image_url) {
            Storage::disk('public')->delete($passage->image_url);
        }

        $passage->delete();

        return back()->with('success', 'Passage berhasil dihapus.');
    }
}
