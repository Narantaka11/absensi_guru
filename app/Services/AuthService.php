<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Proses login: validasi kredensial, buat token Sanctum.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, string $deviceName = 'flutter-client'): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        // Hapus token lama pada device yang sama agar tidak menumpuk
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $this->formatUser($user),
        ];
    }

    /**
     * Proses logout: hapus token aktif saat ini.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Ambil data profil user yang sedang login.
     */
    public function me(User $user): array
    {
        return $this->formatUser($user);
    }

    /**
     * Format data user untuk response JSON yang konsisten.
     */
    public function formatUser(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];
    }
}
