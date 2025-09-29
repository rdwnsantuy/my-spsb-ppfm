<x-app-layout>
    <x-slot name="header">
        @php  $items = $items ?? collect();  @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Data Pendaftar
        </h2>
    </x-slot>

    @php
        // Siapkan opsi filter "Pendidikan Tujuan" dari data yang tampil (aman untuk paginator/collection)
        $tujuanSet = [];
        foreach ($items as $row) {
            $tujuan = data_get($row, 'pendidikanTujuan.pendidikan_tujuan')
                ?? data_get($row, 'user.pendidikanTujuan.pendidikan_tujuan')
                ?? ($row->pendidikan_tujuan ?? null);
            if ($tujuan) { $tujuanSet[] = $tujuan; }
        }
        $tujuanOptions = array_values(array_unique($tujuanSet));
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

        {{-- Action bar: search + filter + quick actions --}}
        <section class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs text-gray-500">Pencarian</label>
                    <input id="q" type="text" placeholder="Cari nama/email/tujuan…"
                           class="mt-1 w-full rounded border-gray-300"
                           oninput="filterRows()">
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Pendidikan Tujuan</label>
                    <select id="f_tujuan" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        @foreach($tujuanOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Status Pemb. Pendaftaran</label>
                    <select id="f_pend" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        <option value="accepted">Accepted</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                        <option value="none">Belum ada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Status Pemb. Daftar Ulang</label>
                    <select id="f_du" class="mt-1 rounded border-gray-300" onchange="filterRows()">
                        <option value="">Semua</option>
                        <option value="accepted">Accepted</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                        <option value="none">Belum ada</option>
                    </select>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.export'))
                        <a href="{{ route('admin.pendaftar.export') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-sm hover:bg-gray-50">
                            Export CSV
                        </a>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.create'))
                        <a href="{{ route('admin.pendaftar.create') }}"
                           class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                            Tambah Pendaftar
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
                    <th class="px-4 py-3 text-left w-[28%]">Pendaftar</th>
                    <th class="px-4 py-3 text-left w-[16%]">Pendidikan Tujuan</th>
                    <th class="px-4 py-3 text-left w-[18%]">Pendaftaran</th>
                    <th class="px-4 py-3 text-left w-[18%]">Daftar Ulang</th>
                    <th class="px-4 py-3 text-left w-[12%]">Terdaftar</th>
                    <th class="px-4 py-3 text-left w-[8%]">Aksi</th>
                </tr>
                </thead>
                <tbody id="tbody" class="divide-y divide-gray-100">
                @forelse($items as $row)
                    @php
                        // Ambil user/nama/email
                        $u      = $row->user ?? $row;
                        $name   = data_get($row, 'dataDiri.nama_lengkap') ?? ($u->name ?? '-');
                        $email  = $u->email ?? '-';

                        // Pendidikan tujuan
                        $tujuan = data_get($row, 'pendidikanTujuan.pendidikan_tujuan')
                            ?? data_get($u ?? null, 'pendidikanTujuan.pendidikan_tujuan')
                            ?? ($row->pendidikan_tujuan ?? '—');

                        // Pembayaran pendaftaran & daftar ulang (latest)
                        $bayarP = $row->pembayaranPendaftaran ?? $row->lastPembayaranPendaftaran ?? null;
                        if (is_object($bayarP) && method_exists($bayarP, 'sortByDesc')) { $bayarP = $bayarP->sortByDesc('created_at')->first(); }
                        $bayarU = $row->pembayaranDaftarUlang ?? $row->lastPembayaranDaftarUlang ?? null;
                        if (is_object($bayarU) && method_exists($bayarU, 'sortByDesc')) { $bayarU = $bayarU->sortByDesc('created_at')->first(); }

                        // Status map
                        $mapBadge = fn($s) => [
                            'accepted' => 'bg-green-100 text-green-700',
                            'pending'  => 'bg-yellow-100 text-yellow-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ][$s] ?? 'bg-gray-100 text-gray-700';

                        $sP = $bayarP->status ?? null;
                        $sU = $bayarU->status ?? null;

                        $badgeP = $mapBadge($sP);
                        $badgeU = $mapBadge($sU);

                        $sPDisp = $sP ? ucfirst($sP) : 'Belum ada';
                        $sUDisp = $sU ? ucfirst($sU) : 'Belum ada';

                        $noteP = $bayarP->note ?? $bayarP->catatan ?? null;
                        $noteU = $bayarU->note ?? $bayarU->catatan ?? null;

                        $buktiP = !empty($bayarP?->foto_bukti) ? asset('storage/'.$bayarP->foto_bukti) : null;
                        $buktiU = !empty($bayarU?->foto_bukti) ? asset('storage/'.$bayarU->foto_bukti) : null;

                        $created = $u->created_at ?? $row->created_at ?? null;

                        // haystack utk search/filter client-side
                        $hay = \Illuminate\Support\Str::lower(trim($name.' '.$email.' '.$tujuan.' '.($sP ?? 'none').' '.($sU ?? 'none')));
                        $fPend = $sP ?? 'none';
                        $fDu   = $sU ?? 'none';
                    @endphp

                    <tr data-haystack="{{ $hay }}" data-tujuan="{{ $tujuan }}" data-pend="{{ $fPend }}" data-du="{{ $fDu }}">
                        {{-- Pendaftar --}}
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $name }}</div>
                            <div class="text-xs text-gray-500">{{ $email }}</div>
                        </td>

                        {{-- Tujuan --}}
                        <td class="px-4 py-3">{{ $tujuan ?: '—' }}</td>

                        {{-- Pembayaran Pendaftaran --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badgeP }}">
                                    {{ $sPDisp }}
                                </span>
                                @if($buktiP)
                                    <a href="{{ $buktiP }}" target="_blank" class="text-xs text-indigo-600 hover:underline">Lihat bukti</a>
                                @endif
                            </div>
                            @if($noteP)
                                <div class="text-xs text-gray-500 mt-1">Catatan: {{ $noteP }}</div>
                            @endif
                        </td>

                        {{-- Pembayaran Daftar Ulang --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badgeU }}">
                                    {{ $sUDisp }}
                                </span>
                                @if($buktiU)
                                    <a href="{{ $buktiU }}" target="_blank" class="text-xs text-indigo-600 hover:underline">Lihat bukti</a>
                                @endif
                            </div>
                            @if($noteU)
                                <div class="text-xs text-gray-500 mt-1">Catatan: {{ $noteU }}</div>
                            @endif
                        </td>

                        {{-- Terdaftar --}}
                        <td class="px-4 py-3">
                            {{ $created ? \Illuminate\Support\Carbon::parse($created)->format('d/m/Y H:i') : '—' }}
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.show'))
                                    <a href="{{ route('admin.pendaftar.show', $u->id ?? $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                                        Detail
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.edit'))
                                    <a href="{{ route('admin.pendaftar.edit', $u->id ?? $row->id) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                        Edit
                                    </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.pendaftar.destroy'))
                                    <form method="POST" action="{{ route('admin.pendaftar.destroy', $u->id ?? $row->id) }}"
                                          onsubmit="return confirm('Hapus pendaftar ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded bg-red-600 text-white text-xs hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                                @if(
                                    !\Illuminate\Support\Facades\Route::has('admin.pendaftar.show') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.pendaftar.edit') &&
                                    !\Illuminate\Support\Facades\Route::has('admin.pendaftar.destroy')
                                )
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                            Belum ada data pendaftar.
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
            const tujuan = (document.getElementById('f_tujuan').value || '').trim();
            const fPend = (document.getElementById('f_pend').value || '').trim();
            const fDu   = (document.getElementById('f_du').value || '').trim();

            document.querySelectorAll('#tbody > tr').forEach(tr => {
                const hay   = (tr.getAttribute('data-haystack') || '').toLowerCase();
                const tj    = tr.getAttribute('data-tujuan') || '';
                const sPend = tr.getAttribute('data-pend')   || 'none';
                const sDu   = tr.getAttribute('data-du')     || 'none';

                const hitQ    = !q     || hay.includes(q);
                const hitTj   = !tujuan|| tj === tujuan;
                const hitPend = !fPend || sPend === fPend;
                const hitDu   = !fDu   || sDu   === fDu;

                tr.style.display = (hitQ && hitTj && hitPend && hitDu) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
