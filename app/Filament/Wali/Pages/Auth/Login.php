<?php

namespace App\Filament\Wali\Pages\Auth;

use App\Models\Santri;
use App\Models\User;
use App\Support\AksesLog;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login wali murid: SATU field email, TANPA password.
 *
 * Alur ini meniru halaman /laporan-ortu/wali milik StudentHub: wali cukup
 * mengetik email anaknya, lalu sistem membuat sesi sebagai akun `users`
 * anak tersebut. Karena tidak ada password yang dicek, tiga lapis proteksi
 * dipasang:
 *
 *   1. Query dibatasi `role = 'murid'`, jadi email guru/staff/admin yang
 *      diketik wali akan ditolak sama seperti email yang tidak dikenal.
 *   2. Rate limit 5 percobaan per menit per IP (dari WithRateLimiting).
 *   3. Setiap percobaan — berhasil maupun gagal — dicatat di tabel
 *      `wali_access_logs`.
 */
class Login extends BaseLogin
{
    protected string $view = 'filament.wali.pages.login';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public ?string $emailTerakhirDiketik = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')
                    ->label('Email Murid')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes([
                        'placeholder' => 'Contoh: nama@mail.anak.sch.id',
                        'class' => 'wali-login-input',
                    ]),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var string $email */
        $email = mb_strtolower(trim($data['email']));

        $user = User::query()
            ->where('email', $email)
            ->where('role', User::ROLE_MURID)
            ->orderBy('id')
            ->first();

        if ($user === null) {
            AksesLog::catat(
                emailInput: $email,
                user: null,
                berhasil: false,
            );

            throw ValidationException::withMessages([
                'data.email' => 'Email murid tidak ditemukan. Silakan periksa kembali penulisan email anak Anda.',
            ]);
        }

        $santri = $user->getSantriProfile();

        // Login tetap BERHASIL meski profil tidak ada; wali diarahkan ke layar
        // ProfilTidakDitemukan supaya bisa menghubungi pihak kesiswaan. 2 dari
        // 148 akun murid berada dalam kondisi ini (nama kembar atau tanpa data).
        AksesLog::catat(
            emailInput: $email,
            user: $user,
            berhasil: true,
            santri: $santri,
        );

        Auth::guard('web')->login($user, remember: true);

        session()->regenerate();

        $this->emailTerakhirDiketik = $email;

        return app(LoginResponse::class);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        $waktu = $exception->secondsUntilAvailable >= 60
            ? $exception->minutesUntilAvailable.' menit'
            : $exception->secondsUntilAvailable.' detik';

        return Notification::make()
            ->title('Terlalu banyak percobaan')
            ->body("Silakan coba lagi dalam {$waktu}. Demi keamanan data anak, percobaan dibatasi 5 kali per menit.")
            ->danger();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Email murid tidak ditemukan. Silakan periksa kembali penulisan email anak Anda.',
        ]);
    }

    /**
     * Helper untuk halaman lain: cari profil santri berdasarkan email,
     * tanpa bergantung pada state Livewire.
     */
    public static function profilSantriDariEmail(?string $email): ?Santri
    {
        if (blank($email)) {
            return null;
        }

        return User::query()
            ->where('email', mb_strtolower(trim($email)))
            ->where('role', User::ROLE_MURID)
            ->orderBy('id')
            ->first()?->getSantriProfile();
    }
}
