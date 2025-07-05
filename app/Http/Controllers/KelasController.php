<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\TahunAjaran;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Kelas::with('guru', 'tahunajaran')->get();
        return view('admin.kelas', compact('data'));
    }

    public function create()
    {
        $guru = Guru::all();
        $tahunAjaran = TahunAjaran::all();
        return view('admin.kelascreate', compact('guru', 'tahunAjaran'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_ta' => 'required|exists:tahun_ajaran,id_ta',
            'id_guru' => 'required|exists:guru,id_guru',
            'nama_kelas' => 'required|string|max:255',
        ]);

        Kelas::create([
            'id_ta' => $request->id_ta,
            'id_guru' => $request->id_guru,
            'nama_kelas' => $request->nama_kelas,
        ]);

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil disimpan!');
    }

    public function edit(string $id_k)
    {
        $data = Kelas::find($id_k);
        $tahunAjaran = TahunAjaran::all();
        $guru = Guru::all();

        return view('admin.kelasedit', compact('data', 'tahunAjaran', 'guru'));
    }


    public function update(Request $request, string $id)
    {
        // Validasi data
        $request->validate([
            'id_ta' => 'required|exists:tahun_ajaran,id_ta',
            'id_guru' => 'required|exists:guru,id_guru',
            'nama_kelas' => 'required',
        ]);

        $kelas = Kelas::findOrFail($id);

        $kelas->update([
            'id_ta' => $request->id_ta,
            'id_guru' => $request->id_guru,
            'nama_kelas' => $request->nama_kelas,
        ]);

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui!');
    }

    public function destroy(string $id_k)
    {
        Kelas::destroy($id_k);
        return redirect()->route('kelas.index')->with('success', 'Data berhasil dihapus!');
    }

    public function kelolaSiswa($id_k)
    {
        // 1. Ambil data kelas yang ingin dikelola, sekalian ambil data relasinya (tahun ajaran).
        $kelas = Kelas::with('tahunajaran')->findOrFail($id_k);

        $id_ta_kelas_ini = $kelas->id_ta;

        $siswaDiKelas = $kelas->siswa;

        $calonSiswa = Siswa::where('status', 'A') // Hanya siswa aktif
            ->whereNotIn('noinduk', function ($query) use ($id_ta_kelas_ini) {
                $query->select('ks.noinduk')
                      ->from('kelas_siswa as ks')
                      ->join('kelas as k', 'ks.id_kelas', '=', 'k.id_k')
                      ->where('k.id_ta', $id_ta_kelas_ini);
            })
            ->orderBy('nama', 'asc') 
            ->get();
        
        return view('admin.kelassiswa', compact('kelas', 'siswaDiKelas', 'calonSiswa'));
    }

    public function tambahSiswaKeKelas(Request $request, $id_k)
    {
        $kelas = Kelas::findOrFail($id_k);

        $request->validate([
            'siswa' => 'required|array', 
            'siswa.*' => 'exists:siswa,noinduk' 
        ]);

        $kelas->siswa()->attach($request->siswa);

        return redirect()->back()->with('success', 'Siswa berhasil ditambahkan ke kelas.');
    }
    
    public function hapusSiswaDariKelas($id_k, $noinduk)
    {
        $kelas = Kelas::findOrFail($id_k);
        $kelas->siswa()->detach($noinduk);

        return redirect()->back()->with('success', 'Siswa berhasil dihapus dari kelas.');
    }
}