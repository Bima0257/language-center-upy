<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PreviewController extends Controller
{
    /**
     * Layout ujian preview: skill code => [[nama part, jumlah soal], ...]
     * Standalone (tanpa passage) ditempatkan di Reading Part 1.
     */
    private const PREVIEW_LAYOUT = [
        'listening' => [
            ['name' => 'Part 1', 'count' => 10],
            ['name' => 'Part 2', 'count' => 8],
            ['name' => 'Part 3', 'count' => 7],
        ],
        'reading' => [
            ['name' => 'Part 1', 'count' => 12, 'standalone' => 3],
            ['name' => 'Part 2', 'count' => 13],
        ],
    ];

    private const SKILL_META = [
        'listening' => [
            'order' => 1,
            'name' => 'Listening',
            'description' => '<p>In the Listening test, you will be asked to demonstrate how well you understand spoken English. The entire Listening test will last approximately 45 minutes. There are four parts, and directions are given for each part. You must mark your answers on the separate answer sheet. Do not write your answers in your test book.</p>',
        ],
        'reading' => [
            'order' => 2,
            'name' => 'Reading',
            'description' => '<p>Dalam bagian Reading, Anda akan membaca beberapa bacaan akademik dan menjawab pertanyaan berdasarkan isi bacaan. Bacalah setiap bacaan dengan cermat sebelum menjawab. Sebagian soal bersifat stand alone dan tidak terkait dengan bacaan mana pun.</p>',
        ],
    ];

    private const PART_DIRECTIONS = [
        'listening' => [
            'Part 1' => '<p><strong>Short Conversations.</strong> Pada bagian ini Anda akan mendengar percakapan pendek antara dua pembicara. Setiap percakapan diikuti satu pertanyaan. Putar audio, lalu pilih jawaban terbaik dari empat pilihan (A–D).</p>',
            'Part 2' => '<p><strong>Long Conversations.</strong> Pada bagian ini Anda akan mendengar percakapan panjang. Setiap percakapan diikuti beberapa pertanyaan. Jawablah berdasarkan apa yang Anda dengar.</p>',
            'Part 3' => '<p><strong>Lectures.</strong> Pada bagian ini Anda akan mendengar ceramah akademik. Setiap ceramah diikuti beberapa pertanyaan. Catat poin-poin penting saat mendengarkan.</p>',
        ],
        'reading' => [
            'Part 1' => '<p>Bacalah bacaan berikut dengan saksama, kemudian jawablah pertanyaan yang tersedia. Beberapa soal bersifat <strong>stand alone</strong> (tidak memerlukan bacaan). Pilih satu jawaban terbaik dari empat pilihan (A–D).</p>',
            'Part 2' => '<p>Bacalah bacaan berikut dengan saksama, kemudian jawablah pertanyaan yang tersedia. Pilih satu jawaban terbaik dari empat pilihan (A–D).</p>',
        ],
    ];

    private const READING_PASSAGES = [
        ['title' => 'The History of Solar Energy', 'topic' => 'solar energy'],
        ['title' => 'The Industrial Revolution', 'topic' => 'industrialization'],
    ];

    private const LISTENING_TITLES = [
        'Part 1' => 'Short Conversation',
        'Part 2' => 'Long Conversation: Campus Life',
        'Part 3' => 'Lecture: Marine Biology',
    ];

    private const READING_QUESTIONS = [
        ['q' => 'What is the main topic of the passage?', 'a' => 'The development of modern technology', 'b' => 'The historical background of the topic discussed', 'c' => 'A comparison between two ancient civilizations', 'd' => 'The biography of a famous scientist'],
        ['q' => 'According to paragraph 2, which factor contributed most to the change?', 'a' => 'Economic pressure from foreign markets', 'b' => 'Technological innovation and new machinery', 'c' => 'Government restrictions on trade', 'd' => 'The decline of urban populations'],
        ['q' => 'The word "significant" in paragraph 3 is closest in meaning to', 'a' => 'slight', 'b' => 'considerable', 'c' => 'temporary', 'd' => 'accidental'],
        ['q' => 'Which of the following is NOT mentioned in the passage?', 'a' => 'The origin of the movement', 'b' => 'Its impact on society', 'c' => 'The cost of implementation', 'd' => 'Opposition from certain groups'],
        ['q' => 'What can be inferred about the subject of the passage?', 'a' => 'It disappeared shortly after it emerged', 'b' => 'It influenced later developments', 'c' => 'It was limited to one country', 'd' => 'It had no economic significance'],
    ];

    private const STANDALONE_QUESTIONS = [
        ['q' => 'Choose the word that best completes the sentence: "The results of the experiment were ___ to interpret."', 'a' => 'difficulty', 'b' => 'difficulties', 'c' => 'difficult', 'd' => 'difficultly'],
        ['q' => 'Select the option with the correct word order.', 'a' => 'She often goes to the library after class.', 'b' => 'Often she the library goes to after class.', 'c' => 'She goes often the library to after class.', 'd' => 'After class she to the library often goes.'],
        ['q' => 'Which sentence uses the passive voice correctly?', 'a' => 'The bridge was built in 1932.', 'b' => 'The bridge was building in 1932.', 'c' => 'The bridge is build in 1932.', 'd' => 'The bridge has build in 1932.'],
    ];

    private const LISTENING_QUESTIONS = [
        ['q' => 'What does the man mean?', 'a' => 'He has finished his assignment', 'b' => 'He needs more time for his assignment', 'c' => 'He lost his assignment file', 'd' => 'He forgot about the assignment'],
        ['q' => 'Where does this conversation most likely take place?', 'a' => 'In a bookstore', 'b' => 'In a classroom', 'c' => 'In a restaurant', 'd' => 'In a hospital'],
        ['q' => 'What is the woman\'s problem?', 'a' => 'She cannot find her classroom', 'b' => 'Her registration was delayed', 'c' => 'She missed an important deadline', 'd' => 'Her laptop stopped working'],
        ['q' => 'What does the professor mainly discuss?', 'a' => 'The migration patterns of coral species', 'b' => 'The relationship between coral reefs and their environment', 'c' => 'Methods for deep-sea exploration', 'd' => 'The history of marine biology research'],
        ['q' => 'According to the lecture, what causes coral bleaching?', 'a' => 'The loss of symbiotic algae due to warm water', 'b' => 'An increase in marine predators', 'c' => 'Pollution from coastal cities only', 'd' => 'Excessive fishing activity'],
    ];

    public function examTake(Request $request)
    {
        $exam = Exam::where('title', 'TOEFL iBT Try Out 1')->first();

        $session = null;

        if ($exam) {
            $session = ExamSession::where('status', 'in_progress')
                ->with(['slot.schedule.exam.sections', 'answers.question', 'currentSection.skill'])
                ->first();

            if ($session) {
                $session->answers->each(function ($answer) {
                    if ($answer->question) {
                        $answer->question->makeHidden('correct_answer');
                    }
                });
            }
        }

        if (! $session) {
            $user = $request->user();
            $section = $exam?->sections()->with('skill')->first();

            $session = new \stdClass;
            $session->id = 0;
            $session->status = 'preview';
            $session->user = (object) ['name' => $user->name ?? 'Peserta Preview'];
            $session->started_at = now()->subMinutes(25)->toIso8601String();
            $session->violation_strikes = 0;
            $session->answers = collect();
            $session->current_section = $section;
            $session->slot = (object) [
                'schedule' => (object) [
                    'exam' => $exam ? (object) [
                        'duration_minutes' => $exam->duration_minutes ?? 71,
                    ] : null,
                ],
            ];
        }

        $total = max(10, min(100, (int) $request->query('soal', 50)));

        [$overview, $blocks] = $this->buildSyntheticFlow($exam, $total);

        return Inertia::render('Exam/PreviewTake', [
            'session' => $session,
            'overview' => $overview,
            'blocks' => $blocks,
        ]);
    }

    private function buildSyntheticFlow(?Exam $exam, int $total): array
    {
        $layout = $this->scaledLayout($total);

        $blocks = [];
        $startNumber = 1;

        foreach ($layout as $skillCode => $parts) {
            $meta = self::SKILL_META[$skillCode];

            foreach ($parts as $index => $partConfig) {
                $partName = $partConfig['name'];
                $count = $partConfig['count'];
                $passage = $this->makePassage($skillCode, $partName, $index);
                $questions = [];
                $standaloneLeft = $partConfig['standalone'] ?? 0;

                if ($standaloneLeft > 0) {
                    // Soal passage-based lebih dulu, standalone ditaruh di akhir part.
                    $passageCount = $count - $standaloneLeft;

                    for ($i = 0; $i < $passageCount; $i++) {
                        $questions[] = $this->makeQuestion($skillCode, "{$startNumber}", $passage, $i, false);
                        $startNumber++;
                    }

                    for ($i = 0; $i < $standaloneLeft; $i++) {
                        $questions[] = $this->makeQuestion($skillCode, "{$startNumber}", null, $i, true);
                        $startNumber++;
                    }
                } else {
                    for ($i = 0; $i < $count; $i++) {
                        $questions[] = $this->makeQuestion($skillCode, "{$startNumber}", $passage, $i, false);
                        $startNumber++;
                    }
                }

                $directions = self::PART_DIRECTIONS[$skillCode][$partName]
                    ?? '<p>Pilih satu jawaban terbaik dari empat pilihan (A–D).</p>';

                $blocks[] = [
                    'startNumber' => 0,
                    'count' => count($questions),
                    'skill' => [
                        'code' => $skillCode,
                        'name' => $meta['name'],
                        'description' => $meta['description'],
                    ],
                    'part' => [
                        'name' => $partName,
                        'directions' => $directions,
                    ],
                    'questions' => $questions,
                ];
            }
        }

        // startNumber global dihitung ulang agar konsisten
        $runningStart = 1;

        foreach ($blocks as &$block) {
            foreach ($block['questions'] as &$question) {
                $question['global_number'] = $runningStart++;
            }
            unset($question);

            $block['startNumber'] = $block['questions'][0]['global_number'];
        }
        unset($block);

        $overview = [
            'examTitle' => $exam->title ?? 'Preview Ujian',
            'durationMinutes' => $exam->duration_minutes ?? 71,
            'totalQuestions' => $startNumber - 1,
            'skills' => collect($blocks)
                ->groupBy(fn ($b) => $b['skill']['code'])
                ->map(fn ($items, $code) => [
                    'code' => $code,
                    'name' => $items->first()['skill']['name'],
                    'totalQuestions' => $items->sum('count'),
                    'parts' => $items->values()->map(fn ($b) => [
                        'name' => $b['part']['name'],
                        'totalQuestions' => $b['count'],
                    ])->all(),
                ])
                ->sortBy(fn ($s) => self::SKILL_META[$s['code']]['order'])
                ->values()
                ->all(),
        ];

        return [$overview, $blocks];
    }

    private function scaledLayout(int $total): array
    {
        $baseTotal = array_sum(array_map(
            fn ($parts) => array_sum(array_column($parts, 'count')),
            self::PREVIEW_LAYOUT,
        ));

        if ($total === $baseTotal) {
            return self::PREVIEW_LAYOUT;
        }

        $layout = [];

        foreach (self::PREVIEW_LAYOUT as $skillCode => $parts) {
            $skillBase = array_sum(array_column($parts, 'count'));
            $skillTotal = (int) round($total * ($skillBase / $baseTotal));

            if ($skillCode === 'reading') {
                // pastikan total pas
                $listeningTotal = array_sum(array_column($layout['listening'] ?? [], 'count'));
                $skillTotal = max(1, $total - $listeningTotal);
            }

            $layout[$skillCode] = [];
            $allocated = 0;
            $lastIndex = count($parts) - 1;

            foreach ($parts as $i => $part) {
                if ($i === $lastIndex) {
                    $count = max(1, $skillTotal - $allocated);
                } else {
                    $count = max(1, (int) round($skillTotal * ($part['count'] / $skillBase)));
                    $allocated += $count;
                }

                $entry = ['name' => $part['name'], 'count' => $count];

                if (isset($part['standalone'])) {
                    $entry['standalone'] = min(3, max(1, (int) floor($count / 4)));
                }

                $layout[$skillCode][] = $entry;
            }
        }

        return $layout;
    }

    private function makePassage(string $skillCode, string $partName, int $partIndex): array
    {
        if ($skillCode === 'listening') {
            return [
                'id' => "gen-listening-{$partIndex}",
                'title' => self::LISTENING_TITLES[$partName] ?? 'Audio Materi',
                'type' => 'audio',
                'content_text' => null,
                'image_url' => 'assets/image/default-passage.png',
                'audio_url' => 'assets/audio/demo.mp3',
            ];
        }

        $source = self::READING_PASSAGES[$partIndex % count(self::READING_PASSAGES)];

        $paragraphs = [
            "<p>The development of {$source['topic']} represents one of the most studied phenomena in modern history. Early attempts were often limited by available technology, yet each generation of researchers built upon the work of those who came before them. Over time, small improvements accumulated into transformations that reshaped entire industries.</p>",
            '<p>Historians generally agree that three factors accelerated this progress: access to new materials, improvements in communication, and growing public interest. When these elements combined, change no longer happened gradually. Instead, societies adapted rapidly, sometimes within a single generation.</p>',
            '<p>Critics, however, point out that rapid change carried significant costs. Traditional practices were abandoned before alternatives were fully understood. Communities that had depended on older methods faced difficult transitions, and the benefits of progress were not always distributed evenly.</p>',
            "<p>Today, scholars continue to debate the long-term consequences. What remains clear is that understanding {$source['topic']} requires examining not only its technical achievements but also its social and economic context.</p>",
        ];

        return [
            'id' => "gen-reading-{$partIndex}",
            'title' => $source['title'],
            'type' => 'text',
            'content_text' => implode('', $paragraphs),
            'image_url' => 'assets/image/default-passage.png',
            'audio_url' => null,
        ];
    }

    private function makeQuestion(string $skillCode, string $id, ?array $passage, int $offset, bool $standalone): array
    {
        if ($standalone) {
            $template = self::STANDALONE_QUESTIONS[$offset % count(self::STANDALONE_QUESTIONS)];
        } elseif ($skillCode === 'listening') {
            $template = self::LISTENING_QUESTIONS[$offset % count(self::LISTENING_QUESTIONS)];
        } else {
            $template = self::READING_QUESTIONS[$offset % count(self::READING_QUESTIONS)];
        }

        return [
            'id' => "gen-{$skillCode}-{$id}".($standalone ? '-sa' : ''),
            'question_text' => $template['q'],
            'option_a' => $template['a'],
            'option_b' => $template['b'],
            'option_c' => $template['c'],
            'option_d' => $template['d'],
            'order' => $offset + 1,
            'passage' => $passage,
            'skill' => [
                'code' => $skillCode,
                'name' => self::SKILL_META[$skillCode]['name'],
            ],
            'skillPart' => null,
        ];
    }
}
