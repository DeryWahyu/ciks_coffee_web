<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Role constants.
     */
    const ROLE_PEMILIK = 'pemilik';
    const ROLE_KARYAWAN = 'karyawan';
    const ROLE_PENGGUNA = 'pengguna';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user is pemilik.
     */
    public function isPemilik(): bool
    {
        return $this->role === self::ROLE_PEMILIK;
    }

    /**
     * Check if user is karyawan.
     */
    public function isKaryawan(): bool
    {
        return $this->role === self::ROLE_KARYAWAN;
    }

    /**
     * Check if user is pengguna.
     */
    public function isPengguna(): bool
    {
        return $this->role === self::ROLE_PENGGUNA;
    }
}
