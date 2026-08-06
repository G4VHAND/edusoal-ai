<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'school_id', 'title', 'subject', 'description',
        'file_path', 'original_filename', 'file_size', 'mime_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Materi yang boleh dilihat $user: punya dia sendiri (personal, apapun
     * sekolahnya) DITAMBAH materi sekolah (kalau dia punya sekolah).
     * Guru dan school_admin di sekolah yang sama otomatis saling lihat
     * materi sekolah masing-masing.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);

            if ($user->school_id) {
                $q->orWhere('school_id', $user->school_id);
            }
        });
    }

    /**
     * Hanya uploader-nya sendiri yang boleh hapus/edit — termasuk kalau
     * school_admin yang upload materi sekolah, cuma dia (bukan school_admin
     * lain atau guru) yang bisa hapus.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isSchoolMaterial(): bool
    {
        return $this->school_id !== null;
    }
}
