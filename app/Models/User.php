<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function guru()
    {
        return $this->hasOne(\App\Models\Guru::class, 'id_user');
    }

    public function ortu()
    {
        return $this->hasOne(\App\Models\Orangtua::class, 'id_user');
    }
    public function getRelatedIdAttribute()
    {
        if ($this->role === 'ortu' && $this->ortu) {
            return $this->ortu->id_ortu;
        }
        if ($this->role === 'guru' && $this->guru) {
            return $this->guru->id_guru;
        }
        return null;
    }
}