<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\PenilaianHarian;
use App\Models\Hasilpm;
use App\Models\Siswa;
use App\Models\Hasilpb;
use App\Models\Hasilph;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function tanggalHarian($noinduk)
    {
        $siswa = \App\Models\Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data riwayat kelas siswa tidak ditemukan'
            ], 404);
        }

        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $penilaian = \App\Models\PenilaianHarian::with('tahunajaran')
            ->whereHas('hasilPenilaian', function ($query) use ($idKelasSiswaArray) {
                $query->whereIn('id_kelas_siswa', $idKelasSiswaArray);
            })
            ->orderBy('tanggal', 'desc')
            ->get();

        $groupedByTa = $penilaian->groupBy(function ($item) {
            return optional($item->tahunajaran)->tahunajaran ?? 'Lainnya';
        })
        ->map(function ($group) {
            return $group->pluck('tanggal')->unique()->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'Tanggal penilaian harian berhasil diambil',
            'penilaian_by_ta' => $groupedByTa
        ]);
    }

    public function harianByTanggal($noinduk, $tanggal)
    {
        // UBAH: Gunakan relasi 'semuaKelasSiswa'
        $siswa = \App\Models\Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data siswa atau kelas tidak ditemukan'
            ], 404);
        }

        // UBAH: Ambil semua ID kelas siswa
        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $hasilph = \App\Models\Hasilph::with(['penilaianHarian.alur', 'penilaianHarian.tema'])
            // UBAH: Gunakan whereIn
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->whereHas('penilaianHarian', function ($q) use ($tanggal) {
                $q->where('tanggal', $tanggal);
            })
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data penilaian harian berhasil diambil',
            'data' => $hasilph
        ]);
    }

    public function mingguList($noinduk)
    {
        // UBAH: Gunakan relasi 'semuaKelasSiswa'
        $siswa = \App\Models\Siswa::with('semuaKelasSiswa.kelas.tahunajaran')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Data siswa tidak ditemukan'], 404);
        }

        // UBAH: Ambil semua ID kelas siswa
        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $hasilMingguan = \App\Models\HasilPm::with('kelasSiswa.kelas.tahunajaran')
            // UBAH: Gunakan whereIn
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->get();

        $groupedByTa = $hasilMingguan->groupBy(function ($item) {
            return optional($item->kelasSiswa->kelas->tahunajaran)->tahunajaran ?? 'Lainnya';
        })
        ->map(function ($group) {
            return $group->pluck('minggu')->unique()->sort()->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'Daftar minggu penilaian berhasil diambil',
            'penilaian_by_ta' => $groupedByTa,
        ]);
    }

    public function mingguanByMinggu($noinduk, $minggu)
    {
        // UBAH: Gunakan relasi 'semuaKelasSiswa'
        $siswa = \App\Models\Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data siswa tidak ditemukan',
            ], 404);
        }

        // UBAH: Ambil semua ID kelas siswa
        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $hasilpm = \App\Models\HasilPm::with('alur')
            // UBAH: Gunakan whereIn
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->where('minggu', $minggu)
            ->get();

        return response()->json([
            'status' => true,
            'minggu' => $minggu,
            'penilaian' => $hasilpm,
        ]);
    }

    public function listBulan($noinduk)
    {
        // UBAH: Gunakan relasi 'semuaKelasSiswa'
        $siswa = Siswa::with('semuaKelasSiswa.kelas.tahunajaran')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data siswa atau kelas tidak ditemukan',
            ], 404);
        }

        // UBAH: Ambil semua ID kelas siswa
        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $hasilBulanan = \App\Models\Hasilpb::with('kelasSiswa.kelas.tahunajaran')
            // UBAH: Gunakan whereIn
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->get();

        $groupedByTa = $hasilBulanan->groupBy(function ($item) {
            return optional($item->kelasSiswa->kelas->tahunajaran)->tahunajaran ?? 'Lainnya';
        })
        ->map(function ($group) {
            return $group->pluck('bulan')->unique()->values();
        });

        return response()->json([
            'status' => true,
            'message' => 'Daftar bulan penilaian bulanan berhasil diambil',
            'penilaian_by_ta' => $groupedByTa,
        ]);
    }

    public function bulananByBulan($noinduk, $bulan)
    {
        // UBAH: Gunakan relasi 'semuaKelasSiswa'
        $siswa = Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data siswa atau kelas tidak ditemukan'
            ], 404);
        }

        // UBAH: Ambil semua ID kelas siswa
        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $hasilpb = \App\Models\Hasilpb::with('alur')
            // UBAH: Gunakan whereIn
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->where('bulan', $bulan)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data penilaian bulanan berhasil diambil',
            'data' => $hasilpb
        ]);
    }
}