<?php

namespace App\Services\AI;

interface AIService
{
    /**
     * @param  array{
     *   subject: string,
     *   grade: string,
     *   topic: string,
     *   question_type: string,
     *   difficulty: string,
     *   total_questions: int,
     *   material_text: string|null,
     *   material_image: string|null,
     *   image_description: string|null
     * } $data
     *
     * @return array{
     *   prompt: string,
     *   raw_result: string,
     *   model: string,
     *   image_description: string|null
     * }
     *
     * @throws \Exception
     */
    public function generateQuestions(array $data): array;
}
