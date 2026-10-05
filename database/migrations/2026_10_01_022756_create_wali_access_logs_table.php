<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wali_access_logs', function (Blueprint $table) {
            $table->id();

            // Akun murid yang berhasil login, null bila email tidak dikenal.
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Email yang diketik wali murid, tetap disimpan walau gagal.
            $table->string('email_input', 255)->nullable();

            // Santri yang profilnya ditemukan, null bila akun yatim.
            $table->unsignedBigInteger('santri_id')->nullable()->index();

            $table->boolean('berhasil')->default(false);

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_access_logs');
    }
};
