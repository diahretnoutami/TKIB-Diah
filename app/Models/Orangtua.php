<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Orangtua extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'orangtua';
    protected $primaryKey = 'id_ortu';

    protected $fillable = [
        'namaortu',
        'pekerjaan',
        'nohp',
        'alamat',
        'fcm_token', // Pastikan ini juga sudah ada
        'id_user',
    ];

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_ortu', 'id_ortu');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function routeNotificationForFcm(): string|array
    {
        // Mengembalikan nilai fcm_token dari kolom di tabel orangtua
        return $this->fcm_token;
    }
}