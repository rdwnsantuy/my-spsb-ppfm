<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ujian Seleksi</h2>
    </x-slot>

    @php
        // Guard: kalau controller lupa kirim, jadikan koleksi kosong agar view tetap aman
        /** @var \Illuminate\Support\Collection|\App\Models\PaketUjian[] $pakets */
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
                            <th class="px-4 py-3 text-left">Kuota</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($pakets as $p)
                            @php
                                $start   = $p->mulai_pada ?? $p->start_at ?? null;
                                $end     = $p->selesai_pada ?? $p->end_at ?? null;
                                $mulai   = $start ? \Illuminate\Support\Carbon::parse($start) : null;
                                $selesai = $end   ? \Illuminate\Support\Carbon::parse($end)   : null;

                                // Periode aktif?
                                $active  = (!$mulai || now()->gte($mulai)) && (!$selesai || now()->lte($selesai));

                                // Kuota & izin ulang
                                $allowed = \App\Services\UjianQuota::allowedAttempts(auth()->id(), $p);
                                $used    = \App\Services\UjianQuota::usedAttempts(auth()->id(), $p);
                                $sisa    = max(0, $allowed - $used);

                                // Boleh mulai/ulangi? (periode aktif + masih ada kuota + tidak ada attempt berjalan)
                                $canRetry = \App\Services\UjianQuota::canStartNewAttempt(auth()->id(), $p);
                                $enabled  = $active && $canRetry;
                            @endphp

                            <tr>
                                <td class="px-4 py-3 font-medium">
                                    {{ $p->nama_paket ?? $p->nama ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $p->durasi_menit ?? $p->durasi ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $mulai ? $mulai->format('d/m/Y H:i') : '—' }} —
                                    {{ $selesai ? $selesai->format('d/m/Y H:i') : '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">
                                        {{ $used }} / {{ $allowed }}
                                    </span>
                                    @if($sisa <= 0)
                                        <span class="ml-2 text-xs text-red-600">Kuota habis</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if ($enabled)
                                        <a href="{{ route('pendaftar.ujian.start', $p->id) }}"
                                           class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
                                            Mulai / Ulangi Ujian
                                        </a>
                                    @else
                                        <span
                                            class="inline-flex items-center px-3 py-2 rounded bg-gray-200 text-gray-600 text-sm cursor-not-allowed select-none">
                                            Mulai / Ulangi Ujian
                                        </span>

                                        <div class="mt-1 text-xs text-gray-500">
                                            @if (! $active && $mulai && now()->lt($mulai))
                                                Di luar periode: belum mulai (aktif {{ $mulai->format('d/m/Y H:i') }}).
                                            @elseif (! $active && $selesai && now()->gt($selesai))
                                                Di luar periode: sudah berakhir (s/d {{ $selesai->format('d/m/Y H:i') }}).
                                            @elseif ($sisa <= 0)
                                                Kuota ujian Anda habis. Hubungi panitia untuk izin ujian ulang.
                                            @else
                                                Tidak tersedia.
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                    Belum ada paket ujian yang tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2 mt-4">
                <a href="{{ route('pendaftar.jadwal') }}"
                   class="inline-flex items-center px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm">
                   Kembali
                </a>
                <a href="{{ route('pendaftar.ujian.riwayat') }}"
                   class="inline-flex items-center px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm">
                   Lihat Riwayat
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
