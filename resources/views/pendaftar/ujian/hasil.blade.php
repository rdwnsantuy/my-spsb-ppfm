<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hasil Ujian
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        {{-- Flash messages --}}
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-2">Selamat, ujian selesai 🎉</h3>
            <p class="text-sm text-gray-600">
                Paket:
                <span class="font-medium">{{ $attempt->paket->nama_paket ?? '-' }}</span>
            </p>

            @php
                // Status label & badge
                $statusRaw = strtolower($attempt->status ?? '');
                $statusLabel = match ($statusRaw) {
                    'selesai'     => 'Selesai',
                    'berlangsung' => 'Berlangsung',
                    'kadaluarsa'  => 'Kadaluarsa',
                    default       => ucfirst($statusRaw ?: '—'),
                };
                $badge = match ($statusRaw) {
                    'selesai'     => 'bg-green-100 text-green-700',
                    'berlangsung' => 'bg-blue-100 text-blue-700',
                    'kadaluarsa'  => 'bg-yellow-100 text-yellow-700',
                    default       => 'bg-gray-100 text-gray-700',
                };

                // Ringkas jumlah soal & benar (aman jika relasi jawaban tidak dimuat)
                $totalSoal = method_exists($attempt, 'jawaban') && $attempt->relationLoaded('jawaban')
                    ? $attempt->jawaban->count()
                    : (\App\Models\JawabanUjian::where('percobaan_id', $attempt->id)->count());

                $jumlahBenar = method_exists($attempt, 'jawaban') && $attempt->relationLoaded('jawaban')
                    ? $attempt->jawaban->where('benar', true)->count()
                    : (\App\Models\JawabanUjian::where('percobaan_id', $attempt->id)->where('benar', true)->count());
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm mt-3">
                <div>
                    <div class="text-gray-500">Status</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badge }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div>
                    <div class="text-gray-500">Skor Total</div>
                    <div class="font-semibold">{{ $attempt->skor_total ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Kelulusan</div>
                    <div class="font-semibold">{{ ($attempt->lulus ?? false) ? 'LULUS' : 'TIDAK LULUS' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Waktu</div>
                    <div class="font-semibold">
                        {{ optional($attempt->mulai_pada)->format('d/m/Y H:i') ?? '—' }}
                        –
                        {{ optional($attempt->selesai_pada)->format('d/m/Y H:i') ?? '—' }}
                    </div>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <div class="text-gray-500">Ringkasan</div>
                    <div class="font-semibold">
                        Benar {{ $jumlahBenar }} dari {{ $totalSoal }} soal
                    </div>
                </div>
            </div>
        </div>

        {{-- Rekap per kategori --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Kategori</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Poin</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Maks</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Persentase</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Lulus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($attempt->nilaiKategori ?? []) as $nk)
                        <tr>
                            <td class="px-4 py-3">{{ $nk->kategori->nama_kategori ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $nk->poin_diperoleh }}</td>
                            <td class="px-4 py-3">{{ $nk->poin_maksimal }}</td>
                            <td class="px-4 py-3">{{ $nk->persentase }}%</td>
                            <td class="px-4 py-3">{{ $nk->lulus ? 'Ya' : 'Tidak' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Rekap per kategori belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <a href="{{ route('pendaftar.ujian.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-md border text-sm">
                Kembali ke Daftar Ujian
            </a>

            {{-- (opsional) tombol lihat riwayat jika ada rute --}}
            @if (\Illuminate\Support\Facades\Route::has('pendaftar.ujian.riwayat'))
                <a href="{{ route('pendaftar.ujian.riwayat') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md border text-sm">
                    Lihat Riwayat
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
