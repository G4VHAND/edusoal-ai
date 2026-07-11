<?php

namespace App\Http\Requests\QuestionSet;

use App\Models\QuestionSet;
use App\Services\AI\AIServiceFactory;

class StoreQuestionSetRequest extends QuestionSetRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', QuestionSet::class);
    }

    public function rules(): array
    {
        return $this->sharedRules() + [
            'total_questions' => 'required|integer|min:1|max:50',
            // Nullable: guru tidak mengirim field ini sama sekali (provider
            // sepenuhnya ditentukan sekolah), individual boleh mengirim
            // preferensinya tapi tetap divalidasi ulang & bisa diabaikan
            // server lewat User::resolveAiProvider() kalau plan tidak
            // mengizinkan pilih provider sendiri.
            'ai_provider' => 'nullable|string|in:'.implode(',', AIServiceFactory::supported()),
            'material_file' => [
                'nullable', 'file', 'max:5120',
                'mimetypes:application/pdf,application/msword,'
                    .'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                    .'text/plain',
            ],
            'material_image' => [
                'nullable', 'file', 'max:5120',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
            ],
        ];
    }
}
