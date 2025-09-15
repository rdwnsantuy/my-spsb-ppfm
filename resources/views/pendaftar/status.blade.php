<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Status</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        @php
            $badge = fn($st) => match($st){
                'accepted' => 'bg-green-100 text-green-800',
                'rejected' => 'bg-red-100 text-red-800',
                'pending'  => 'bg-yellow-100 text-yellow-800',
                default    => 'bg-gray-100 text-gray-800',
            };
        @endphp

        <div class="grid md:grid-cols-2 gap-6">

            {{-- Ringkasan Pembayaran Pendaftaran (tanpa upload di sini) --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-3">Pembayaran Pendaftaran</h3>
                <div class="text-sm space-y-2">
                    <div>
                        <span class="text-gray-500">Status:</span>
                        @if($bayarP)
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded {{ $badge($bayarP->status) }}">{{ ucfirst($bayarP->status) }}</span>
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
                <div class="mt-3 text-xs text-gray-500">* Upload/ganti bukti pendaftaran ada di halaman <b>Jadwal Seleksi</b>.</div>
            </section>

            {{-- Pembayaran Daftar Ulang (upload di sini) --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-3">Pembayaran Daftar Ulang</h3>

                <div class="text-sm space-y-2">
                    <div>
                        <span class="text-gray-500">Status:</span>
                        @if($bayarU)
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded {{ $badge($bayarU->status) }}">{{ ucfirst($bayarU->status) }}</span>
                            @if($bayarU->catatan)
                                <div class="mt-1 text-xs text-gray-500">Catatan admin: {{ $bayarU->catatan }}</div>
                            @endif
                        @else
                            <span class="ml-2 text-gray-500">Belum ada bukti</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-gray-500">Bukti:</span>
                        @if($bayarU?->foto_bukti)
                            <a href="{{ asset('storage/'.$bayarU->foto_bukti) }}" target="_blank" class="ml-2 underline text-indigo-600">Lihat bukti</a>
                        @else
                            <span class="ml-2 text-gray-500">—</span>
                        @endif
                    </div>
                </div>

<form action="{{ route('pendaftar.payment.store','daftar_ulang') }}"
      method="POST" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <input type="file" name="bukti" accept="image/*" required
           class="block w-full rounded border-gray-300"/>
    <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">
      Upload Bukti Daftar Ulang
    </button>
</form>

            </section>
        </div>

        <div class="mt-6 text-xs text-gray-500">
            * Nomor rekening & jumlah tagihan bersifat tetap. Admin akan mencocokkan bukti yang kamu unggah.
        </div>
    </div>
</x-app-layout>
