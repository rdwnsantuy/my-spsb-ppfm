<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Ujian Seleksi</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
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

        @if($pakets->isEmpty())
            <div class="bg-white rounded shadow p-6">
                <p class="text-gray-700">Belum ada paket ujian yang tersedia.</p>

                @if(\Illuminate\Support\Facades\Route::has('pendaftar.jadwal'))
                    <a href="{{ route('pendaftar.jadwal') }}"
                       class="inline-flex items-center mt-4 px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm">
                        Kembali
                    </a>
                @endif
            </div>
        @else
            <div class="bg-white rounded shadow p-6">
                @php($now = \Illuminate\Support\Carbon::now())

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 rounded">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Nama Paket</th>
                                <th class="px-4 py-3 text-left">Durasi (menit)</th>
                                <th class="px-4 py-3 text-left">Periode</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($pakets as $p)
                                @php
                                    // Pastikan objek waktu
                                    $start = $p->mulai_pada ? \Illuminate\Support\Carbon::parse($p->mulai_pada) : null;
                                    $end   = $p->selesai_pada ? \Illuminate\Support\Carbon::parse($p->selesai_pada) : null;

                                    // Logika boleh mulai: (now >= start atau start null) dan (now <= end atau end null)
                                    $canStart = (!$start || $now->gte($start)) && (!$end || $now->lte($end));

                                    // Label status
                                    if ($start && $now->lt($start)) {
                                        $statusLabel = 'Belum dibuka';
                                        $statusBadge = 'bg-blue-100 text-blue-700';
                                    } elseif ($end && $now->gt($end)) {
                                        $statusLabel = 'Selesai';
                                        $statusBadge = 'bg-gray-200 text-gray-700';
                                    } elseif ($canStart) {
                                        $statusLabel = $start || $end ? 'Sedang berlangsung' : 'Siap';
                                        $statusBadge = 'bg-green-100 text-green-700';
                                    } else {
                                        $statusLabel = 'Tidak tersedia';
                                        $statusBadge = 'bg-gray-100 text-gray-700';
                                    }

                                    // Periode teks
                                    $periode = trim(
                                        ($start ? $start->format('d/m/Y H:i') : '—')
                                        .' — '.
                                        ($end ? $end->format('d/m/Y H:i') : '—')
                                    );

                                    // Tooltip alasan disable
                                    $whyDisabled = $start && $now->lt($start)
                                        ? 'Belum masuk waktu mulai'
                                        : ($end && $now->gt($end) ? 'Periode telah berakhir' : '');
                                @endphp

                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $p->nama_paket }}</td>
                                    <td class="px-4 py-3">{{ $p->durasi_menit }}</td>
                                    <td class="px-4 py-3">{{ $periode }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded {{ $statusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(\Illuminate\Support\Facades\Route::has('pendaftar.ujian.start'))
                                            <a href="{{ $canStart ? route('pendaftar.ujian.start', $p->id) : '#' }}"
                                               @if(!$canStart) aria-disabled="true" title="{{ $whyDisabled }}" @endif
                                               class="inline-flex items-center px-3 py-2 rounded text-sm
                                                      {{ $canStart ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                                   : 'bg-gray-200 text-gray-500 cursor-not-allowed' }}">
                                                Mulai Ujian
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-500">Route belum tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(\Illuminate\Support\Facades\Route::has('pendaftar.jadwal'))
                    <a href="{{ route('pendaftar.jadwal') }}"
                       class="inline-flex items-center mt-4 px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm">
                        Kembali
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
