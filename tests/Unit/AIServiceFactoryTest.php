<?php

namespace Tests\Unit;

use App\Services\AI\AIServiceFactory;
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

    public function test_make_throws_for_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/tidak didukung/');

        AIServiceFactory::make('openai');
    }

    public function test_make_throws_for_deepseek_provider(): void
    {
        // DeepSeek sudah tidak didukung lagi, memastikan tidak diam-diam
        // fallback ke provider lain.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/tidak didukung/');

        AIServiceFactory::make('deepseek');
    }

    public function test_supported_returns_array_of_providers(): void
    {
        $supported = AIServiceFactory::supported();

        $this->assertIsArray($supported);
        $this->assertContains('gemini', $supported);
        $this->assertContains('groq', $supported);
        $this->assertNotContains('deepseek', $supported);
    }

    // ── labeled() — dipakai bersama oleh halaman admin & form generate soal ──

    public function test_labeled_returns_entry_for_every_supported_provider(): void
    {
        $labeled = AIServiceFactory::labeled();

        foreach (AIServiceFactory::supported() as $provider) {
            $this->assertArrayHasKey($provider, $labeled);
            $this->assertArrayHasKey('label', $labeled[$provider]);
            $this->assertArrayHasKey('description', $labeled[$provider]);
            $this->assertNotEmpty($labeled[$provider]['label']);
        }
    }

    public function test_labeled_does_not_include_unsupported_providers(): void
    {
        $labeled = AIServiceFactory::labeled();

        $this->assertArrayNotHasKey('deepseek', $labeled);
        $this->assertArrayNotHasKey('openai', $labeled);
    }

    public function test_labeled_gemini_mentions_image_support(): void
    {
        // Perbedaan paling penting antar provider saat ini: cuma Gemini
        // yang mendukung soal berbasis gambar — pastikan ini kelihatan di
        // label supaya admin sekolah/individual tidak salah pilih.
        $labeled = AIServiceFactory::labeled();

        $this->assertStringContainsStringIgnoringCase('gambar', $labeled['gemini']['description']);
    }
}
