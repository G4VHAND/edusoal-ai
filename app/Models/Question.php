<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question_set_id',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
        'source_paragraph',
        'needs_image',
        'image_recommendation',
        'image_path',
        'image_description',
    ];

    protected $casts = [
        'needs_image' => 'boolean',
    ];

    public function questionSet()
    {
        return $this->belongsTo(QuestionSet::class);
    }

    /**
     * Apakah soal ini sudah punya gambar yang diupload guru?
     */
    public function hasImage(): bool
    {
        return ! empty($this->image_path);
    }

    /**
     * Apakah soal ini butuh gambar tapi belum diupload?
     */
    public function needsImageUpload(): bool
    {
        return $this->needs_image && ! $this->hasImage();
    }
}
