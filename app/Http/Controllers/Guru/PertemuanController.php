<?php

namespace App\Http\Controllers\Guru; // Namespace yang benar untuk controller guru

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pertemuan; // Model Pertemuan
use App\Models\TahunAjaran; // Model TahunAjaran untuk filter aktif
use App\Models\OrangTua; // <<< Tambahkan ini: Model OrangTua untuk notifikasi
use App\Notifications\OrtuNotification; // <<< Tambahkan ini: Kelas Notifikasi kustom
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang sedang login
use Illuminate\Support\Facades\Validator; // Untuk validasi input
use Illuminate\Support\Facades\Session; // Untuk flash messages
use Illuminate\Support\Facades\Log; // <<< Tambahkan ini: Untuk logging notifikasi

class PertemuanController extends Controller
{
    /**
     * Menampilkan daftar pengajuan pertemuan yang masuk untuk guru yang sedang login.
     * Data difilter berdasarkan tahun ajaran aktif dan siswa yang diajar oleh guru tersebut.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        // Mendapatkan objek Guru yang sedang login melalui relasi user
        $guru = Auth::user()->guru;

        // Jika user tidak terautentikasi sebagai guru atau data guru tidak ditemukan,
        // redirect ke halaman login guru dengan pesan error.
        if (!$guru) {
            return redirect()->route('guru.login')->with('error', 'Anda tidak terautentikasi sebagai guru atau data guru tidak ditemukan.');
        }

        $id_guru = $guru->id_guru; // Mengambil ID guru dari objek guru yang terautentikasi

        // Mencari tahun ajaran yang sedang aktif
        $tahunAjaranAktif = TahunAjaran::where('is_active', true)->first();

        $pertemuan = collect(); // Menginisialisasi koleksi kosong untuk data pertemuan

        // Jika tahun ajaran aktif ditemukan, baru lakukan query data pertemuan
        if ($tahunAjaranAktif) {
            $pertemuan = Pertemuan::where('id_guru', $id_guru)
                                    // Menggunakan query whereHas yang kamu berikan
                                    ->whereHas('siswa.kelasSiswaAktif.kelas', function ($q) use ($tahunAjaranAktif) {
                                        $q->where('id_ta', $tahunAjaranAktif->id_ta);
                                    })
                                    ->with(['siswa.orangtua', 'siswa.kelasSiswaAktif.kelas'])
                                    ->orderBy('tglpengajuan', 'desc') // Mengurutkan berdasarkan tanggal pengajuan terbaru
                                    ->get();
        }

        // Mengembalikan view 'guru.pertemuan'
        return view('guru.pertemuan', compact('pertemuan', 'tahunAjaranAktif'));
    }

    /**
     * Menampilkan detail satu pengajuan pertemuan berdasarkan ID pertemuan.
     * Guru hanya dapat melihat detail pertemuan yang ditujukan kepadanya.
     *
     * @param  int  $id_p ID Pertemuan
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id_p)
    {
        // Mendapatkan objek Guru yang sedang login melalui relasi user
        $guru = Auth::user()->guru;

        // Jika user tidak terautentikasi sebagai guru atau data guru tidak ditemukan,
        // redirect ke halaman login guru dengan pesan error.
        if (!$guru) {
            return redirect()->route('guru.login')->with('error', 'Anda tidak terautentikasi sebagai guru atau data guru tidak ditemukan.');
        }

        // Mencari detail pertemuan berdasarkan ID dan memastikan itu milik guru yang login
        $pertemuan = Pertemuan::where('id_p', $id_p)
                                ->where('id_guru', $guru->id_guru) // Memastikan pertemuan milik guru ini
                                ->with(['siswa.orangtua', 'siswa.kelasSiswaAktif.kelas']) // Menggunakan eager load yang kamu berikan
                                ->first();

        // Jika pertemuan tidak ditemukan atau guru tidak memiliki akses, redirect ke daftar pertemuan
        if (!$pertemuan) {
            return redirect()->route('guru.pertemuan.index')->with('error', 'Pertemuan tidak ditemukan atau Anda tidak memiliki akses untuk melihatnya.');
        }

        // Mengembalikan view 'guru.pertemuansedit' dengan data pertemuan
        return view('guru.pertemuansdetail', compact('pertemuan'));
    }

    /**
     * Memperbarui status pengajuan pertemuan (Diproses, Diterima, Ditolak, Selesai)
     * dan/atau alasan penolakan oleh guru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id_p ID Pertemuan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id_p)
    {
        // Mendapatkan objek Guru yang sedang login melalui relasi user
        $guru = Auth::user()->guru;

        // Jika user tidak terautentikasi sebagai guru atau data guru tidak ditemukan,
        // redirect ke halaman login guru dengan pesan error.
        if (!$guru) {
            return redirect()->route('guru.login')->with('error', 'Anda tidak terautentikasi sebagai guru atau data guru tidak ditemukan.');
        }

        // Mencari pertemuan yang akan diperbarui berdasarkan ID dan memastikan itu milik guru yang login
        $pertemuan = Pertemuan::where('id_p', $id_p)
                                ->where('id_guru', $guru->id_guru) // Memastikan pertemuan milik guru ini
                                ->first();

        // Jika pertemuan tidak ditemukan atau guru tidak memiliki akses, redirect ke daftar pertemuan
        if (!$pertemuan) {
            return redirect()->route('pertemuan.index')->with('error', 'Pertemuan tidak ditemukan atau Anda tidak memiliki akses untuk mengubahnya.');
        }

        // Validasi input dari form
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Diproses,Diterima,Ditolak,Selesai', // Status wajib dan harus salah satu dari opsi yang ditentukan
            'alasan' => 'nullable|string|max:1000', // Alasan boleh kosong, tapi jika ada harus string
        ]);

        // Jika validasi gagal, kembalikan ke halaman sebelumnya dengan error dan input lama
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Memperbarui status dan alasan pertemuan
            $pertemuan->status = $request->status;
            // Jika statusnya 'Ditolak', simpan alasan. Jika status lain, set alasan menjadi null.
            $pertemuan->alasan = ($request->status == 'Ditolak') ? ($request->alasan ?? null) : null;

            // Validasi tambahan: jika status 'Ditolak' tapi alasan kosong
            if ($request->status == 'Ditolak' && empty($request->alasan)) {
                 return redirect()->back()->with('error', 'Alasan wajib diisi jika status Ditolak.')->withInput();
            }

            $pertemuan->save(); // Menyimpan perubahan ke database

            // --- LOGIKA PENGIRIMAN NOTIFIKASI KE ORANG TUA ---
            // Memuat ulang relasi siswa dan orangtua untuk memastikan data terbaru tersedia untuk notifikasi
            $pertemuan->load('siswa.orangtua'); // Memuat relasi siswa dan orangtua dari pertemuan

            $ortu = $pertemuan->siswa->orangtua; // Mendapatkan objek OrangTua dari relasi siswa

            // Memeriksa apakah objek orang tua ditemukan dan memiliki FCM token
            if ($ortu && $ortu->fcm_token) {
                // Menentukan judul dan isi pesan notifikasi
                $title = "Update Status Pertemuan Siswa " . ($pertemuan->siswa->nama ?? 'Nama Siswa');
                $body = "Status pengajuan pertemuan Anda untuk siswa " . ($pertemuan->siswa->nama ?? 'Nama Siswa') . " telah diperbarui menjadi " . $pertemuan->status . ".";

                // Menyiapkan data tambahan yang akan dikirim bersama notifikasi (untuk penanganan di aplikasi mobile)
                $data = [
                    'type' => 'pertemuan_status_update',
                    'pertemuan_id' => $pertemuan->id_p,
                    'status_baru' => $pertemuan->status,
                    'siswa_nama' => ($pertemuan->siswa->nama ?? '')
                ];

                // Mengirim notifikasi menggunakan sistem notifikasi Laravel
                $ortu->notify(new OrtuNotification($title, $body, $data));

                // Mencatat informasi pengiriman notifikasi ke log Laravel
                Log::info('Notifikasi pertemuan dikirim', ['id_ortu' => $ortu->id_ortu, 'fcm_token' => $ortu->fcm_token, 'title' => $title]);
            } else {
                // Mencatat peringatan jika notifikasi tidak dapat dikirim (misalnya token tidak ada)
                Log::warning('Notifikasi pertemuan gagal dikirim: Orang tua atau FCM token tidak ditemukan', [
                    'id_ortu' => $pertemuan->id_ortu,
                    'ortu_exists' => (bool)$ortu,
                    'fcm_token_exists' => (bool)($ortu->fcm_token ?? null)
                ]);
            }
            // --- AKHIR LOGIKA NOTIFIKASI ---

            Session::flash('success', 'Status pertemuan berhasil diperbarui.'); // Menampilkan pesan sukses
            // Redirect kembali ke halaman detail pertemuan setelah update
            return redirect()->route('pertemuan.show', $pertemuan->id_p);

        } catch (\Exception $e) {
            // Menangani error jika terjadi masalah saat menyimpan atau mengirim notifikasi
            Log::error('Error saat update status pertemuan dan kirim notifikasi: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Gagal memperbarui status pertemuan: ' . $e->getMessage())->withInput();
        }
    }
}