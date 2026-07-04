<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GeminiService extends AbstractAIService
{
    public function generateQuestions(array $data): array
    {
        $config = config('ai.providers.gemini');
        $key = $config['key'];
        $model = $config['model'];

        if (empty($key)) {
            throw new \Exception('GEMINI_API_KEY belum diatur di file .env.');
        }

        // Jika ada gambar, gunakan Vision API untuk dapatkan deskripsi dulu
        if (! empty($data['material_image'])) {
            $data['image_description'] = $this->describeImage(
                $data['material_image'],
                $key,
                $model,
                $data['subject'],
                $data['topic']
            );
        }

        $prompt = $this->buildPrompt($data);

        $parts = [['text' => $prompt]];

        // Jika ada gambar DAN ingin kirim langsung ke Vision (multimodal)
        if (! empty($data['material_image']) && Storage::disk('local')->exists($data['material_image'])) {
            $imageData = base64_encode(Storage::disk('local')->get($data['material_image']));
            $mimeType = Storage::disk('local')->mimeType($data['material_image']);

            // Tambahkan gambar sebagai part kedua
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageData,
                ],
            ];

            // Tambahkan instruksi khusus gambar
            $parts[] = ['text' => "\n\nGambar di atas adalah materi visual untuk soal. Buat soal yang merujuk pada gambar tersebut dengan menuliskan '[Perhatikan gambar]' di awal question_text jika soal membutuhkan siswa melihat gambar."];
        }

        $response = Http::retry($config['retry'], $config['retry_sleep'])
            ->timeout($config['timeout'])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                [
                    'contents' => [
                        ['parts' => $parts],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3, // Lebih rendah = lebih faktual, kurang kreatif
                    ],
                ]
            );

        if (! $response->successful()) {
            throw new \Exception('Gemini API Error: '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            throw new \Exception('Gemini tidak mengembalikan hasil.');
        }

        return [
            'prompt' => $prompt,
            'raw_result' => $text,
            'model' => $model,
            'image_description' => $data['image_description'] ?? null,
        ];
    }

    /**
     * Gunakan Gemini Vision untuk mendeskripsikan gambar materi.
     * Deskripsi ini digunakan sebagai konteks tambahan di prompt soal.
     */
    private function describeImage(
        string $imagePath,
        string $key,
        string $model,
        string $subject,
        string $topic
    ): ?string {
        if (! Storage::disk('local')->exists($imagePath)) {
            return null;
        }

        $imageData = base64_encode(Storage::disk('local')->get($imagePath));
        $mimeType = Storage::disk('local')->mimeType($imagePath);

        $response = Http::timeout(30)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                                [
                                    'text' => 'Deskripsikan gambar ini secara detail dalam Bahasa Indonesia. '
                                        ."Konteks: gambar ini adalah materi pelajaran {$subject} tentang {$topic}. "
                                        .'Sebutkan semua elemen, label, angka, teks, diagram, atau bagian yang terlihat. '
                                        .'Deskripsi akan digunakan oleh AI lain untuk membuat soal ujian.',
                                ],
                            ],
                        ],
                    ],
                ]
            );

        if (! $response->successful()) {
            return null;
        }

        return $response->json('candidates.0.content.parts.0.text');
    }
}
