<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_teachers' => 1,
                'quota_per_month' => 10,
                'max_questions_per_generate' => 5,
                'allow_image_upload' => false,
                'allow_export_word' => false,
                'allow_export_pdf' => true,
                'allow_all_providers' => false,
                'features' => ['Generate soal pilihan ganda & essay', 'Export PDF', '1 provider AI (Gemini)'],
                'is_active' => true,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price_monthly' => 49000,
                'price_yearly' => 490000,
                'max_teachers' => 5,
                'quota_per_month' => 100,
                'max_questions_per_generate' => 20,
                'allow_image_upload' => false,
                'allow_export_word' => true,
                'allow_export_pdf' => true,
                'allow_all_providers' => true,
                'features' => ['Semua fitur Free', 'Export Word & PDF', 'Semua provider AI', 'Hingga 5 guru', '100 generate/bulan'],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 149000,
                'price_yearly' => 1490000,
                'max_teachers' => 20,
                'quota_per_month' => 500,
                'max_questions_per_generate' => 50,
                'allow_image_upload' => true,
                'allow_export_word' => true,
                'allow_export_pdf' => true,
                'allow_all_providers' => true,
                'features' => ['Semua fitur Basic', 'Upload gambar (Vision AI)', 'Hingga 20 guru', '500 generate/bulan', 'Prioritas support'],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 499000,
                'price_yearly' => 4990000,
                'max_teachers' => -1,
                'quota_per_month' => -1,
                'max_questions_per_generate' => 50,
                'allow_image_upload' => true,
                'allow_export_word' => true,
                'allow_export_pdf' => true,
                'allow_all_providers' => true,
                'features' => ['Semua fitur Pro', 'Guru tidak terbatas', 'Generate tidak terbatas', 'Dedicated support', 'Custom onboarding'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
