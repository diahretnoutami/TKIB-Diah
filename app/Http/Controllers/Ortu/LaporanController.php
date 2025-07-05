<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Siswa;
// use App\Models\Semester; // Jika tidak dipakai, bisa dihapus
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Jika tidak dipakai untuk download, bisa dihapus

class LaporanController extends Controller
{
    /**
     * Fungsi untuk mendapatkan daftar laporan yang tersedia, dikelompokkan per tahun ajaran.
     */
    public function listLaporan($noinduk)
    {
        $siswa = Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Data riwayat kelas siswa tidak ditemukan'], 404);
        }

        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        // Ambil semua laporan dengan relasi yang dibutuhkan untuk grouping
        $laporan = Laporan::with([
                'hasilph.penilaianHarian.tahunajaran',
                'hasilph.penilaianHarian.alur'
            ])
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            ->get();

        // Buat struktur data yang unik berdasarkan tahun ajaran dan semester
        $reportsByTa = [];
        foreach ($laporan as $item) {
            // Pastikan relasi yang dibutuhkan ada untuk menghindari error
            if (isset($item->hasilph->penilaianHarian->tahunajaran) && isset($item->hasilph->penilaianHarian->alur)) {
                $ta = $item->hasilph->penilaianHarian->tahunajaran->tahunajaran;
                $semester = $item->hasilph->penilaianHarian->alur->semester;
                $id_ta = $item->hasilph->penilaianHarian->tahunajaran->id_ta;

                // Buat ID unik untuk setiap laporan (contoh: "2024/2025-1")
                $reportId = "{$ta}-{$semester}";
                
                if (!isset($reportsByTa[$ta])) {
                    $reportsByTa[$ta] = [];
                }

                // Cek agar tidak ada laporan duplikat (berdasarkan tahun ajaran dan semester)
                $isDuplicate = false;
                foreach ($reportsByTa[$ta] as $existingReport) {
                    if ($existingReport['id_ta'] == $id_ta && $existingReport['semester'] == $semester) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $reportsByTa[$ta][] = [
                        'nama_laporan' => "Laporan Semester {$semester}",
                        'id_ta' => $id_ta,
                        'semester' => $semester
                    ];
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Daftar laporan berhasil diambil',
            'laporan_by_ta' => $reportsByTa
        ]);
    }

    public function getLaporanDetail($noinduk, $id_ta, $semester)
    {
        $siswa = Siswa::with('semuaKelasSiswa')->where('noinduk', $noinduk)->first();

        if (!$siswa || $siswa->semuaKelasSiswa->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Data riwayat kelas siswa tidak ditemukan'], 404);
        }

        $idKelasSiswaArray = $siswa->semuaKelasSiswa->pluck('id_kelas_siswa');

        $laporan = Laporan::with([
            'cp',
                'deskripsi',
                'hasilph.penilaianHarian.alur', // Perbarui relasi untuk semester
                'hasilph.penilaianHarian.tema', // Tambahkan jika diperlukan
            ])
            ->whereIn('id_kelas_siswa', $idKelasSiswaArray)
            // Gunakan whereHas yang benar untuk tahun ajaran
            ->whereHas('hasilph.penilaianHarian', function ($q) use ($id_ta) {
                $q->where('id_ta', $id_ta);
            })
            // Gunakan whereHas yang benar untuk semester
            ->whereHas('hasilph.penilaianHarian.alur', function ($q) use ($semester) {
                $q->where('semester', $semester);
            })
            ->get();
            
        if ($laporan->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Detail laporan tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => true,
            'laporan' => $laporan
        ]);
    }

}