  @section('content')
    @include('layouts.headerguru')
  
  <div class="main-content">
        <div class="container-fluid">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="text-success">Proses Pertemuan</h2>

                    {{-- Menampilkan pesan sukses atau error --}}
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('pertemuan.updateStatus', $pertemuan->id_p) }}" method="POST">
                        @csrf
                        @method('PUT') {{-- Penting untuk metode PUT --}}

                        <div class="form-group mb-4">
                            <label>Tanggal Pengajuan Pertemuan</label>
                            <input type="text" name="tglpengajuan" value="{{ \Carbon\Carbon::parse($pertemuan->tglpengajuan)->format('d M Y') }}" class="form-control" readonly>
                        </div>

                        <div class="form-group mb-4">
                            <label>Tanggal Pertemuan</label>
                            <input type="text" name="tglpertemuan" value="{{ \Carbon\Carbon::parse($pertemuan->tglpertemuan)->format('d M Y') }}" class="form-control" readonly>
                        </div>

                        <div class="form-group mb-4">
                            <label>Jam Pertemuan</label>
                            <input type="text" name="jampertemuan" value="{{ \Carbon\Carbon::parse($pertemuan->jampertemuan)->format('H:i') }}" class="form-control" readonly>
                        </div>

                        <div class="form-group mb-4">
                            <label>Deskripsi Pertemuan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" readonly>{{ $pertemuan->deskripsi }}</textarea>
                        </div>

                        <div class="form-group mb-4">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Diproses" {{ $pertemuan->status == 'Diproses' ? 'selected' : '' }}>Diproses</option>
                                <option value="Diterima" {{ $pertemuan->status == 'Diterima' ? 'selected' : '' }}>Diterima</option>
                                <option value="Ditolak" {{ $pertemuan->status == 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                                <option value="Selesai" {{ $pertemuan->status == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>

                        {{-- Field Alasan Ditolak (Hanya Muncul Ketika Status = Ditolak) --}}
                        <div class="form-group mb-4" id="alasanDitolakContainer" style="display: {{ $pertemuan->status == 'Ditolak' ? 'block' : 'none' }};">
                            <label for="alasan">Alasan (jika Ditolak)</label>
                            <textarea name="alasan" id="alasan" class="form-control" rows="3">{{ old('alasan', $pertemuan->alasan) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Simpan Perubahan</button>
                        <a href="{{ route('pertemuan.index') }}" class="btn btn-secondary mt-3">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript untuk Tampilkan/Sembunyikan Alasan Ditolak --}}
    @section('scripts')
    @parent {{-- Memastikan script dari parent layout tetap di-include --}}
     <script src="https://argon-dashboard-laravel-bs4.creative-tim.com/argon/vendor/jquery/dist/jquery.min.js"></script>
    <script
        src="https://argon-dashboard-laravel-bs4.creative-tim.com/argon/vendor/bootstrap/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const alasanContainer = document.getElementById('alasanDitolakContainer');

            // Fungsi untuk update tampilan alasan
            function updateAlasanVisibility() {
                if (statusSelect.value === 'Ditolak') {
                    alasanContainer.style.display = 'block';
                } else {
                    alasanContainer.style.display = 'none';
                }
            }

            // Panggil saat halaman pertama kali dimuat
            updateAlasanVisibility();

            // Panggil saat dropdown status berubah
            statusSelect.addEventListener('change', updateAlasanVisibility);
        });
    </script>