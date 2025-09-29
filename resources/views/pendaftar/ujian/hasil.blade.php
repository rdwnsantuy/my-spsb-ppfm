<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hasil Ujian
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        {{-- Flash --}}
        @if(session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        @php
            use Illuminate\Support\Str;
            use Illuminate\Support\Carbon;

            $statusRaw  = Str::of(strtolower($attempt->status ?? ''))->replace('_', ' ')->title();
            $statusCls  = match(strtolower($attempt->status ?? '')) {
                'completed', 'selesai', 'done', 'submitted' => 'bg-green-100 text-green-700',
                'in_progress', 'proses', 'running'         => 'bg-blue-100 text-blue-700',
                'expired', 'timeout'                        => 'bg-orange-100 text-orange-700',
                'cancelled', 'canceled', 'gagal'            => 'bg-red-100 text-red-700',
                default                                     => 'bg-gray-100 text-gray-700',
            };

            $mulai   = $attempt->mulai_pada   ? Carbon::parse($attempt->mulai_pada)   : null;
            $selesai = $attempt->selesai_pada ? Carbon::parse($attempt->selesai_pada) : null;
            $durasiMenit = ($mulai && $selesai) ? $mulai->diffInMinutes($selesai) : null;

            // Koleksi nilai per kategori
            $nilaiKats = collect($attempt->nilaiKategori ?? []);
            $sumDapat  = (float) $nilaiKats->sum('poin_diperoleh');
            $sumMaks   = (float) $nilaiKats->sum('poin_maksimal');
            $persenTotal = $sumMaks > 0 ? round(($sumDapat / $sumMaks) * 100, 2) : null;

            $skorTotal = is_numeric($attempt->skor_total ?? null) ? $attempt->skor_total : $sumDapat; // fallback
            $lulus     = (bool) ($attempt->lulus ?? false);
        @endphp

        {{-- Ringkasan --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold mb-2">{{ $attempt->paket->nama_paket ?? '-' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Status</div>
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded {{ $statusCls }}">
                                    {{ $statusRaw ?: '—' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500">Skor Total</div>
                            <div class="font-semibold">{{ is_numeric($skorTotal) ? $skorTotal : 0 }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Kelulusan</div>
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded {{ $lulus ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $lulus ? 'LULUS' : 'TIDAK LULUS' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500">Waktu</div>
                            <div class="font-semibold">
                                {{ $mulai?->format('d/m/Y H:i') ?? '—' }} – {{ $selesai?->format('d/m/Y H:i') ?? '—' }}
                                @if(!is_null($durasiMenit))
                                    <span class="text-gray-500 font-normal">({{ $durasiMenit }} menit)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="shrink-0 flex items-center gap-2">
                    <button onclick="window.print()"
                            class="inline-flex items-center px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm border">
                        Cetak
                    </button>
                    @if(\Illuminate\Support\Facades\Route::has('pendaftar.ujian.index'))
                        <a href="{{ route('pendaftar.ujian.index') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md border text-sm">
                            Kembali
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Nilai per Kategori --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($nilaiKats->isEmpty())
                <div class="p-6 text-sm text-gray-600">Belum ada rincian nilai per kategori.</div>
            @else
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
                        @foreach($nilaiKats as $nk)
                            @php
                                $poin   = (float) ($nk->poin_diperoleh ?? 0);
                                $maks   = (float) ($nk->poin_maksimal ?? 0);
                                $pct    = $nk->persentase ?? ($maks > 0 ? round(($poin / $maks) * 100, 2) : 0);
                                $pass   = (bool) ($nk->lulus ?? false);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">{{ $nk->kategori->nama_kategori ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $poin }}</td>
                                <td class="px-4 py-3">{{ $maks }}</td>
                                <td class="px-4 py-3">{{ $pct }}%</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $pass ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $pass ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td class="px-4 py-3 font-semibold">Total</td>
                            <td class="px-4 py-3 font-semibold">{{ $sumDapat }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $sumMaks }}</td>
                            <td class="px-4 py-3 font-semibold">
                                {{ !is_null($persenTotal) ? $persenTotal.'%' : '—' }}
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Kembali (mobile-friendly) --}}
        @if(\Illuminate\Support\Facades\Route::has('pendaftar.ujian.index'))
            <div class="lg:hidden">
                <a href="{{ route('pendaftar.ujian.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md border text-sm">
                    Kembali ke Daftar Ujian
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
