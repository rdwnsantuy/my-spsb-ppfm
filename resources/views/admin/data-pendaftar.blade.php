<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Pendaftar</h2>
    </x-slot>

    @php
        /** @var \Illuminate\Pagination\LengthAwarePaginator $rows */
        $rows = $rows ?? collect();
        $badge = function ($status) {
            $base = 'px-2 py-0.5 text-xs rounded';
            return match($status) {
                'accepted' => "<span class=\"$base bg-emerald-100 text-emerald-700\">accepted</span>",
                'pending'  => "<span class=\"$base bg-amber-100 text-amber-700\">pending</span>",
                'rejected' => "<span class=\"$base bg-rose-100 text-rose-700\">rejected</span>",
                '-', null  => "<span class=\"$base bg-gray-100 text-gray-600\">—</span>",
                default    => "<span class=\"$base bg-gray-100 text-gray-600\">$status</span>",
            };
        };
    @endphp

    <div class="max-w-7xl mx-auto">
        {{-- Flash --}}
        @foreach (['ok'=>'green','warning'=>'yellow','error'=>'red'] as $k => $color)
            @if(session($k))
                <div class="mb-3 p-3 rounded border bg-{{$color}}-50 text-{{$color}}-700 border-{{$color}}-200">
                    {{ session($k) }}
                </div>
            @endif
        @endforeach

        <div class="bg-white rounded shadow p-6 space-y-4">
            {{-- Filter (server-side, method GET) --}}
            <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500">Pencarian</label>
                    <input type="text" name="q" value="{{ $q }}"
                           class="mt-1 w-full rounded border-gray-300"
                           placeholder="Cari nama/email/username…">
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Pendidikan Tujuan</label>
                    <select name="tujuan" class="mt-1 w-full rounded border-gray-300">
                        @foreach ($opsTujuan as $opt)
                            <option value="{{ $opt }}" {{ $tujuan===$opt?'selected':'' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Status Pemb. Pendaftaran</label>
                    <select name="status_pend" class="mt-1 w-full rounded border-gray-300">
                        @foreach ($opsStatus as $opt)
                            <option value="{{ $opt }}" {{ $statusPend===$opt?'selected':'' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500">Status Pemb. Daftar Ulang</label>
                    <select name="status_du" class="mt-1 w-full rounded border-gray-300">
                        @foreach ($opsStatus as $opt)
                            <option value="{{ $opt }}" {{ $statusDu===$opt?'selected':'' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-5">
                    <button class="inline-flex items-center px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                        Terapkan
                    </button>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200 rounded">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Pendaftar</th>
                            <th class="px-4 py-3 text-left">Pendidikan Tujuan</th>
                            <th class="px-4 py-3 text-left">Pendaftaran</th>
                            <th class="px-4 py-3 text-left">Daftar Ulang</th>
                            <th class="px-4 py-3 text-left">Terdaftar</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($rows as $r)
                            @php
                                $pend = $r->status_pendaftaran;
                                $du   = $r->status_daftar_ulang;
                                $terdaftar = ($pend === 'accepted' && $du === 'accepted');
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $r->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $r->email }} · {{ '@'.$r->username }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ $r->pendidikan_tujuan ?? '—' }}
                                </td>

                                <td class="px-4 py-3">{!! $badge($pend) !!}</td>
                                <td class="px-4 py-3">{!! $badge($du) !!}</td>

                                <td class="px-4 py-3">
                                    @if ($terdaftar)
                                        <span class="px-2 py-0.5 text-xs rounded bg-emerald-100 text-emerald-700">Ya</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">Belum</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex gap-1.5">
                                        @if(Route::has('admin.pendaftar.show'))
                                            <a href="{{ route('admin.pendaftar.show', $r->id) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                                                Detail
                                            </a>
                                        @endif
                                        @if(Route::has('admin.pendaftar.edit'))
                                            <a href="{{ route('admin.pendaftar.edit', $r->id) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                                Edit
                                            </a>
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
            </div>

            {{-- Pagination --}}
            <div>
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
