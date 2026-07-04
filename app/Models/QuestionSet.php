<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionSet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'subject',
        'grade',
        'topic',
        'question_type',
        'difficulty',
        'curriculum',
        'assessment_type',
        'total_questions',
        'ai_result',
        'is_ai_generated',
        'ai_provider',
        'ai_model',
        'ai_prompt',
        'ai_error',
        'status',
        'material_file',
        'material_original_name',
        'material_image',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
