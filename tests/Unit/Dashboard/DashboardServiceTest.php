<?php

namespace Tests\Unit\Dashboard;

use App\Models\QuestionSet;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService;
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => 'individual',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        $qs = QuestionSet::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 5,
            'status' => 'completed',
            'is_ai_generated' => true,
        ], $attrs));

        if (isset($attrs['created_at'])) {
            $qs->created_at = $attrs['created_at'];
            $qs->save();
        }

        return $qs;
    }

    // ── Agregat dasar ────────────────────────────────────────────────────────

    public function test_aggregates_counts_correctly_across_types_and_difficulty(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['question_type' => 'multiple_choice', 'difficulty' => 'mudah', 'total_questions' => 5]);
        $this->makeQuestionSet($user, ['question_type' => 'essay', 'difficulty' => 'sulit', 'total_questions' => 3]);

        $data = $this->service->forUser($user, 'all');

        $this->assertEquals(2, $data['totalQuestionSets']);
        $this->assertEquals(8, $data['totalQuestions']);
        $this->assertEquals(1, $data['totalMultipleChoice']);
        $this->assertEquals(1, $data['totalEssay']);
        $this->assertEquals(1, $data['easyCount']);
        $this->assertEquals(1, $data['hardCount']);
    }

    public function test_only_counts_current_users_question_sets(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();
        $this->makeQuestionSet($user);
        $this->makeQuestionSet($otherUser);
        $this->makeQuestionSet($otherUser);

        $data = $this->service->forUser($user, 'all');

        $this->assertEquals(1, $data['totalQuestionSets']);
    }

    // ── Filter periode ───────────────────────────────────────────────────────

    public function test_7days_period_excludes_older_question_sets(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['created_at' => now()->subDays(2)]);
        $this->makeQuestionSet($user, ['created_at' => now()->subDays(20)]); // di luar 7 hari

        $data = $this->service->forUser($user, '7days');

        $this->assertEquals(1, $data['totalQuestionSets']);
    }

    public function test_year_period_excludes_question_sets_from_last_year(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['created_at' => now()]);
        $this->makeQuestionSet($user, ['created_at' => now()->subYear()]);

        $data = $this->service->forUser($user, 'year');

        $this->assertEquals(1, $data['totalQuestionSets']);
    }

    public function test_all_period_includes_everything_regardless_of_date(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['created_at' => now()->subYears(2)]);

        $data = $this->service->forUser($user, 'all');

        $this->assertEquals(1, $data['totalQuestionSets']);
    }

    // ── Statistik mata pelajaran ─────────────────────────────────────────────

    public function test_subject_stats_groups_and_sorts_by_count_descending(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['subject' => 'Biologi']);
        $this->makeQuestionSet($user, ['subject' => 'Matematika']);
        $this->makeQuestionSet($user, ['subject' => 'Matematika']);
        $this->makeQuestionSet($user, ['subject' => 'Matematika']);

        $data = $this->service->forUser($user, 'all');

        $this->assertEquals('Matematika', $data['subjectStats']->first()->subject);
        $this->assertEquals(3, $data['subjectStats']->first()->total);
    }

    public function test_top_subjects_is_limited_to_five(): void
    {
        $user = $this->makeUser();
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $subject) {
            $this->makeQuestionSet($user, ['subject' => $subject]);
        }

        $data = $this->service->forUser($user, 'all');

        $this->assertCount(5, $data['topSubjects']);
        $this->assertCount(7, $data['subjectStats']); // subjectStats sendiri tidak dipotong
    }

    // ── Aktivitas bulanan ────────────────────────────────────────────────────

    public function test_monthly_activity_groups_by_month_with_correct_label(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['created_at' => now()->setMonth(1)->setYear(2026)]);
        $this->makeQuestionSet($user, ['created_at' => now()->setMonth(1)->setYear(2026)]);
        $this->makeQuestionSet($user, ['created_at' => now()->setMonth(3)->setYear(2026)]);

        $data = $this->service->forUser($user, 'all');

        $this->assertContains('Jan', $data['monthlyLabels']);
        $this->assertContains('Mar', $data['monthlyLabels']);
        $this->assertEquals(2, $data['monthlyTotals'][array_search('Jan', $data['monthlyLabels'])]);
    }

    // ── Latest question sets ────────────────────────────────────────────────

    public function test_latest_question_sets_limited_to_five_most_recent(): void
    {
        $user = $this->makeUser();
        for ($i = 0; $i < 8; $i++) {
            $this->makeQuestionSet($user, ['title' => "Set {$i}"]);
        }

        $data = $this->service->forUser($user, 'all');

        $this->assertCount(5, $data['latestQuestionSets']);
    }

    public function test_returns_zeroed_stats_for_user_with_no_question_sets(): void
    {
        $user = $this->makeUser();

        $data = $this->service->forUser($user, 'all');

        $this->assertEquals(0, $data['totalQuestionSets']);
        $this->assertEquals(0, $data['totalQuestions']);
        $this->assertCount(0, $data['topSubjects']);
        $this->assertCount(0, $data['latestQuestionSets']);
    }
}
