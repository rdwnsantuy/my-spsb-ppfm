<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ujian Seleksi</h2>
    </x-slot>

    @php
        // guard: kalau controller lupa kirim, jadikan koleksi kosong agar view tetap aman
        $pakets = $pakets ?? collect();
    @endphp

    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded shadow p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200 rounded">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama Paket</th>
                            <th class="px-4 py-3 text-left">Durasi (menit)</th>
                            <th class="px-4 py-3 text-left">Periode</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($pakets as $p)
                            @php
                                $start  = $p->mulai_pada ?? $p->start_at ?? null;
                                $end    = $p->selesai_pada ?? $p->end_at ?? null;
                                $mulai  = $start ? \Illuminate\Support\Carbon::parse($start) : null;
                                $selesai= $end   ? \Illuminate\Support\Carbon::parse($end)   : null;

                                // tombol aktif kalau sudah dalam rentang waktu
                                $active = (!$mulai || now()->gte($mulai)) && (!$selesai || now()->lte($selesai));
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">
                                    {{ $p->nama_paket ?? $p->nama ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $p->durasi_menit ?? $p->durasi ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $mulai ? $mulai->format('d/m/Y H:i') : '—' }}
                                    —
                                    {{ $selesai ? $selesai->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('pendaftar.ujian.start', $p->id) }}"
                                       class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 text-sm {{ $active ? '' : 'opacity-50 cursor-not-allowed pointer-events-none' }}">
                                        Mulai Ujian
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada paket ujian yang tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('pendaftar.jadwal') }}"
               class="inline-flex items-center mt-4 px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm">
               Kembali
            </a>
        </div>
    </div>
</x-app-layout>
