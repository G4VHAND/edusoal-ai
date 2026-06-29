<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price_monthly', 'price_yearly',
        'max_teachers', 'quota_per_month', 'max_questions_per_generate',
        'allow_image_upload', 'allow_export_word', 'allow_export_pdf',
        'allow_all_providers', 'features', 'is_active',
    ];

    protected $casts = [
        'allow_image_upload'    => 'boolean',
        'allow_export_word'     => 'boolean',
        'allow_export_pdf'      => 'boolean',
        'allow_all_providers'   => 'boolean',
        'is_active'             => 'boolean',
        'features'              => 'array',
    ];

    public function schoolSubscriptions()
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isUnlimitedTeachers(): bool
    {
        return $this->max_teachers === -1;
    }

    public function isUnlimitedQuota(): bool
    {
        return $this->quota_per_month === -1;
    }

    public function formattedPriceMonthly(): string
    {
        if ($this->price_monthly === 0) return 'Gratis';
        return 'Rp ' . number_format($this->price_monthly, 0, ',', '.');
    }
}
