<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pertemuan extends Model
{
    use HasFactory;

    protected $table = 'pertemuan'; // Pastikan nama tabel benar
    protected $primaryKey = 'id_p'; // Pastikan primary key benar

    protected $fillable = [
        'noinduk',
        'id_guru',
        'id_ortu',
        'tglpengajuan',
        'tglpertemuan',
        'jampertemuan',
        'deskripsi',
        'status',
        'alasan',
    ];

    // Relasi ke Siswa
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'noinduk', 'noinduk');
    }

    // Relasi ke Guru
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    // Relasi ke OrangTua (jika diperlukan, meskipun id_ortu sudah ada di sini)
    public function orangtua()
    {
        return $this->belongsTo(OrangTua::class, 'id_ortu', 'id_ortu');
    }
}