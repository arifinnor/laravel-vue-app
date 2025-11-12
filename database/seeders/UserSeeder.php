<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $secretLength = (int) config('fortify-options.two-factor-authentication.secret-length', 16);

        $provider = app(TwoFactorAuthenticationProvider::class);

        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt(
                $provider->generateSecretKey($secretLength)
            ),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
                json_encode(Collection::times(8, static fn () => RecoveryCode::generate())->all())
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }
}
