<?php

namespace App\Console\Commands;

use App\Models\SchoolSubscription;
use Illuminate\Console\Command;

/**
 * Tandai subscription sekolah yang sudah lewat tanggal ends_at sebagai
 * 'expired'. Ini TIDAK menentukan apakah quota masih bisa dipakai —
 * itu sudah dijaga langsung di query School::activeSubscription() lewat
 * filter tanggal. Command ini murni untuk kerapian data & akurasi
 * tampilan status di panel admin (supaya super admin lihat "expired",
 * bukan "trial"/"active" yang menyesatkan padahal aksesnya sudah diblokir).
 */
class ExpireSchoolSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Tandai subscription sekolah yang sudah lewat ends_at sebagai expired';

    public function handle(): int
    {
        $count = SchoolSubscription::whereIn('status', ['active', 'trial'])
            ->where('ends_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("{$count} subscription ditandai sebagai expired.");

        return self::SUCCESS;
    }
}
