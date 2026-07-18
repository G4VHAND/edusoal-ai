<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Batasi query ke template milik "ruang lingkup" user: kalau dia
     * school_admin, semua template sekolahnya; kalau individual, template
     * personalnya sendiri. Dipakai baik untuk listing (index) maupun untuk
     * "matikan default lain" saat set template baru jadi default.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->when(! $user->isSchoolAdmin(), fn ($q) => $q->where('user_id', $user->id));
    }

    /**
     * Apakah $user berhak mengelola (hapus/set default) template ini?
     * school_admin cuma boleh atas template sekolahnya sendiri; individual
     * cuma boleh atas template personalnya sendiri.
     */
    public function isOwnedBy(User $user): bool
    {
        return $user->isSchoolAdmin()
            ? $this->school_id === $user->school_id
            : $this->user_id === $user->id;
    }
}
