<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->model = config('services.openrouter.model', 'google/gemini-flash-1.5');
    }

    public function generate(array $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'AI Video Generator',
        ])->timeout(90)->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $this->buildPrompt($input)],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            throw new \Exception('AI API request failed: ' . $response->status() . ' ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode($content, true);

        if (!$data || !isset($data['script'])) {
            return ['script' => $content ?? 'No content returned.', 'scenes' => []];
        }

        return [
            'script' => $data['script'] ?? '',
            'scenes' => $data['scenes'] ?? [],
        ];
    }

    private function buildPrompt(array $input): string
    {
        $duration = !empty($input['duration']) ? "- Duration: {$input['duration']}" : '';
        $type = str_replace('_', ' ', $input['video_type']);

        return <<<PROMPT
You are a professional video scriptwriter.

Generate a complete video script with scene breakdown for:
- Video Type: {$type}
- Topic: {$input['topic']}
- Keywords: {$input['keywords']}
- Target Audience: {$input['target_audience']}
- Tone: {$input['tone']}
{$duration}

Respond ONLY in valid JSON format with no markdown, no code blocks:
{
  "script": "full narration script here as a single string",
  "scenes": [
    {
      "scene_number": 1,
      "title": "Scene title",
      "duration": "5s",
      "narration": "narration text for this scene",
      "visual_description": "visual direction for this scene"
    }
  ]
}
PROMPT;
    }
}
