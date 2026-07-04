<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GroqService extends AbstractAIService
{
    public function generateQuestions(array $data): array
    {
        $config = config('ai.providers.groq');
        $key = $config['key'];
        $model = $config['model'];

        if (empty($key)) {
            throw new \Exception('GROQ_API_KEY belum diatur di file .env.');
        }

        $prompt = $this->buildPrompt($data);

        $response = Http::timeout($config['timeout'])
            ->withHeaders([
                'Authorization' => 'Bearer '.$key,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => $config['temperature'],
            ]);

        if (! $response->successful()) {
            throw new \Exception('Groq API Error: '.$response->body());
        }

        $text = $response->json('choices.0.message.content');

        if (! $text) {
            throw new \Exception('Groq tidak mengembalikan hasil.');
        }

        return [
            'prompt' => $prompt,
            'raw_result' => $text,
            'model' => $model,
            'image_description' => $data['image_description'] ?? null,
        ];
    }
}
