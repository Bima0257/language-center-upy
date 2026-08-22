<?php

namespace App\Modules\Exam\Services;

use App\Models\Passage;
use App\Models\Skill;
use App\Modules\Exam\Repositories\Contracts\PassageRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillRepositoryInterface;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class PassageService
{
    public function __construct(
        private PassageRepositoryInterface $passages,
        private QuestionBankRepositoryInterface $questionBanks,
        private SkillRepositoryInterface $skills,
        private SkillPartRepositoryInterface $skillParts,
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function indexData(): array
    {
        return [
            'passages' => $this->passages->paginateWithCounts(),
            'questionBanks' => $this->questionBanks->allActiveWithExamTypeOrdered(),
            'skillOptions' => $this->skills->allActiveOrdered()
                ->map(fn (Skill $skill) => ['value' => $skill->id, 'label' => $skill->name, 'code' => $skill->code])
                ->toArray(),
            'parts' => $this->skillParts->allActiveOrdered(),
        ];
    }

    public function store(array $validated, ?Request $request): void
    {
        $data = $this->prepareData($validated, $request, null);

        $this->assertTypeFulfilled($data, null);

        $this->passages->create($data);
    }

    public function update(Passage $passage, array $validated, ?Request $request): void
    {
        $data = $this->prepareData($validated, $request, $passage);

        $this->assertTypeFulfilled($data, $passage);

        if ($this->passages->hasListeningQuestions($passage->id) && $data['type'] !== 'audio') {
            throw new \RuntimeException('Passage ini dipakai soal listening — tipe wajib tetap Audio.');
        }

        $this->passages->update($passage, $data);
    }

    public function destroy(Passage $passage): void
    {
        if ($this->passages->hasQuestions($passage->id)) {
            throw new \RuntimeException('Passage tidak bisa dihapus karena masih dipakai oleh soal.');
        }

        if ($passage->audio_url) {
            Storage::disk('public')->delete($passage->audio_url);
        }
        if ($passage->image_url) {
            Storage::disk('public')->delete($passage->image_url);
        }

        $this->passages->delete($passage);
    }

    private function prepareData(array $validated, ?Request $request, ?Passage $existing): array
    {
        $data = $validated;
        $data['content_text'] = $this->sanitizeContent($data['content_text'] ?? null);

        if ($request !== null && $request->hasFile('audio_file')) {
            if ($existing !== null && $existing->audio_url) {
                Storage::disk('public')->delete($existing->audio_url);
            }
            $audioPath = $request->file('audio_file')->store('passages/audio', 'public');
            $compressedPath = $this->audioCompression->compress('public', $audioPath);
            $data['audio_url'] = $compressedPath ?? $audioPath;
        } elseif ($existing === null) {
            $data['audio_url'] = null;
        }

        if ($request !== null && $request->hasFile('image_file')) {
            if ($existing !== null && $existing->image_url) {
                Storage::disk('public')->delete($existing->image_url);
            }
            $imagePath = $request->file('image_file')->store('passages/images', 'public');
            $compressedPath = $this->imageCompression->compress('public', $imagePath);
            $data['image_url'] = $compressedPath ?? $imagePath;
        } elseif ($existing === null) {
            $data['image_url'] = null;
        }

        unset($data['audio_file'], $data['image_file']);

        return $data;
    }

    private function assertTypeFulfilled(array $data, ?Passage $existing): void
    {
        $hasAudio = ! empty($data['audio_url'] ?? null) || ($existing !== null && ! empty($existing->audio_url));
        $hasImage = ! empty($data['image_url'] ?? null) || ($existing !== null && ! empty($existing->image_url));

        if ($data['type'] === 'audio' && ! $hasAudio) {
            throw new \RuntimeException('Passage tipe audio wajib memiliki file audio.');
        }

        if ($data['type'] === 'image' && ! $hasImage) {
            throw new \RuntimeException('Passage tipe gambar wajib memiliki file gambar.');
        }

        if ($data['type'] === 'text' && empty($data['content_text'])) {
            throw new \RuntimeException('Passage tipe teks wajib memiliki isi teks.');
        }
    }

    private function sanitizeContent(?string $content): ?string
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        return Purifier::clean($content);
    }
}
