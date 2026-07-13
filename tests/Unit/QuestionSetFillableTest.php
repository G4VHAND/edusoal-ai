<?php

namespace Tests\Unit;

use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi untuk bug nyata yang pernah terjadi: kolom `source_reference`
 * sudah ada di migration & sudah diisi oleh job generate soal lewat
 * update([...]), tapi TIDAK ADA di $fillable model QuestionSet — jadi
 * Eloquent diam-diam membuang field itu setiap kali mass-assignment
 * (update()/create()), tanpa error apapun. Hasilnya selalu null,
 * padahal semua kode di sekitarnya (prompt AI, job, safety net) sudah
 * benar. Test ini memastikan field penting selalu bisa diisi via
 * mass-assignment, supaya bug serupa langsung ketahuan lewat test,
 * bukan lewat trial-error manual di browser.
 */
class QuestionSetFillableTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_reference_can_be_mass_assigned_via_update(): void
    {
        $user = User::factory()->create(['role' => 'individual']);

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'essay',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'pending',
        ]);

        $questionSet->update([
            'source_reference' => 'Buku Paket Biologi Kelas 11 Kurikulum Merdeka (Kemdikbud)',
        ]);

        $this->assertEquals(
            'Buku Paket Biologi Kelas 11 Kurikulum Merdeka (Kemdikbud)',
            $questionSet->fresh()->source_reference
        );
    }

    public function test_source_reference_is_listed_in_fillable(): void
    {
        // Test paling langsung untuk mencegah regresi: kalau suatu saat
        // ada yang tidak sengaja menghapus 'source_reference' dari
        // $fillable lagi, test ini akan gagal duluan sebelum sempat jadi
        // bug tersembunyi di production.
        $questionSet = new QuestionSet;

        $this->assertContains('source_reference', $questionSet->getFillable());
    }
}
