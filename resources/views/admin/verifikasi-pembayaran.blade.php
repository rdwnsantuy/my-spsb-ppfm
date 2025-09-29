<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Verifikasi Pembayaran
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-4">
        {{-- Flash --}}
        @if (session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Pendaftar</th>
                        <th class="px-4 py-3">Bukti</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Verifikator</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($items as $row)
                    @php
                        // Jenis (title case)
                        $jenisLabel = \Illuminate\Support\Str::of($row->jenis ?? '-')
                                        ->replace(['_', '-'], ' ')->title();

                        // Bukti
                        $buktiPath  = $row->foto_bukti ? asset('storage/'.$row->foto_bukti) : null;
                        $isImage    = $row->foto_bukti
                                        && \Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower($row->foto_bukti), ['.jpg','.jpeg','.png','.webp']);
                        $isPdf      = $row->foto_bukti
                                        && \Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower($row->foto_bukti), ['.pdf']);

                        // Badge status
                        $badgeCls = [
                            'pending'  => 'bg-yellow-100 text-yellow-800',
                            'accepted' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                        ][$row->status] ?? 'bg-gray-100 text-gray-800';

                        // Catatan (dukung field lama 'catatan')
                        $note = $row->note ?? $row->catatan ?? null;

                        // Verifikator
                        $verifierName = optional($row->verifier ?? null)->name ?? null;
                        $verifierId   = $row->verified_by ?? null;
                        $verifiedAt   = $row->verified_at ? \Illuminate\Support\Carbon::parse($row->verified_at) : null;
                    @endphp

                    <tr class="align-top">
                        {{-- Jenis --}}
                        <td class="px-4 py-3 text-sm capitalize">
                            {{ $jenisLabel }}
                            @if(!empty($row->created_at))
                                <div class="text-xs text-gray-500">
                                    Diunggah: {{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y H:i') }}
                                </div>
                            @endif
                        </td>

                        {{-- Pendaftar --}}
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900">{{ optional($row->user)->name ?? '—' }}</div>
                            <div class="text-gray-500 text-xs">{{ optional($row->user)->email ?? '—' }}</div>
                        </td>

                        {{-- Bukti --}}
                        <td class="px-4 py-3">
                            @if($buktiPath)
                                @if($isImage)
                                    <a href="{{ $buktiPath }}" target="_blank" class="inline-block">
                                        <img src="{{ $buktiPath }}" class="h-16 w-16 object-cover rounded border" alt="Bukti">
                                    </a>
                                @elseif($isPdf)
                                    <a href="{{ $buktiPath }}" target="_blank"
                                       class="inline-flex items-center px-2 py-1 rounded border text-xs hover:bg-gray-50">
                                        Lihat PDF
                                    </a>
                                @else
                                    <a href="{{ $buktiPath }}" target="_blank" class="text-indigo-600 underline text-sm">
                                        Lihat berkas
                                    </a>
                                @endif
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>

                        {{-- Status + Catatan --}}
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeCls }}">
                                {{ ucfirst($row->status ?? '-') }}
                            </span>
                            @if($note)
                                <div class="text-xs text-gray-500 mt-1">Catatan: {{ $note }}</div>
                            @endif
                        </td>

                        {{-- Verifikator --}}
                        <td class="px-4 py-3 text-sm">
                            @if($verifierName || $verifierId)
                                <div>{{ $verifierName ?? ('ID: '.$verifierId) }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $verifiedAt?->format('d M Y H:i') ?? '' }}
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3">
                            @if(($row->status ?? '') === 'pending')
                                <div class="flex items-center gap-2">
                                    {{-- TERIMA --}}
                                    <form method="POST"
                                          action="{{ route('admin.verifikasi-pembayaran.terima', [$row->jenis, $row->id]) }}"
                                          onsubmit="return confirm('Terima pembayaran ini?');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700">
                                            Terima
                                        </button>
                                    </form>

                                    {{-- TOLAK --}}
                                    <form method="POST"
                                          action="{{ route('admin.verifikasi-pembayaran.tolak', [$row->jenis, $row->id]) }}"
                                          onsubmit="return this.alasan.value.trim() ? confirm('Tolak pembayaran ini?') : (alert('Alasan penolakan wajib diisi'), false);">
                                        @csrf
                                        <input type="text" name="alasan" placeholder="Alasan penolakan"
                                               class="border-gray-300 rounded text-sm px-2 py-1" required>
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm bg-red-600 text-white hover:bg-red-700">
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">Tidak ada aksi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 text-sm">
                            Belum ada bukti pembayaran yang diunggah.
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
</x-app-layout>
