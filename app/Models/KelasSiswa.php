<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasSiswa extends Model
{
    protected $table = 'kelas_siswa';
    protected $primaryKey = 'id_kelas_siswa';
    public $timestamps = false;

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'noinduk', 'noinduk');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_k');
    }

    public function absen()
    {
        return $this->hasMany(Absen::class, 'id_kelas_siswa');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(\App\Models\TahunAjaran::class, 'id_ta', 'id_ta');
    }
}