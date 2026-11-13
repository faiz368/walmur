<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Database bersama sempat memiliki dua header laporan:
     * `laporan_pekanan_kelas` (skema lama) dan `laporan_pekanan_kelas1`
     * (skema baru). Detail sudah menunjuk ke tabel baru. Migration ini
     * merapikan nama tabel tanpa menghapus data laporan yang sudah terisi.
     */
    public function up(): void
    {
        if (! Schema::hasTable('laporan_pekanan_kelas1')) {
            return;
        }

        if (Schema::hasTable('laporan_pekanan_kelas')) {
            if (Schema::hasColumn('laporan_pekanan_kelas', 'kelas_nama')) {
                throw new RuntimeException(
                    'Tabel laporan_pekanan_kelas sudah memakai skema baru; '
                    .'laporan_pekanan_kelas1 tidak bisa di-rename otomatis.'
                );
            }

            if (DB::table('laporan_pekanan_kelas')->count() > 0) {
                throw new RuntimeException(
                    'Tabel laporan_pekanan_kelas lama masih berisi data. '
                    .'Pindahkan datanya terlebih dahulu sebelum rename.'
                );
            }

            Schema::drop('laporan_pekanan_kelas');
        }

        Schema::rename('laporan_pekanan_kelas1', 'laporan_pekanan_kelas');

        $this->arahkanForeignKeyDetailKeHeaderBaru();
    }

    public function down(): void
    {
        // Rename ini sengaja tidak dibalik otomatis agar data laporan yang
        // sudah terisi tidak kembali ke nama tabel sementara.
    }

    private function arahkanForeignKeyDetailKeHeaderBaru(): void
    {
        if (! Schema::hasTable('laporan_pekanan_kelas_details')) {
            return;
        }

        $foreignKey = collect(Schema::getForeignKeys('laporan_pekanan_kelas_details'))
            ->first(fn (array $fk) => $fk['columns'] === ['laporan_pekanan_id']);

        if ($foreignKey !== null) {
            Schema::table('laporan_pekanan_kelas_details', function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey['name']);
            });
        }

        Schema::table('laporan_pekanan_kelas_details', function (Blueprint $table) {
            $table->foreign('laporan_pekanan_id')
                ->references('id')
                ->on('laporan_pekanan_kelas')
                ->cascadeOnDelete();
        });
    }
};
