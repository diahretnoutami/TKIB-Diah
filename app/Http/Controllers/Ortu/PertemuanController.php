<?php

namespace App\Http\Controllers\Ortu; // Pastikan namespace ini benar

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pertemuan;
use App\Models\Siswa; // Pastikan model Siswa di-import
use App\Models\Orangtua; // Pastikan model Orangtua di-import
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Pastikan Log di-import
use Illuminate\Validation\ValidationException; // Pastikan ini di-import jika digunakan

class PertemuanController extends Controller // Nama kelas yang jelas
{
    /**
     * Menampilkan daftar pertemuan yang diajukan oleh orang tua yang sedang login,
     * difilter berdasarkan siswa tertentu.
     * Route: GET /api/ortu/pertemuan/{noinduk}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $noinduk Nomor Induk siswa yang akan difilter
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $noinduk) // <<< TAMBAH PARAMETER noinduk
    {
        $ortuUser = Auth::user();

        if (!$ortuUser || !isset($ortuUser->ortu)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Orang tua tidak terautentikasi atau ID orang tua tidak ditemukan.'
            ], 401);
        }

        $id_ortu = $ortuUser->ortu->id_ortu;

        $pertemuan = Pertemuan::where('id_ortu', $id_ortu)
                                ->where('noinduk', $noinduk) // <<< TAMBAH FILTER BERDASARKAN NOINDUK SISWA
                                ->with(['siswa.orangtua', 'siswa.kelasSiswaAktif.kelas.guru']) // Eager load relasi yang diperlukan
                                ->orderBy('tglpertemuan', 'desc') // Urutkan dari pertemuan terbaru
                                ->orderBy('jampertemuan', 'desc')
                                ->get();

        if ($pertemuan->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Belum ada data pertemuan yang Anda ajukan untuk siswa ini.', // Pesan lebih spesifik
                'data' => []
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data pertemuan berhasil diambil.',
            'data' => $pertemuan
        ], 200);
    }

    /**
     * Menyimpan pengajuan pertemuan baru dari orang tua.
     * Route: POST /api/ortu/pertemuan
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $ortuUser = Auth::user();

        if (!$ortuUser || !isset($ortuUser->ortu)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Orang tua tidak terautentikasi atau ID orang tua tidak ditemukan.'
            ], 401);
        }

        $id_ortu = $ortuUser->ortu->id_ortu;

        $validator = Validator::make($request->all(), [
            'noinduk_siswa' => 'required|string|exists:siswa,noinduk',
            'tglpertemuan'  => 'required|date|after_or_equal:today',
            'jampertemuan'  => 'required|date_format:H:i',
            'deskripsi'     => 'required|string|max:1000',
        ], [
            'noinduk_siswa.required'    => 'Nomor induk siswa wajib diisi.',
            'noinduk_siswa.exists'      => 'Siswa dengan nomor induk tersebut tidak ditemukan.',
            'tglpertemuan.required'     => 'Tanggal pertemuan wajib diisi.',
            'tglpertemuan.date'         => 'Format tanggal pertemuan tidak valid.',
            'tglpertemuan.after_or_equal' => 'Tanggal pertemuan tidak boleh di masa lalu.',
            'jampertemuan.required'     => 'Jam pertemuan wajib diisi.',
            'jampertemuan.date_format'  => 'Format jam pertemuan tidak valid (gunakan HH:MM).',
            'deskripsi.required'        => 'Deskripsi pertemuan wajib diisi.',
            'deskripsi.max'             => 'Deskripsi pertemuan maksimal 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $siswa = Siswa::with(['kelasSiswaAktif.kelas.guru'])
            ->where('noinduk', $request->noinduk_siswa)
            ->first();

        if (!$siswa || !$siswa->kelasSiswaAktif || !$siswa->kelasSiswaAktif->kelas || !$siswa->kelasSiswaAktif->kelas->guru) {
            return response()->json([
                'status' => false,
                'message' => 'Guru kelas untuk siswa ini tidak ditemukan atau siswa tidak memiliki kelas aktif. Tidak dapat mengajukan pertemuan.'
            ], 404);
        }

        $id_guru_kelas = $siswa->kelasSiswaAktif->kelas->guru->id_guru;

        try {
            // Buat entri pertemuan baru di database
            $pertemuan = Pertemuan::create([
                'noinduk'       => $request->noinduk_siswa,
                'id_guru'       => $id_guru_kelas,
                'id_ortu'       => $id_ortu,
                'tglpengajuan'  => now()->toDateString(), // Tanggal pengajuan otomatis hari ini
                'tglpertemuan'  => $request->tglpertemuan,
                'jampertemuan'  => $request->jampertemuan,
                'deskripsi'     => $request->deskripsi,
                'status'        => 'Diproses', // Status default saat pengajuan
                'alasan'        => null, // Alasan awalnya null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Pengajuan pertemuan berhasil disimpan. Menunggu konfirmasi guru.',
                'data' => $pertemuan
            ], 201); // 201 Created untuk resource baru

        } catch (\Exception $e) {
            // Tangani error jika terjadi masalah saat menyimpan ke database
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan pengajuan pertemuan: ' . $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Menyimpan atau memperbarui FCM token untuk user ortu yang sedang login.
     * Route: POST /api/ortu/save-fcm-token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveFcmToken(Request $request)
    {
        $ortuUser = Auth::user();

        if (!$ortuUser || !isset($ortuUser->ortu)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Orang tua tidak terautentikasi atau ID orang tua tidak ditemukan.'
            ], 401);
        }

        // Validasi input: fcm_token wajib diisi
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal untuk FCM token.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ortu = $ortuUser->ortu; // Dapatkan objek OrangTua yang terkait

            // Perbarui fcm_token
            $ortu->fcm_token = $request->fcm_token;
            $ortu->save();

            Log::info('FCM token berhasil disimpan/diperbarui.', ['id_ortu' => $ortu->id_ortu, 'fcm_token' => $request->fcm_token]);

            return response()->json([
                'status' => true,
                'message' => 'FCM token berhasil disimpan.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Gagal menyimpan FCM token: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan FCM token: ' . $e->getMessage()
            ], 500);
        }
    }
}