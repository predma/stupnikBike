<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Fillable(['name', 'email', 'email_verified_at', 'email_verification_code_hash', 'email_verification_expires_at', 'email_verification_sent_at', 'password', 'role', 'phone', 'is_active', 'api_token'])]
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'email_verification_code_hash',
        'email_verification_expires_at',
        'email_verification_sent_at',
        'password',
        'role',
        'phone',
        'is_active',
        'api_token',
    ];

    #[Hidden(['password', 'remember_token', 'api_token', 'email_verification_code_hash'])]
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        'email_verification_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
