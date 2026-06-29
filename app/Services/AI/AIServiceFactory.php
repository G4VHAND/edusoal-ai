<?php

namespace App\Services\AI;

class AIServiceFactory
{
    /**
     * Membuat instance AI service berdasarkan nama provider.
     *
     * Menggantikan switch/case di QuestionSetController agar
     * controller tidak perlu tahu class mana yang dipakai.
     *
     * @throws \InvalidArgumentException jika provider tidak didukung
     */
    public static function make(string $provider): AIService
    {
        return match ($provider) {
            'gemini' => new GeminiService(),
            'groq'   => new GroqService(),
            default  => throw new \InvalidArgumentException(
                "Provider AI tidak didukung: {$provider}"
            ),
        };
    }

    public static function supported(): array
    {
        return config('ai.supported_providers', ['gemini', 'groq']);
    }
}