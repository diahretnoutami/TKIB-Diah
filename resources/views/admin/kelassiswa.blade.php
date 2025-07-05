@section('content')
    @include('layouts.header')

    <div class="main-content">
        <div class="container-fluid pt-7">
            {{-- Judul Halaman --}}
            <h1 class="text-success" style="margin-top: -100px; margin-bottom: 20px;">Kelola Kelas {{ $kelas->nama_kelas }}
                <br>
                <small class="text-muted" style="font-size: 1rem;">Tahun Ajaran:
                    {{ $kelas->tahunajaran->tahunajaran }}</small>
            </h1>

            {{-- Menampilkan pesan sukses dari controller --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            {{-- Bagian Tabel Siswa yang SUDAH ADA di Kelas --}}
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Daftar Siswa di Kelas Ini</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center; font-size: 16px;">No</th>
                                <th style="text-align: center; font-size: 16px;">Nomor Induk</th>
                                <th style="text-align: center; font-size: 16px;">Nama Siswa</th>
                                <th style="text-align: center; font-size: 16px;">Jenis Kelamin</th>
                                <th style="text-align: center; font-size: 16px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- DIUBAH: dari $data menjadi $siswaDiKelas. --}}
                            {{-- Menggunakan @forelse untuk menangani jika tidak ada siswa. --}}
                            @forelse ($siswaDiKelas as $siswa)
                                <tr>
                                    {{-- DIUBAH: dari $index + 1 menjadi $loop->iteration untuk penomoran --}}
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td style="text-align: center">{{ $siswa->noinduk }}</td>
                                    <td>{{ $siswa->nama }}</td> {{-- Text-align kiri untuk nama agar lebih rapi --}}
                                    <td style="text-align: center">{{ $siswa->jeniskelamin }}</td>
                                    <td style="text-align: center;">
                                        {{-- DIUBAH: Route diubah ke 'kelas.hapusSiswa' --}}
                                        <form
                                            action="{{ route('kelas.hapusSiswa', ['id_k' => $kelas->id_k, 'noinduk' => $siswa->noinduk]) }}"
                                            method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini dari kelas?')">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada siswa di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bagian Form untuk MENAMBAH Siswa Baru --}}
            <div class="card shadow">
                <div class="card-body">
                    <h4 class="text-default">Tambah Siswa ke Kelas</h4>
                    {{-- DIUBAH: Route diubah ke 'kelas.tambahSiswa' dan method form adalah POST --}}
                    <form action="{{ route('kelas.tambahSiswa', $kelas->id_k) }}" method="POST">
                        @csrf
                        {{-- DIUBAH: @method('PUT') dihapus karena kita pakai POST --}}

                        <div class="form-group mb-3">
                            <input type="text" id="searchSiswa" class="form-control" placeholder="Cari calon siswa...">
                        </div>

                        <div style="max-height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;">
                            {{-- DIUBAH: dari $semuaSiswa menjadi $calonSiswa --}}
                            @forelse($calonSiswa as $siswa)
                                <div class="form-check siswa-item">
                                    <input class="form-check-input" type="checkbox" name="siswa[]"
                                        value="{{ $siswa->noinduk }}" id="siswa-{{ $siswa->noinduk }}">
                                    <label class="form-check-label" for="siswa-{{ $siswa->noinduk }}">
                                        {{ $siswa->noinduk }} - {{ $siswa->nama }}
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">Semua siswa aktif sudah memiliki kelas di tahun ajaran ini.</p>
                            @endforelse
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Simpan Anggota Kelas</button>
                        <a href="{{ route('kelas.index') }}" class="btn btn-secondary mt-3">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Script JS tidak diubah, karena sudah bagus --}}
    @push('js') {{-- Gunakan @push('js') jika Anda punya @stack('js') di layout utama --}}
        <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
                    }
                });

                $('#searchSiswa').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $('.siswa-item').each(function() {
                        let label = $(this).text().toLowerCase();
                        $(this).toggle(label.includes(value));
                    });
                });
            });
        </script>

        </body>

        </html>
