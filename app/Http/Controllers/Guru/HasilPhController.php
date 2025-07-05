<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\HasilPh;
use App\Models\PenilaianHarian;
use App\Models\KelasSiswa;
use App\Models\TahunAjaran;
use App\Models\Guru;
use App\Models\Siswa; // Tambahkan ini jika belum ada
use App\Models\OrangTua; // <<< PENTING: Tambahkan ini untuk notifikasi
use App\Notifications\OrtuNotification; // <<< PENTING: Tambahkan ini untuk notifikasi
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <<< PENTING: Tambahkan ini untuk logging
use Illuminate\Support\Facades\Session; // Untuk flash messages

class HasilPhController extends Controller
{
    public function index()
    {
        // Ambil data guru yang login melalui relasi user
        $guru = Auth::user()->guru;

        $taAktif = \App\Models\TahunAjaran::where('is_active', true)->first();

        $data = \App\Models\Siswa::whereHas('kelas', function ($query) use ($guru, $taAktif) {
            $query->where('id_guru', $guru->id_guru)
                ->where('id_ta', $taAktif->id_ta);
        })->where('status', 'A')->get();
        return view('guru.hph', compact('data'));
    }

    public function pilihTgl($noinduk)
    {
        $taAktif = TahunAjaran::where('is_active', true)->first();

        if (!$taAktif) {
            return redirect()->route('hph.index')->with('error', 'Tidak ada tahun ajaran aktif yang ditemukan.');
        }
        $kelasSiswa = KelasSiswa::where('noinduk', $noinduk)
                                ->whereHas('kelas', function ($query) use ($taAktif) {
                                    $query->where('id_ta', $taAktif->id_ta);
                                })
                                ->first();

        if (!$kelasSiswa) {
            return redirect()->route('hph.index')->with('error', 'Data kelas siswa tidak ditemukan untuk tahun ajaran aktif.');
        }

        $data = PenilaianHarian::selectRaw('MIN(id_ph) as id_ph, tanggal')
            ->where('id_ta', $taAktif->id_ta) // ← tambahkan ini
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('guru.hphinputtgl', [
            'id_kelas_siswa' => $kelasSiswa->id_kelas_siswa,
            'noinduk' => $noinduk,
            'data' => $data,
            'kelasSiswa' => $kelasSiswa
        ]);
    }

    public function inputHarian($tanggal, $id_kelas_siswa)
    {
        $penilaianPoin = PenilaianHarian::with(['alur', 'tema'])
            ->where('tanggal', $tanggal)
            ->get();

        $kelasSiswa = KelasSiswa::with('siswa')->findOrFail($id_kelas_siswa);

        $existingNilai = HasilPh::where('id_kelas_siswa', $id_kelas_siswa)
            ->whereIn('id_ph', $penilaianPoin->pluck('id_ph'))
            ->pluck('hasil', 'id_ph'); // hasilnya: [id_ph => hasil]

        return view('guru.hphinput', compact('penilaianPoin', 'kelasSiswa', 'tanggal', 'existingNilai'));
    }

    public function storeHarian(Request $request)
    {
        $request->validate([
            'hasil' => 'required|array',
            'id_kelas_siswa' => 'required|integer|exists:kelas_siswa,id_kelas_siswa',
            'noinduk' => 'required|string|exists:siswa,noinduk',
            'dokumentasi.*' => 'nullable|image|max:2048'
        ]);

        try {
            DB::beginTransaction(); // Memulai transaksi database

            foreach ($request->hasil as $id_ph => $nilai) {
                $path = null;

                if ($request->hasFile("dokumentasi.$id_ph")) {
                    $file = $request->file("dokumentasi.$id_ph");
                    $path = $file->store("dokumentasi_hph", 'public');
                }

                $dataToSave = ['hasil' => $nilai];
                if ($path) {
                    $dataToSave['dokumentasi'] = $path;
                }

                $existing = HasilPh::where('id_kelas_siswa', $request->id_kelas_siswa)
                    ->where('id_ph', $id_ph)
                    ->first();

                if ($existing) {
                    // Jika ada dokumentasi lama dan ada dokumentasi baru, hapus yang lama
                    if ($path && $existing->dokumentasi) {
                        Storage::disk('public')->delete($existing->dokumentasi);
                    }
                    $existing->update($dataToSave); // Menggunakan update untuk fillable
                } else {
                    HasilPh::create(array_merge([
                        'id_ph' => $id_ph,
                        'id_kelas_siswa' => $request->id_kelas_siswa
                    ], $dataToSave));
                }
            }

            DB::commit(); // Menyelesaikan transaksi jika semua berhasil

            // --- LOGIKA PENGIRIMAN NOTIFIKASI KE ORANG TUA ---
            $siswa = Siswa::where('noinduk', $request->noinduk)
                        ->with('orangtua') // Eager load relasi orangtua
                        ->first();

            if ($siswa && $siswa->orangtua && $siswa->orangtua->fcm_token) {
                $ortu = $siswa->orangtua;
                $title = "Penilaian Harian Baru/Diperbarui";
                $body = "Nilai harian siswa " . ($siswa->nama ?? 'Nama Siswa') . " telah diinput/diperbarui.";
                $data = [
                    'type' => 'penilaian_harian_update',
                    'noinduk_siswa' => $siswa->noinduk,
                    'tanggal_penilaian' => $request->tanggal, // Asumsi tanggal ada di request
                    'siswa_nama' => ($siswa->nama ?? '')
                ];

                $ortu->notify(new OrtuNotification($title, $body, $data));
                Log::info('Notifikasi penilaian harian dipicu', ['id_ortu' => $ortu->id_ortu, 'fcm_token' => $ortu->fcm_token, 'title' => $title]);
            } else {
                Log::warning('Notifikasi penilaian harian gagal dipicu: Orang tua atau FCM token tidak ditemukan', [
                    'noinduk_siswa' => $request->noinduk,
                    'ortu_exists' => (bool)($siswa->orangtua ?? null),
                    'fcm_token_exists' => (bool)($siswa->orangtua->fcm_token ?? null)
                ]);
            }
            // --- AKHIR LOGIKA NOTIFIKASI ---

            return redirect()->route('guru.hphinputtgl', $request->noinduk)->with('success', 'Nilai berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack(); // Mengembalikan transaksi jika terjadi error
            Log::error('Error saat menyimpan nilai harian dan/atau kirim notifikasi: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal menyimpan nilai: ' . $e->getMessage())->withInput();
        }
    }
}