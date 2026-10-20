<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $tableRenames = [
        'exam_types' => 'jenis_tes',
        'faculties' => 'fakultas',
        'departments' => 'program_studi',
        'student_profiles' => 'profil_mahasiswa',
        'question_banks' => 'bank_soal',
        'skills' => 'keterampilan',
        'skill_parts' => 'bagian_soal',
        'passages' => 'materi_soal',
        'questions' => 'soal',
        'skill_passage_policies' => 'kebijakan_materi_soal',
        'skill_passage_media_rules' => 'aturan_media_materi_soal',
        'passage_media' => 'media_materi_soal',
        'exams' => 'ujian',
        'exam_sections' => 'bagian_ujian',
        'exam_section_questions' => 'soal_bagian_ujian',
        'exam_section_parts' => 'bagian_soal_ujian',
        'exam_schedules' => 'periode_ujian',
        'exam_schedule_slots' => 'slot_ujian',
        'exam_sessions' => 'sesi_ujian',
        'scoring_rules' => 'aturan_penilaian',
        'score_interpretations' => 'interpretasi_skor',
        'answers' => 'jawaban',
        'violation_logs' => 'catatan_pelanggaran',
        'certificates' => 'sertifikat',
        'settings' => 'pengaturan',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private array $columnRenames = [
        'jenis_tes' => [
            'name' => 'nama',
            'max_strikes' => 'batas_pelanggaran',
            'description' => 'deskripsi',
            'is_active' => 'aktif',
        ],
        'fakultas' => [
            'name' => 'nama',
            'code' => 'kode',
            'is_active' => 'aktif',
        ],
        'program_studi' => [
            'faculty_id' => 'fakultas_id',
            'name' => 'nama',
            'code' => 'kode',
            'is_active' => 'aktif',
        ],
        'profil_mahasiswa' => [
            'user_id' => 'pengguna_id',
            'faculty_id' => 'fakultas_id',
            'department_id' => 'program_studi_id',
            'batch_year' => 'tahun_angkatan',
            'identity_photo' => 'foto_identitas',
            'is_verified' => 'terverifikasi',
            'verified_at' => 'diverifikasi_pada',
            'verified_by' => 'verifikator_id',
            'verification_note' => 'catatan_verifikasi',
        ],
        'bank_soal' => [
            'exam_type_id' => 'jenis_tes_id',
            'name' => 'nama',
            'description' => 'deskripsi',
            'is_active' => 'aktif',
        ],
        'keterampilan' => [
            'code' => 'kode',
            'name' => 'nama',
            'description' => 'deskripsi',
            'order' => 'urutan',
            'is_active' => 'aktif',
        ],
        'bagian_soal' => [
            'question_bank_id' => 'bank_soal_id',
            'skill_id' => 'keterampilan_id',
            'name' => 'nama',
            'order' => 'urutan',
            'directions' => 'petunjuk',
            'is_active' => 'aktif',
        ],
        'materi_soal' => [
            'question_bank_id' => 'bank_soal_id',
            'title' => 'judul',
            'content_text' => 'isi_teks',
        ],
        'soal' => [
            'question_bank_id' => 'bank_soal_id',
            'passage_id' => 'materi_soal_id',
            'skill_id' => 'keterampilan_id',
            'skill_part_id' => 'bagian_soal_id',
            'question_text' => 'teks_soal',
            'option_a' => 'pilihan_a',
            'option_b' => 'pilihan_b',
            'option_c' => 'pilihan_c',
            'option_d' => 'pilihan_d',
            'correct_answer' => 'kunci_jawaban',
            'order' => 'urutan',
            'created_by' => 'pembuat_id',
            'updated_by' => 'pengubah_id',
            'reviewed_by' => 'peninjau_id',
            'reviewed_at' => 'ditinjau_pada',
            'review_note' => 'catatan_tinjauan',
        ],
        'kebijakan_materi_soal' => [
            'question_bank_id' => 'bank_soal_id',
            'skill_id' => 'keterampilan_id',
            'passage_requirement' => 'ketentuan_materi',
        ],
        'aturan_media_materi_soal' => [
            'skill_passage_policy_id' => 'kebijakan_materi_soal_id',
            'media_type' => 'jenis_media',
            'requirement' => 'ketentuan',
        ],
        'media_materi_soal' => [
            'passage_id' => 'materi_soal_id',
            'media_type' => 'jenis_media',
            'file_path' => 'lokasi_berkas',
            'original_name' => 'nama_asli',
            'mime_type' => 'tipe_mime',
            'size_bytes' => 'ukuran_bita',
            'duration_seconds' => 'durasi_detik',
            'sort_order' => 'urutan',
        ],
        'ujian' => [
            'exam_type_id' => 'jenis_tes_id',
            'title' => 'judul',
            'description' => 'deskripsi',
            'duration_minutes' => 'durasi_menit',
            'is_active' => 'aktif',
        ],
        'bagian_ujian' => [
            'exam_id' => 'ujian_id',
            'question_bank_id' => 'bank_soal_id',
            'skill_id' => 'keterampilan_id',
            'title' => 'judul',
            'order' => 'urutan',
            'total_questions' => 'jumlah_soal',
        ],
        'soal_bagian_ujian' => [
            'exam_section_id' => 'bagian_ujian_id',
            'question_id' => 'soal_id',
            'order' => 'urutan',
        ],
        'bagian_soal_ujian' => [
            'exam_section_id' => 'bagian_ujian_id',
            'skill_part_id' => 'bagian_soal_id',
            'order' => 'urutan',
        ],
        'periode_ujian' => [
            'exam_id' => 'ujian_id',
            'title' => 'judul',
            'start_date' => 'tanggal_mulai',
            'end_date' => 'tanggal_selesai',
            'is_active' => 'aktif',
        ],
        'slot_ujian' => [
            'exam_schedule_id' => 'periode_ujian_id',
            'date' => 'tanggal',
            'start_time' => 'waktu_mulai',
            'end_time' => 'waktu_selesai',
            'late_tolerance_minutes' => 'toleransi_keterlambatan_menit',
            'max_participants' => 'kapasitas_peserta',
            'is_active' => 'aktif',
        ],
        'sesi_ujian' => [
            'exam_schedule_slot_id' => 'slot_ujian_id',
            'user_id' => 'pengguna_id',
            'started_at' => 'dimulai_pada',
            'submitted_at' => 'diserahkan_pada',
            'terminated_at' => 'dihentikan_pada',
            'termination_reason' => 'alasan_penghentian',
            'current_section_id' => 'bagian_aktif_id',
            'last_heartbeat_at' => 'keaktifan_terakhir_pada',
            'review_status' => 'status_tinjauan',
            'review_note' => 'catatan_tinjauan',
            'reviewed_by' => 'peninjau_id',
            'reviewed_at' => 'ditinjau_pada',
            'score_reading' => 'skor_membaca',
            'score_listening' => 'skor_menyimak',
            'score_speaking' => 'skor_berbicara',
            'score_writing' => 'skor_menulis',
            'score_total' => 'skor_total',
            'is_flagged' => 'ditandai',
            'flag_reason' => 'alasan_penandaan',
            'violation_strikes' => 'jumlah_pelanggaran',
            'device_type' => 'jenis_perangkat',
            'device_user_agent' => 'agen_pengguna_perangkat',
            'selfie_path' => 'lokasi_swafoto',
            'selfie_taken_at' => 'swafoto_diambil_pada',
        ],
        'aturan_penilaian' => [
            'exam_type_id' => 'jenis_tes_id',
            'section_skill' => 'kode_keterampilan',
            'conversion_table' => 'tabel_konversi',
            'max_raw' => 'skor_mentah_maksimum',
            'max_scaled' => 'skor_skala_maksimum',
        ],
        'interpretasi_skor' => [
            'exam_type_id' => 'jenis_tes_id',
            'min_score' => 'skor_minimum',
            'max_score' => 'skor_maksimum',
            'cefr_level' => 'tingkat_cefr',
            'level_label' => 'label_tingkat',
            'is_passing' => 'lulus',
            'description' => 'deskripsi',
        ],
        'jawaban' => [
            'exam_session_id' => 'sesi_ujian_id',
            'question_id' => 'soal_id',
            'answer_text' => 'teks_jawaban',
            'is_correct' => 'benar',
        ],
        'catatan_pelanggaran' => [
            'exam_session_id' => 'sesi_ujian_id',
            'type' => 'jenis',
            'severity' => 'tingkat',
            'description' => 'deskripsi',
            'strike_count' => 'jumlah_pelanggaran',
        ],
        'sertifikat' => [
            'exam_session_id' => 'sesi_ujian_id',
            'certificate_number' => 'nomor_sertifikat',
            'issued_at' => 'diterbitkan_pada',
            'valid_until' => 'berlaku_hingga',
        ],
        'pengaturan' => [
            'key' => 'kunci',
            'value' => 'nilai',
            'group' => 'kelompok',
        ],
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->tableRenames as $oldTable => $newTable) {
                if (Schema::hasTable($oldTable) && ! Schema::hasTable($newTable)) {
                    Schema::rename($oldTable, $newTable);
                }
            }

            foreach ($this->columnRenames as $table => $columns) {
                $this->renameColumns($table, $columns);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->columnRenames as $table => $columns) {
                $this->renameColumns($table, array_flip($columns));
            }

            foreach (array_reverse($this->tableRenames, true) as $oldTable => $newTable) {
                if (Schema::hasTable($newTable) && ! Schema::hasTable($oldTable)) {
                    Schema::rename($newTable, $oldTable);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function renameColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $oldColumn => $newColumn) {
            if (Schema::hasColumn($table, $oldColumn) && ! Schema::hasColumn($table, $newColumn)) {
                Schema::table($table, function ($blueprint) use ($oldColumn, $newColumn): void {
                    $blueprint->renameColumn($oldColumn, $newColumn);
                });
            }
        }
    }
};
