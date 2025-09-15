<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Seleksi</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Kartu upload bukti PEMBAYARAN PENDAFTARAN --}}
        <section class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold text-lg mb-3">Pembayaran Pendaftaran</h3>

            @php
                $badge = fn($st) => match($st){
                    'accepted' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    'pending'  => 'bg-yellow-100 text-yellow-800',
                    default    => 'bg-gray-100 text-gray-800',
                };
            @endphp

            <div class="text-sm space-y-2">
                <div>
                    <span class="text-gray-500">Status:</span>
                    @if($bayarP)
                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded {{ $badge($bayarP->status) }}">
                            {{ ucfirst($bayarP->status) }}
                        </span>
                        @if($bayarP->catatan)
                            <div class="mt-1 text-xs text-gray-500">Catatan admin: {{ $bayarP->catatan }}</div>
                        @endif
                    @else
                        <span class="ml-2 text-gray-500">Belum ada bukti</span>
                    @endif
                </div>

                <div>
                    <span class="text-gray-500">Bukti:</span>
                    @if($bayarP?->foto_bukti)
                        <a href="{{ asset('storage/'.$bayarP->foto_bukti) }}" target="_blank" class="ml-2 underline text-indigo-600">Lihat bukti</a>
                    @else
                        <span class="ml-2 text-gray-500">—</span>
                    @endif
                </div>
            </div>

<form action="{{ route('pendaftar.payment.store','pendaftaran') }}"
      method="POST" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <input type="file" name="bukti" accept="image/*" required
           class="block w-full rounded border-gray-300"/>
    <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">
      Upload Bukti Pendaftaran
    </button>
</form>

        </section>

        {{-- Jika belum accepted, tampilkan blocking note --}}
        @if($blocked ?? false)
            <div class="p-4 rounded border bg-yellow-50 text-yellow-800 border-yellow-200">
                {{ $reason }}
                @if(!empty($note))
                    <div class="text-xs mt-1 text-yellow-700">Catatan admin: {{ $note }}</div>
                @endif
            </div>
        @else
            {{-- Tampilkan jadwal seleksi normal di sini --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-3">Daftar Jadwal</h3>
                <p class="text-sm text-gray-600">Jadwal seleksi kamu akan muncul di sini.</p>
            </section>
        @endif
    </div>
</x-app-layout>
