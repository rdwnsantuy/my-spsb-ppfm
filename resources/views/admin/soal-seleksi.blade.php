<x-app-layout>
    <x-slot name="header">
        @php  $items = $items ?? collect();  @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Soal Seleksi
        </h2>
    </x-slot>

    @php
        // Kumpulkan opsi filter kategori/tipe/tingkat dari data yang tampil (aman utk paginator/collection)
        $katSet = [];
        $tipeSet = [];
        $lvlSet = [];

        foreach ($items as $row) {
            $kat = optional($row->kategori ?? null)->nama_kategori
                ?? ($row->kategori_nama ?? $row->kategori ?? null);
            if ($kat) $katSet[] = $kat;

            $tipe = $row->tipe ?? $row->jenis ?? null; // 'pg' | 'esai' | 'isian' | dst
            if ($tipe) $tipeSet[] = $tipe;

            $lvl = $row->tingkat ?? $row->kesulitan ?? null; // 'mudah'|'sedang'|'sulit' (opsional)
            if ($lvl) $lvlSet[] = $lvl;
        }

        $katOptions  = array_values(array_unique($katSet));
        $tipeOptions = array_values(array_unique($tipeSet));
        $lvlOptions  = array_values(array_unique($lvlSet));

        $tipeLabel = function ($t) {
            return match(\Illuminate\Support\Str::lower((string)$t)) {
                'pg', 'pilihan_ganda', 'pilihan-ganda' => 'Pilihan Ganda',
                'esai', 'essay'                        => 'Esai',
                'isian', 'short', 'short_answer'       => 'Isian',
                default                                => ucfirst((string)$t ?: '—'),
            };
        };
    @endphp

    <div class="max-w-7xl mx-auto space-y-4">
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

        {{-- Action bar --}}
        <section class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-xs text-gray-500">Pencarian</label>
                    <input id="q" type="text" placeholder="Cari isi soal/kategori/tingkat…"
                           class="mt-1 w-full rounded border-gray-300"
                           oninput="filterRows()">
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Kategori</label>
                    <select id="f_kat" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        @foreach($katOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Tipe</label>
                    <select id="f_tipe" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        @foreach($tipeOptions as $opt)
                            <option value="{{ $opt }}">{{ $tipeLabel($opt) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Tingkat</label>
                    <select id="f_lvl" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        @foreach($lvlOptions as $opt)
                            <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Status</label>
                    <select id="f_status" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        <option value="aktif">Aktif</option>
                        <option value="draft">Draft</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @if(\Illuminate\Support\Facades\Route::has('admin.soal.export'))
                        <a href="{{ route('admin.soal.export') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-sm hover:bg-gray-50">
                            Export
                        </a>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.soal.import'))
                        <a href="{{ route('admin.soal.import') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-sm hover:bg-gray-50">
                            Import
                        </a>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.soal.create'))
                        <a href="{{ route('admin.soal.create') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                            Tambah Soal
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Tabel utama --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left w-[34%]">Soal</th>
                    <th class="px-4 py-3 text-left w-[16%]">Kategori</th>
                    <th class="px-4 py-3 text-left w-[12%]">Tipe</th>
                    <th class="px-4 py-3 text-left w-[10%]">Tingkat</th>
                    <th class="px-4 py-3 text-left w-[14%]">Opsi / Kunci</th>
                    <th class="px-4 py-3 text-left w-[8%]">Status</th>
                    <th class="px-4 py-3 text-left w-[6%]">Aksi</th>
                </tr>
                </thead>
                <tbody id="tbody" class="divide-y divide-gray-100">
                @forelse($items as $row)
                    @php
                        // Soal text (snapshot atau langsung)
                        $rawSoal = $row->teks_soal_snapshot ?? $row->teks_soal ?? $row->soal ?? '';
                        $text    = trim(\Illuminate\Support\Str::limit(strip_tags($rawSoal), 160));

                        // Kategori
                        $kategori = optional($row->kategori ?? null)->nama_kategori
                            ?? ($row->kategori_nama ?? $row->kategori ?? '—');

                        // Tipe & label
                        $tipeRaw = $row->tipe ?? $row->jenis ?? 'pg';
                        $tipeDisp = $tipeLabel($tipeRaw);

                        // Tingkat/kesulitan (opsional)
                        $lvl = $row->tingkat ?? $row->kesulitan ?? '—';
                        $lvlDisp = $lvl ? ucfirst($lvl) : '—';

                        // Opsi dan kunci (mendukung berbagai bentuk: array/collection/relasi)
                        $opsiArr = [];
                        if (is_array($row->opsi_snapshot ?? null)) {
                            $opsiArr = $row->opsi_snapshot;
                        } elseif (isset($row->opsi) && (is_array($row->opsi) || $row->opsi instanceof \Illuminate\Support\Collection)) {
                            $opsiArr = $row->opsi;
                        } elseif (method_exists($row, 'opsi') && $row->opsi) {
                            try { $opsiArr = $row->opsi->toArray(); } catch (\Throwable $e) { $opsiArr = []; }
                        }
                        $opsiCount = is_array($opsiArr) ? count($opsiArr) : 0;

                        // Kunci
                        $kunciDisp = null;
                        if (!empty($row->kunci)) {
                            $kunciDisp = is_array($row->kunci) ? implode(',', $row->kunci) : (string)$row->kunci;
                        } else {
                            $benar = [];
                            foreach ($opsiArr as $op) {
                                $label = $op['label'] ?? $op['kode'] ?? null;
                                $isBenar = $op['benar'] ?? $op['is_correct'] ?? false;
                                if ($label && $isBenar) $benar[] = $label;
                            }
                            if ($benar) $kunciDisp = implode(',', $benar);
                        }
                        if (!$kunciDisp) $kunciDisp = '—';

                        // Status: aktif/draft/nonaktif
                        $statusRaw = null;
                        if (property_exists($row, 'aktif') || isset($row->aktif)) {
                            $statusRaw = $row->aktif ? 'aktif' : 'nonaktif';
                        } elseif (isset($row->status)) {
                            $statusRaw = \Illuminate\Support\Str::lower($row->status);
                            if (!in_array($statusRaw, ['aktif','draft','nonaktif'])) {
                                $statusRaw = $row->status ? 'aktif' : 'nonaktif';
                            }
                        }
                        $statusRaw = $statusRaw ?? 'aktif';
                        $statusBadge = [
                            'aktif'    => 'bg-green-100 text-green-700',
                            'draft'    => 'bg-yellow-100 text-yellow-700',
                            'nonaktif' => 'bg-gray-100 text-gray-700',
                        ][$statusRaw] ?? 'bg-gray-100 text-gray-700';

                        // Updated
                        $updated = $row->updated_at ?? $row->created_at ?? null;
                        $updatedDisp = $updated ? \Illuminate\Support\Carbon::parse($updated)->format('d/m/Y H:i') : '—';

                        // haystack utk filter/search
                        $hay = \Illuminate\Support\Str::lower(trim($text.' '.$kategori.' '.$tipeDisp.' '.$lvlDisp.' '.$statusRaw));
                    @endphp

                    <tr data-haystack="{{ $hay }}"
                        data-kat="{{ $kategori }}"
                        data-tipe="{{ $tipeRaw }}"
                        data-lvl="{{ \Illuminate\Support\Str::lower($lvl) }}"
                        data-status="{{ $statusRaw }}">
                        {{-- Soal --}}
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $text ?: '—' }}</div>
                            @if(!empty($row->lampiran))
                                <div class="text-xs text-gray-500 mt-1">Lampiran: tersedia</div>
                            @endif
                        </td>

                        {{-- Kategori --}}
                        <td class="px-4 py-3">{{ $kategori }}</td>

                        {{-- Tipe --}}
                        <td class="px-4 py-3">{{ $tipeDisp }}</td>

                        {{-- Tingkat --}}
                        <td class="px-4 py-3">{{ $lvlDisp }}</td>

                        {{-- Opsi / Kunci --}}
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $opsiCount }} opsi</div>
                            <div class="text-xs text-gray-500">Kunci: {{ $kunciDisp }}</div>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded {{ $statusBadge }}">
                                {{ ucfirst($statusRaw) }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">Upd: {{ $updatedDisp }}</div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if(\Illuminate\Support\Facades\Route::has('admin.soal.show'))
                                    <a href="{{ route('admin.soal.show', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                                        Detail
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.soal.edit'))
                                    <a href="{{ route('admin.soal.edit', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                        Edit
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.soal.duplicate'))
                                    <form method="POST" action="{{ route('admin.soal.duplicate', $row->id) }}"
                                          onsubmit="return confirm('Duplikasi soal ini?');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">
                                            Duplikasi
                                        </button>
                                    </form>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.soal.destroy'))
                                    <form method="POST" action="{{ route('admin.soal.destroy', $row->id) }}"
                                          onsubmit="return confirm('Hapus soal ini? Tindakan tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded bg-red-600 text-white text-xs hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                                @if(
                                    !\Illuminate\Support\Facades\Route::has('admin.soal.show') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.soal.edit') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.soal.duplicate') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.soal.destroy')
                                )
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                            Belum ada soal pada daftar ini.
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
            const q   = (document.getElementById('q').value || '').toLowerCase().trim();
            const kat = (document.getElementById('f_kat').value || '').trim();
            const tp  = (document.getElementById('f_tipe').value || '').trim();
            const lv  = (document.getElementById('f_lvl').value || '').trim().toLowerCase();
            const st  = (document.getElementById('f_status').value || '').trim().toLowerCase();

            document.querySelectorAll('#tbody > tr').forEach(tr => {
                const hay = (tr.getAttribute('data-haystack') || '').toLowerCase();
                const rk  = tr.getAttribute('data-kat') || '';
                const rt  = tr.getAttribute('data-tipe') || '';
                const rl  = (tr.getAttribute('data-lvl') || '').toLowerCase();
                const rs  = (tr.getAttribute('data-status') || '').toLowerCase();

                const hitQ  = !q   || hay.includes(q);
                const hitK  = !kat || rk === kat;
                const hitT  = !tp  || rt === tp;
                const hitL  = !lv  || rl === lv;
                const hitS  = !st  || rs === st;

                tr.style.display = (hitQ && hitK && hitT && hitL && hitS) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
