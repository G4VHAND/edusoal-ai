<?php

namespace Tests\Feature\Dashboard;

use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Badge tren di card "Total Bank Soal" (bulan ini vs bulan lalu) harus
 * berdasarkan data asli — bukan angka rekaan. Test ini memastikan
 * perhitungannya benar untuk beberapa skenario.
 */
class DashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => 'individual',
            'email_verified_at' => now(),
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);
    }

    private function makeQuestionSet(User $user, \Carbon\Carbon $createdAt): QuestionSet
    {
        $qs = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ]);

        $qs->created_at = $createdAt;
        $qs->save();

        return $qs;
    }

    public function test_shows_positive_trend_when_this_month_has_more_than_last_month(): void
    {
        $user = $this->makeUser();

        $this->makeQuestionSet($user, now()->subMonthNoOverflow());
        $this->makeQuestionSet($user, now());
        $this->makeQuestionSet($user, now());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('+100% dari bulan lalu');
    }

    public function test_shows_new_count_when_last_month_has_no_data(): void
    {
        $user = $this->makeUser();

        $this->makeQuestionSet($user, now());
        $this->makeQuestionSet($user, now());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('2 dibuat bulan ini');
    }

    public function test_shows_negative_trend_when_this_month_has_less_than_last_month(): void
    {
        $user = $this->makeUser();

        $this->makeQuestionSet($user, now()->subMonthNoOverflow());
        $this->makeQuestionSet($user, now()->subMonthNoOverflow());
        $this->makeQuestionSet($user, now());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('-50% dari bulan lalu');
    }
}
