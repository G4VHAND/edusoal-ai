<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'school_id', 'user_id', 'name', 'file_path',
        'original_filename', 'type', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}