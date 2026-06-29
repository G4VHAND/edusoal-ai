<?php

namespace Tests\Unit;

use App\Services\AI\AIServiceFactory;
use App\Services\AI\DeepSeekService;
use App\Services\AI\GeminiService;
use App\Services\AI\GroqService;
use InvalidArgumentException;
use Tests\TestCase;

class AIServiceFactoryTest extends TestCase
{
    public function test_make_returns_gemini_service(): void
    {
        $service = AIServiceFactory::make('gemini');
        $this->assertInstanceOf(GeminiService::class, $service);
    }

    public function test_make_returns_groq_service(): void
    {
        $service = AIServiceFactory::make('groq');
        $this->assertInstanceOf(GroqService::class, $service);
    }

    public function test_make_returns_deepseek_service(): void
    {
        $service = AIServiceFactory::make('deepseek');
        $this->assertInstanceOf(DeepSeekService::class, $service);
    }

    public function test_make_throws_for_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/tidak didukung/');

        AIServiceFactory::make('openai');
    }

    public function test_supported_returns_array_of_providers(): void
    {
        $supported = AIServiceFactory::supported();

        $this->assertIsArray($supported);
        $this->assertContains('gemini', $supported);
        $this->assertContains('groq', $supported);
        $this->assertContains('deepseek', $supported);
    }
}
