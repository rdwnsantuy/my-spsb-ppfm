<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Verifikasi Pembayaran
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
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
                    <tr>
                        <td class="px-4 py-3 text-sm capitalize">
                            {{ str_replace('-', ' ', $row->jenis) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900">{{ $row->user->name }}</div>
                            <div class="text-gray-500 text-xs">{{ $row->user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($row->foto_bukti)
                                <a href="{{ asset('storage/'.$row->foto_bukti) }}" target="_blank" class="inline-block">
                                    <img src="{{ asset('storage/'.$row->foto_bukti) }}" class="h-16 w-16 object-cover rounded border" alt="Bukti">
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = [
                                    'pending'  => 'bg-yellow-100 text-yellow-800',
                                    'accepted' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                ][$row->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badge }}">
                                {{ ucfirst($row->status) }}
                            </span>

                            @if($row->catatan)
                                <div class="text-xs text-gray-500 mt-1">Catatan: {{ $row->catatan }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($row->verified_by)
                                <div>{{ $row->verified_by }}</div>
                                <div class="text-xs text-gray-500">{{ optional($row->verified_at)->format('d M Y H:i') }}</div>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($row->status === 'pending')
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.verifikasi-pembayaran.terima', [$row->jenis, $row->id]) }}">
                                        @csrf
                                        <input type="hidden" name="catatan" value="">
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700">
                                            Terima
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.verifikasi-pembayaran.tolak', [$row->jenis, $row->id]) }}"
                                          onsubmit="return confirm('Tolak pembayaran ini?');">
                                        @csrf
                                        <input type="text" name="catatan" placeholder="Alasan penolakan"
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
        </div>
    </div>
</x-app-layout>
