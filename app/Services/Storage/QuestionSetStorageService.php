<?php

namespace App\Services\Storage;

use App\Services\Material\MaterialReaderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menangani semua baca/tulis/hapus file yang berhubungan dengan QuestionSet
 * & Question: materi upload guru, gambar materi, dan gambar per-soal.
 *
 * Sebelumnya logic ini tersebar di QuestionSetController (store, destroy,
 * uploadQuestionImage, dst) — dipindah ke sini supaya controller tidak perlu
 * tahu detail disk mana yang dipakai atau bagaimana cara ekstrak teks materi.
 *
 * Semua file disimpan di disk 'local' (private), bukan 'public', karena
 * materi/jawaban siswa tidak boleh bisa diakses langsung lewat URL.
 */
class QuestionSetStorageService
{
    public function __construct(
        private readonly MaterialReaderService $materialReader,
    ) {}

    /**
     * Simpan file materi (PDF/DOCX/TXT) dan langsung ekstrak teksnya
     * supaya siap dikirim ke AI.
     *
     * @return array{path: string, original_name: string, text: ?string}
     */
    public function storeMaterialFile(UploadedFile $file): array
    {
        $path = $file->store('materials', 'local');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'text' => $this->materialReader->extractText($path),
        ];
    }

    /**
     * Simpan gambar materi (dipakai AI sebagai referensi visual, beda
     * dengan gambar per-soal yang diupload guru belakangan).
     */
    public function storeMaterialImage(UploadedFile $file): string
    {
        return $file->store('material-images', 'local');
    }

    public function deleteMaterialFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Simpan gambar untuk satu soal. Kalau sebelumnya sudah ada gambar
     * lama, otomatis dihapus dulu supaya tidak jadi file sampah di disk.
     */
    public function storeQuestionImage(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $file->store('question-images', 'local');
    }

    public function deleteQuestionImage(string $path): void
    {
        Storage::disk('local')->delete($path);
    }

    public function questionImageExists(string $path): bool
    {
        return Storage::disk('local')->exists($path);
    }

    public function questionImageFullPath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }
}
