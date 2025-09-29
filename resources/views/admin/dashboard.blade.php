<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Halaman Dashboard
        </h2>
    </x-slot>

    @php
        use Illuminate\Support\Carbon;
        $today = Carbon::now();

        // Fallback aman kalau controller belum mengirim data:
        $stats = [
            'total_pendaftar'       => $stats['total_pendaftar']       ?? 0,
            'pending_pendaftaran'   => $stats['pending_pendaftaran']   ?? 0,
            'pending_daftar_ulang'  => $stats['pending_daftar_ulang']  ?? 0,
            'paket_aktif'           => $stats['paket_aktif']           ?? 0,
            'ujian_berjalan'        => $stats['ujian_berjalan']        ?? 0,
        ];

        // Koleksi opsional untuk tabel
        $waitingPayments = collect($waitingPayments ?? []);   // item: {nama, jenis, created_at, id}
        $recentPendaftar = collect($recentPendaftar ?? []);   // item: {name, created_at, id}
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Flash messages --}}
        @if(session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('ok') }}</div>
        @endif
        @if(session('warning'))
            <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
        @endif

        {{-- Kartu Statistik --}}
        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Total Pendaftar</div>
                    <div class="mt-1 text-2xl font-semibold">{{ number_format($stats['total_pendaftar']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Verifikasi Pendaftaran (Pending)</div>
                    <div class="mt-1 text-2xl font-semibold text-yellow-700">{{ number_format($stats['pending_pendaftaran']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Verifikasi Daftar Ulang (Pending)</div>
                    <div class="mt-1 text-2xl font-semibold text-orange-700">{{ number_format($stats['pending_daftar_ulang']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Paket Ujian Aktif</div>
                    <div class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format($stats['paket_aktif']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="text-sm text-gray-500">Ujian Berjalan</div>
                    <div class="mt-1 text-2xl font-semibold text-green-700">{{ number_format($stats['ujian_berjalan']) }}</div>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-1">Per {{ $today->format('d/m/Y H:i') }}</div>
        </section>

        {{-- Quick Actions (hanya tampil jika route tersedia) --}}
        <section class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold mb-3">Aksi Cepat</h3>
            <div class="flex flex-wrap gap-2">
                @if(\Illuminate\Support\Facades\Route::has('admin.verifikasi.index'))
                    <a href="{{ route('admin.verifikasi.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
                        Verifikasi Pembayaran
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.index'))
                    <a href="{{ route('admin.pendaftar.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm">
                        Data Pendaftar
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.paket.index'))
                    <a href="{{ route('admin.paket.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 hover:bg-gray-50 text-sm">
                        Kelola Paket Ujian
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.soal.index'))
                    <a href="{{ route('admin.soal.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 hover:bg-gray-50 text-sm">
                        Bank Soal
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.jadwal.index'))
                    <a href="{{ route('admin.jadwal.index') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 hover:bg-gray-50 text-sm">
                        Jadwal Seleksi
                    </a>
                @endif

                @if(
                    !\Illuminate\Support\Facades\Route::has('admin.verifikasi.index') &&
                    !\Illuminate\Support\Facades\Route::has('admin.pendaftar.index') &&
                    !\Illuminate\Support\Facades\Route::has('admin.paket.index') &&
                    !\Illuminate\Support\Facades\Route::has('admin.soal.index') &&
                    !\Illuminate\Support\Facades\Route::has('admin.jadwal.index')
                )
                    <div class="text-sm text-gray-500">Belum ada route aksi cepat yang terdaftar.</div>
                @endif
            </div>
        </section>

        {{-- Ringkasan: Pembayaran menunggu verifikasi --}}
        <section class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Pembayaran Menunggu Verifikasi</h3>
                @if(\Illuminate\Support\Facades\Route::has('admin.verifikasi.index'))
                    <a href="{{ route('admin.verifikasi.index') }}" class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                @endif
            </div>

            @if($waitingPayments->isEmpty())
                <div class="text-sm text-gray-500">Tidak ada pembayaran pending.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 rounded">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 text-left">Nama</th>
                                <th class="px-4 py-2 text-left">Jenis</th>
                                <th class="px-4 py-2 text-left">Diupload</th>
                                <th class="px-4 py-2 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($waitingPayments as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row->nama ?? $row['nama'] ?? '-' }}</td>
                                    <td class="px-4 py-2 capitalize">
                                        {{ $row->jenis ?? $row['jenis'] ?? 'pendaftaran' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ optional($row->created_at ?? null)->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if(\Illuminate\Support\Facades\Route::has('admin.verifikasi.show') && ($row->id ?? null))
                                            <a href="{{ route('admin.verifikasi.show', $row->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                                Periksa
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Ringkasan: Pendaftar terbaru --}}
        <section class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Pendaftar Terbaru</h3>
                @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.index'))
                    <a href="{{ route('admin.pendaftar.index') }}" class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                @endif
            </div>

            @if($recentPendaftar->isEmpty())
                <div class="text-sm text-gray-500">Belum ada data pendaftar terbaru.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 rounded">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-2 text-left">Nama</th>
                                <th class="px-4 py-2 text-left">Tanggal</th>
                                <th class="px-4 py-2 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recentPendaftar as $u)
                                <tr>
                                    <td class="px-4 py-2">{{ $u->name ?? $u['name'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ optional($u->created_at ?? null)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.show') && ($u->id ?? null))
                                            <a href="{{ route('admin.pendaftar.show', $u->id) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 hover:bg-gray-50">
                                                Detail
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
