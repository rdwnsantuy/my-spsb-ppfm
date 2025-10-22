<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Soal Seleksi
        </h2>
    </x-slot>

    @php
        // ===== Normalisasi input view =====
        /** @var \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $items */
        $items = $items ?? ($soal ?? collect());
        $kategories = $kategories ?? collect();

        // Kumpulkan opsi filter (kategori/tipe/tingkat) dari data yang tampil
        $katSet=[]; $tipeSet=[]; $lvlSet=[];
        foreach ($items as $row) {
            $kat = optional($row->kategori ?? null)->nama_kategori
                ?? ($row->kategori_nama ?? $row->kategori ?? null);
            if ($kat) $katSet[] = $kat;

            $tipe = $row->tipe ?? $row->jenis ?? 'pg';
            if ($tipe) $tipeSet[] = $tipe;

            $lvl = $row->tingkat ?? $row->kesulitan ?? null;
            if ($lvl) $lvlSet[] = $lvl;
        }
        $katOptions  = array_values(array_unique($katSet));
        $tipeOptions = array_values(array_unique($tipeSet));
        $lvlOptions  = array_values(array_unique($lvlSet));

        $tipeLabel = function ($t) {
            return match(\Illuminate\Support\Str::lower((string)$t)) {
                'pg','pilihan_ganda','pilihan-ganda' => 'Pilihan Ganda',
                'esai','essay'                        => 'Esai',
                'isian','short','short_answer'       => 'Isian',
                default                               => ucfirst((string)$t ?: '—'),
            };
        };
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
        @if ($errors->any())
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- ===== Row: Kategori Soal (Tambah + Daftar) ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="font-semibold mb-3">Tambah Kategori Soal</h3>
                <form method="POST" action="{{ route('admin.ujian.kategori-soal.store.quick') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Nama Kategori</label>
                        <input name="nama_kategori" type="text" class="mt-1 w-full rounded border-gray-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Deskripsi (opsional)</label>
                        <textarea name="deskripsi" rows="3" class="mt-1 w-full rounded border-gray-300"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Simpan Kategori</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Kategori Soal</h3>
                </div>
                <div class="divide-y">
                    @forelse($kategories as $kat)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $kat->nama_kategori }}</div>
                                @if(!empty($kat->deskripsi))
                                    <div class="text-xs text-gray-500">{{ $kat->deskripsi }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Hapus cepat --}}
                                <form method="POST"
                                      action="{{ route('admin.ujian.kategori-soal.destroy.quick', $kat->id) }}"
                                      onsubmit="return confirm('Hapus kategori ini?');">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 rounded border text-sm">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">Belum ada kategori.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Form: Tambah Soal Cepat ===== --}}
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold mb-3">Tambah Soal</h3>
            <form method="POST" action="{{ route('admin.ujian.soal.store.quick') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Kategori</label>
                        <select name="kategori_id" class="mt-1 w-full rounded border-gray-300" required>
                            <option value="">— pilih —</option>
                            @foreach($kategories as $kat)
                                <option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kesulitan</label>
                        <select name="tingkat_kesulitan" class="mt-1 w-full rounded border-gray-300">
                            <option value="mudah">mudah</option>
                            <option value="sedang" selected>sedang</option>
                            <option value="sulit">sulit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Bobot</label>
                        <input type="number" name="bobot" min="1" max="10" value="1" class="mt-1 w-full rounded border-gray-300">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Teks Soal</label>
                    <textarea name="teks_soal" rows="3" class="mt-1 w-full rounded border-gray-300" required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach (['A','B','C','D'] as $lbl)
                        <div>
                            <label class="block text-sm font-medium">Opsi {{ $lbl }}</label>
                            <textarea name="opsi[{{ $lbl }}][teks]" rows="2" class="mt-1 w-full rounded border-gray-300" required></textarea>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium">Jawaban Benar</label>
                        <select name="opsi_benar" class="mt-1 w-full rounded border-gray-300">
                            <option value="A">A</option><option value="B">B</option>
                            <option value="C">C</option><option value="D">D</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Status</label>
                        <select name="status_aktif" class="mt-1 w-full rounded border-gray-300">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <button class="px-4 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">Simpan Soal</button>
            </form>
        </div>

        {{-- ===== Action bar (Filter client-side) ===== --}}
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
                    @if(Route::has('admin.soal.create'))
                        <a href="{{ route('admin.soal.create') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                            Halaman Lengkap
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- ===== Tabel utama ===== --}}
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

                        // Tingkat/kesulitan
                        $lvl = $row->tingkat ?? $row->kesulitan ?? '—';
                        $lvlDisp = $lvl ? ucfirst($lvl) : '—';

                        // Opsi dan kunci (array/collection/relasi)
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

                        // Status
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

                        // pencarian
                        $hay = \Illuminate\Support\Str::lower(trim($text.' '.$kategori.' '.$tipeDisp.' '.$lvlDisp.' '.$statusRaw));
                    @endphp

                    <tr data-haystack="{{ $hay }}"
                        data-kat="{{ $kategori }}"
                        data-tipe="{{ $tipeRaw }}"
                        data-lvl="{{ \Illuminate\Support\Str::lower($lvl) }}"
                        data-status="{{ $statusRaw }}">
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $text ?: '—' }}</div>
                            @if(!empty($row->lampiran))
                                <div class="text-xs text-gray-500 mt-1">Lampiran: tersedia</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $kategori }}</td>
                        <td class="px-4 py-3">{{ $tipeDisp }}</td>
                        <td class="px-4 py-3">{{ $lvlDisp }}</td>
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $opsiCount }} opsi</div>
                            <div class="text-xs text-gray-500">Kunci: {{ $kunciDisp }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded {{ $statusBadge }}">
                                {{ ucfirst($statusRaw) }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">Upd: {{ $updatedDisp }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if(Route::has('admin.soal.show'))
                                    <a href="{{ route('admin.soal.show', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">Detail</a>
                                @endif
                                @if(Route::has('admin.soal.edit'))
                                    <a href="{{ route('admin.soal.edit', $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Edit</a>
                                @endif
                                @if(Route::has('admin.soal.destroy'))
                                    <form method="POST" action="{{ route('admin.soal.destroy', $row->id) }}"
                                          onsubmit="return confirm('Hapus soal ini? Tindakan tidak dapat dibatalkan.');">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center px-2.5 py-1.5 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
                                    </form>
                                @endif
                                @if(!Route::has('admin.soal.show') && !Route::has('admin.soal.edit') && !Route::has('admin.soal.destroy'))
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
