<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Reading Comprehension', 'type' => 'skill'],
            ['name' => 'Listening Comprehension', 'type' => 'skill'],
            ['name' => 'Grammar Structure', 'type' => 'skill'],
            ['name' => 'Vocabulary', 'type' => 'skill'],
            ['name' => 'Main Idea', 'type' => 'skill'],
            ['name' => 'Detail Information', 'type' => 'skill'],
            ['name' => 'Inference', 'type' => 'skill'],
            ['name' => 'Vocabulary Context', 'type' => 'skill'],

            ['name' => 'Science', 'type' => 'topic'],
            ['name' => 'History', 'type' => 'topic'],
            ['name' => 'Education', 'type' => 'topic'],
            ['name' => 'Technology', 'type' => 'topic'],
            ['name' => 'Environment', 'type' => 'topic'],
            ['name' => 'Social', 'type' => 'topic'],
            ['name' => 'Business', 'type' => 'topic'],
            ['name' => 'Arts', 'type' => 'topic'],

            ['name' => 'Beginner', 'type' => 'level'],
            ['name' => 'Intermediate', 'type' => 'level'],
            ['name' => 'Advanced', 'type' => 'level'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate($tag);
        }

        $this->command->info('Tags seeded: ' . count($tags));
    }
}
