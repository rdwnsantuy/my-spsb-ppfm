<x-app-layout>
    <x-slot name="header">
        @php  $items = $items ?? collect();  @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Jadwal Seleksi
        </h2>
    </x-slot>

    @php
        // pakai FQN agar tidak error di Blade
        $now = \Illuminate\Support\Carbon::now();
    @endphp

    <div class="max-w-7xl mx-auto space-y-4">
        {{-- Flash messages --}}
        @if (session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('ok') }}</div>
        @endif
        @if (session('warning'))
            <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">{{ session('warning') }}</div>
        @endif
        @if (session('error'))
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
        @endif

        {{-- Action bar --}}
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                Kelola daftar jadwal seleksi. Gunakan pencarian dan filter untuk mempermudah.
            </div>
            <div class="flex items-center gap-2">
                @if(\Illuminate\Support\Facades\Route::has('admin.jadwal.create'))
                    <a href="{{ route('admin.jadwal.create') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm">
                        Tambah Jadwal
                    </a>
                @endif
            </div>
        </div>

        {{-- Filter bar --}}
        <section class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500">Pencarian</label>
                    <input id="q" type="text" placeholder="Cari nama/lokasi/keterangan…"
                           class="mt-1 w-full rounded border-gray-300"
                           oninput="filterRows()">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Status</label>
                    <select id="status" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        <option value="upcoming">Mendatang</option>
                        <option value="running">Berlangsung</option>
                        <option value="finished">Selesai</option>
                        <option value="na">Tanpa periode</option>
                    </select>
                </div>
            </div>
        </section>

        {{-- Tabel utama --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left w-[28%]">Acara</th>
                    <th class="px-4 py-3 text-left w-[26%]">Periode</th>
                    <th class="px-4 py-3 text-left w-[20%]">Lokasi</th>
                    <th class="px-4 py-3 text-left w-[14%]">Status</th>
                    <th class="px-4 py-3 text-left w-[12%]">Aksi</th>
                </tr>
                </thead>
                <tbody id="tbody" class="divide-y divide-gray-100">
                @forelse($items as $row)
                    @php
                        // Ambil field dengan fallback aman
                        $name = $row->nama ?? $row->nama_jadwal ?? $row->nama_kegiatan ?? $row->judul ?? '-';
                        $loc  = $row->lokasi ?? $row->tempat ?? '-';
                        $desc = $row->keterangan ?? $row->deskripsi ?? null;

                        $start = $row->mulai_pada ?? $row->start_at ?? $row->tanggal_mulai ?? null;
                        $end   = $row->selesai_pada ?? $row->end_at ?? $row->tanggal_selesai ?? null;

                        $startC = $start ? \Illuminate\Support\Carbon::parse($start) : null;
                        $endC   = $end   ? \Illuminate\Support\Carbon::parse($end)   : null;

                        // Tentukan status
                        if (!$startC && !$endC) {
                            $stat = 'na';            // Tanpa periode
                            $badge = 'bg-gray-100 text-gray-700';
                            $label = 'Tanpa periode';
                        } elseif ($startC && $now->lt($startC)) {
                            $stat = 'upcoming';      // Mendatang
                            $badge = 'bg-blue-100 text-blue-700';
                            $label = 'Mendatang';
                        } elseif ($endC && $now->gt($endC)) {
                            $stat = 'finished';      // Selesai
                            $badge = 'bg-gray-200 text-gray-700';
                            $label = 'Selesai';
                        } else {
                            $stat = 'running';       // Berlangsung
                            $badge = 'bg-green-100 text-green-700';
                            $label = 'Berlangsung';
                        }

                        // Periode tampil
                        $periode = ($startC ? $startC->format('d/m/Y H:i') : '—')
                                .' — '
                                .($endC ? $endC->format('d/m/Y H:i') : '—');

                        // String gabungan untuk pencarian
                        $hay = \Illuminate\Support\Str::lower(
                            trim($name.' '.$loc.' '.($desc ?? '').' '.$periode.' '.$label)
                        );
                    @endphp

                    <tr class="align-top" data-haystack="{{ $hay }}" data-status="{{ $stat }}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $name }}</div>
                            @if($desc)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($desc), 120) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $periode }}</td>
                        <td class="px-4 py-3">{{ $loc }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badge }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if(\Illuminate\Support\Facades\Route::has('admin.jadwal.show'))
                                    <a href="{{ route('admin.jadwal.show', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                                        Detail
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.jadwal.edit'))
                                    <a href="{{ route('admin.jadwal.edit', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                        Edit
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.jadwal.destroy'))
                                    <form method="POST" action="{{ route('admin.jadwal.destroy', $row->id) }}"
                                          onsubmit="return confirm('Hapus jadwal ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded bg-red-600 text-white text-xs hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                                @if(
                                    !\Illuminate\Support\Facades\Route::has('admin.jadwal.show') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.jadwal.edit') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.jadwal.destroy')
                                )
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                            Belum ada jadwal seleksi yang tercatat.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{-- Pagination (jika paginator) --}}
            @if(method_exists($items, 'links'))
                <div class="px-4 py-3 border-t">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Filter client-side --}}
    <script>
        function filterRows() {
            const q = (document.getElementById('q').value || '').toLowerCase().trim();
            const st = (document.getElementById('status').value || '').trim();
            const rows = document.querySelectorAll('#tbody > tr');

            rows.forEach(tr => {
                const hay = tr.getAttribute('data-haystack') || '';
                const s = tr.getAttribute('data-status') || '';
                const hitQ = !q || hay.includes(q);
                const hitS = !st || s === st;
                tr.style.display = (hitQ && hitS) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
