<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test memakai SQLite in-memory dengan skema minimal yang dibuat inline.
 *
 * Tidak memakai RefreshDatabase karena WALMUR sengaja tidak punya migration
 * untuk tabel StudentHub (`users`, `santris`, dst.) - tabel itu milik aplikasi
 * lain dan hanya dibaca.
 */
class LoginMuridTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->nullable();
            $table->string('nis')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('santris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('nis')->nullable();
            $table->string('nama_lengkap');
            $table->timestamps();
        });

        Schema::create('wali_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email_input')->nullable();
            $table->unsignedBigInteger('santri_id')->nullable();
            $table->boolean('berhasil');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    private function murid(string $email, ?int $santriId = null): User
    {
        $user = User::query()->forceCreate([
            'name' => 'Anak '.explode('@', $email)[0],
            'email' => $email,
            'role' => 'murid',
        ]);

        if ($santriId !== null) {
            Santri::query()->forceCreate([
                'user_id' => $user->id,
                'nis' => '100'.$user->id,
                'nama_lengkap' => 'Santri '.$user->id,
            ]);
        }

        return $user;
    }

    public function test_hanya_role_murid_yang_boleh_masuk_panel(): void
    {
        $murid = $this->murid('anak@student.abudzar.sch.id', 1);
        $guru = User::query()->forceCreate([
            'name' => 'Guru',
            'email' => 'guru@abudzar.sch.id',
            'role' => 'guru',
        ]);

        $this->actingAs($murid);
        $this->assertTrue($murid->canAccessPanel(\Filament\Facades\Filament::getPanel('wali')));

        $this->assertFalse($guru->canAccessPanel(\Filament\Facades\Filament::getPanel('wali')));
    }

    public function test_role_null_ditolak(): void
    {
        $tanpaRole = User::query()->forceCreate([
            'name' => 'Tanpa Role',
            'email' => 'tanpa-role@abudzar.sch.id',
        ]);

        $this->assertFalse($tanpaRole->canAccessPanel(\Filament\Facades\Filament::getPanel('wali')));
    }

    public function test_email_murid_tertaut_masuk_dan_mencatat_audit(): void
    {
        $user = $this->murid('ahmad0331@student.abudzar.sch.id', 1);

        Livewire::test(\App\Filament\Wali\Pages\Auth\Login::class)
            ->fillForm(['email' => 'ahmad0331@student.abudzar.sch.id'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);

        $log = \DB::table('wali_access_logs')->latest('id')->first();
        $this->assertTrue((bool) $log->berhasil);
        $this->assertSame($user->id, (int) $log->user_id);
        $this->assertSame('ahmad0331@student.abudzar.sch.id', $log->email_input);
    }

    public function test_email_guru_ditolak_sama_dengan_email_tidak_dikenal(): void
    {
        $this->murid('anak@student.abudzar.sch.id', 1);
        User::query()->forceCreate([
            'name' => 'Guru',
            'email' => 'guru@abudzar.sch.id',
            'role' => 'guru',
        ]);

        Livewire::test(\App\Filament\Wali\Pages\Auth\Login::class)
            ->fillForm(['email' => 'guru@abudzar.sch.id'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();

        $log = \DB::table('wali_access_logs')->latest('id')->first();
        $this->assertFalse((bool) $log->berhasil);
        $this->assertNull($log->user_id);
    }

    public function test_email_tidak_dikenal_ditolak(): void
    {
        Livewire::test(\App\Filament\Wali\Pages\Auth\Login::class)
            ->fillForm(['email' => 'entah@student.abudzar.sch.id'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_email_kosong_ditolak(): void
    {
        Livewire::test(\App\Filament\Wali\Pages\Auth\Login::class)
            ->fillForm(['email' => ''])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    /**
     * Akun murid yang tidak tertaut ke `santris` tetap boleh login; wali
     * diarahkan ke layar pengingat, bukan diberi 403.
     */
    public function test_akun_tanpa_profil_tetap_berhasil_login(): void
    {
        $user = $this->murid('ghany1116@student.abudzar.sch.id');

        $this->assertNull($user->getSantriProfile());

        Livewire::test(\App\Filament\Wali\Pages\Auth\Login::class)
            ->fillForm(['email' => 'ghany1116@student.abudzar.sch.id'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_pencocokan_profil_lewat_nis_hanya_jika_persis(): void
    {
        $user = $this->murid('nis-fallback@student.abudzar.sch.id');
        $user->forceFill(['nis' => '2026001'])->save();

        Santri::query()->forceCreate([
            'nis' => '2026001',
            'nama_lengkap' => 'Santri Via NIS',
        ]);

        $this->assertSame('Santri Via NIS', $user->fresh()->getSantriProfile()?->nama_lengkap);

        $user->forceFill(['nis' => '202600'])->save();
        $this->assertNull($user->fresh()->getSantriProfile());
    }

    public function test_profil_ambiguous_dipilih_deterministik(): void
    {
        $user = $this->murid('ambiguous@student.abudzar.sch.id');

        $dua = collect([
            ['user_id' => $user->id, 'nama_lengkap' => 'Santri Kedua'],
            ['user_id' => $user->id, 'nama_lengkap' => 'Santri Pertama'],
        ])->map(fn ($baris) => Santri::query()->forceCreate($baris));

        $profil = $user->fresh()->getSantriProfile();

        $this->assertNotNull($profil);
        $this->assertSame($dua->min('id'), $profil->id);
        $this->assertSame($user->fresh()->getSantriProfile()->id, $profil->id);
    }
}