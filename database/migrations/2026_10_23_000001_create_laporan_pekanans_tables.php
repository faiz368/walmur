<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan pekanan tahfizh berbentuk header per kelas/periode + detail per
     * santri. Dibuat dengan guard `hasTable()` karena database dipakai bersama
     * StudentHub dan dimigrasikan lewat tabel `wali_migrations`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('laporan_pekanan_kelas')) {
            Schema::create('laporan_pekanan_kelas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
                $table->string('kelas_nama');
                $table->date('periode_mulai');
                $table->date('periode_selesai');
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->unique(
                    ['kelas_id', 'periode_mulai', 'periode_selesai'],
                    'laporan_pekanan_kelas_periode_unique'
                );
                $table->index(
                    ['status', 'periode_mulai'],
                    'laporan_pekanan_kelas_status_periode_index'
                );
            });
        }

        if (! Schema::hasTable('laporan_pekanan_kelas_details')) {
            Schema::create('laporan_pekanan_kelas_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laporan_pekanan_id')
                    ->constrained('laporan_pekanan_kelas')
                    ->cascadeOnDelete();
                $table->foreignId('santri_id')
                    ->nullable()
                    ->constrained('santris')
                    ->nullOnDelete();
                $table->string('nama_santri');
                $table->string('nis')->nullable();
                $table->string('grade')->nullable();
                $table->decimal('capaian_pekanan', 8, 2);
                $table->decimal('target_pekanan', 8, 2);
                $table->decimal('persentase', 8, 2);
                $table->decimal('total_hafalan_juz', 6, 2);
                $table->decimal('total_hafalan_halaman', 8, 2);
                $table->unsignedInteger('absensi_hadir')->default(0);
                $table->unsignedInteger('absensi_sakit')->default(0);
                $table->unsignedInteger('absensi_izin')->default(0);
                $table->unsignedInteger('absensi_alpa')->default(0);
                $table->unsignedInteger('absensi_tidak_setor')->default(0);
                $table->text('catatan')->nullable();
                $table->unsignedInteger('urutan')->default(0);
                $table->timestamps();

                $table->unique(
                    ['laporan_pekanan_id', 'santri_id'],
                    'laporan_pekanan_kelas_details_unique'
                );
                $table->index(
                    ['santri_id', 'laporan_pekanan_id'],
                    'laporan_pekanan_kelas_details_santri_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_pekanan_kelas_details');
        Schema::dropIfExists('laporan_pekanan_kelas');
    }
};
