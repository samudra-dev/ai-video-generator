<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'video_type' => $this->faker->randomElement(['marketing_video', 'educational_clip', 'social_media_reel']),
            'topic' => $this->faker->paragraph(),
            'keywords' => implode(', ', $this->faker->words(5)),
            'target_audience' => $this->faker->sentence(),
            'tone' => $this->faker->randomElement(['formal', 'casual', 'persuasive', 'humorous', 'inspirational']),
            'duration' => $this->faker->randomElement(['30s', '60s', '90s', '120s', null]),
            'script' => $this->faker->paragraphs(3, true),
            'scenes' => [
                [
                    'scene_number' => 1,
                    'title' => 'Opening',
                    'duration' => '10s',
                    'narration' => $this->faker->sentence(),
                    'visual_description' => $this->faker->sentence(),
                ],
            ],
            'template_used' => null,
            'status' => 'completed',
        ];
    }
}
