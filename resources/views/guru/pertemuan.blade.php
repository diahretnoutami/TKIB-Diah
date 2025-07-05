@section('content')
    @include('layouts.headerguru')

    <div class="main-content">
        <div class="container-fluid pt-7">
            <h1 class="text-success mb-4" style="margin-top: -100px;">Data Pengajuan Pertemuan</h1>

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="text-align: center; font-size: 16px;">No</th>
                                    <th style="text-align: center; font-size: 16px;">Nama Siswa</th>
                                    <th style="text-align: center; font-size: 16px;">Nama Orang Tua</th>
                                    <th style="text-align: center; font-size: 16px;">Tanggal Pengajuan</th>
                                    <th style="text-align: center; font-size: 16px;">Tanggal Pertemuan</th>
                                    <th style="text-align: center; font-size: 16px;">Jam Pertemuan</th>
                                    <th style="text-align: center; font-size: 16px;">Status</th>
                                    <th style="text-align: center; font-size: 16px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pertemuan as $index => $item)
                                    <tr>
                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                        <td>{{ $item->siswa->nama ?? '-' }}</td>
                                        <td>{{ $item->siswa->orangtua->namaortu ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tglpengajuan)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tglpertemuan)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->jampertemuan)->format('H:i') }}</td>
                                        <td>
                                            <span
                                                class="badge text-white {{ $item->status == 'Diproses'
                                                    ? 'bg-info'
                                                    : ($item->status == 'Diterima'
                                                        ? 'bg-success'
                                                        : ($item->status == 'Ditolak'
                                                            ? 'bg-danger'
                                                            : ($item->status == 'Selesai'
                                                                ? 'bg-secondary'
                                                                : 'bg-warning'))) }}">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('pertemuan.show', $item->id_p) }}"
                                                class="btn btn-sm btn-primary">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
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
