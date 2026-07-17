<?php

namespace Tests\Feature\BankSoal;

use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk bug nyata yang ditemukan: halaman /bank-soal memakai
 * paginate(20) di controller, tapi view-nya tidak pernah menampilkan
 * link halaman berikutnya — jadi bank soal ke-21 dst. tidak bisa
 * diakses sama sekali lewat UI walau datanya ada di database.
 */
class BankSoalIndexPaginationTest extends TestCase
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

    private function makeQuestionSets(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            QuestionSet::create([
                'user_id' => $user->id,
                'title' => "Bank Soal {$i}",
                'subject' => 'Matematika',
                'grade' => 'Kelas 9 SMP',
                'topic' => 'Aljabar',
                'question_type' => 'multiple_choice',
                'difficulty' => 'sedang',
                'total_questions' => 1,
                'status' => 'completed',
            ]);
        }
    }

    public function test_pagination_links_appear_when_more_than_one_page(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSets($user, 25); // > 20 per halaman

        $response = $this->actingAs($user)->get('/bank-soal');

        $response->assertOk();
        // Laravel pagination bawaan render "Next" (screen-reader text) dan
        // aria-label "Pagination Navigation" — cek yang pasti ada di HTML-nya.
        $response->assertSee('Next', false);
    }

    public function test_pagination_links_hidden_when_only_one_page(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSets($user, 5); // < 20, cuma 1 halaman

        $response = $this->actingAs($user)->get('/bank-soal');

        $response->assertOk();
        $response->assertDontSee('Next', false);
    }

    public function test_second_page_shows_remaining_items(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSets($user, 25);

        $response = $this->actingAs($user)->get('/bank-soal?page=2');

        $response->assertOk();
        // Halaman 2 harus menampilkan sisa 5 item (25 - 20).
        $response->assertSee('Bank Soal 0'); // item paling lama, ada di halaman 2 karena ->latest()
    }
}
