<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Seleksi</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        @if (session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- ================= KARTU TAGIHAN PENDAFTARAN (STATIS) ================= --}}
        @php
            $biayaPendaftaran = 'Rp. 100.000,00';
            $rekeningResmi = [
                ['1244678209236', 'Mandiri'],
                ['145789276543862', 'BRI'],
                ['1457629875', 'BNI'],
            ];
        @endphp

        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-5 border-b">
                <h3 class="font-semibold text-lg">Selesaikan registrasi pendaftaran untuk lanjut ke tahap seleksi.</h3>
            </div>

            <div class="px-6 py-5">
                {{-- Ringkasan tagihan --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 rounded">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left w-1/2">Keterangan</th>
                                <th class="px-4 py-3 text-left w-1/4">Nominal</th>
                                <th class="px-4 py-3 text-left w-1/4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t">
                                <td class="px-4 py-3">Registrasi Pendaftaran Santri Baru</td>
                                <td class="px-4 py-3 font-medium">{{ $biayaPendaftaran }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 text-orange-600">
                                        <span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                                        Harus Dibayarkan
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Info rekening --}}
                <p class="mt-5 text-sm text-gray-600">
                    Pembayaran dapat dilakukan melalui kanal pembayaran yang telah disediakan.
                </p>

                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 rounded">
                        <thead>
                            <tr>
                                <th colspan="2" class="px-4 py-3 text-left bg-gray-100 text-gray-700">
                                    Kanal Resmi Pembayaran Registrasi Pendaftaran Santri Baru PPFM
                                </th>
                            </tr>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="px-4 py-2 text-left">Nomor Rekening</th>
                                <th class="px-4 py-2 text-left">Bank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rekeningResmi as [$no, $bank])
                                <tr class="border-t">
                                    <td class="px-4 py-3 font-medium tracking-wide">{{ $no }}</td>
                                    <td class="px-4 py-3">{{ $bank }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-500">
                        * Nomor rekening & jumlah tagihan bersifat tetap. Admin akan mencocokkan bukti yang kamu unggah.
                    </p>
                </div>
            </div>
        </section>

        {{-- ================= BLOK PEMBAYARAN PENDAFTARAN ================= --}}
        @php
            $canUploadPendaftaran = !($bayarP && $bayarP->status === 'accepted');
            $badge = match($bayarP->status ?? '—') {
                'accepted' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                'pending'  => 'bg-yellow-100 text-yellow-700',
                default    => 'bg-gray-100 text-gray-700'
            };
            $label = $bayarP->status ?? 'Belum ada';
        @endphp

        <section class="bg-white rounded-lg shadow">
            <div class="px-6 py-5 border-b flex items-center justify-between">
                <h3 class="font-semibold text-lg">Pembayaran Pendaftaran</h3>
                <div class="text-sm">
                    <span class="text-gray-600 mr-2">Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badge }}">{{ ucfirst($label) }}</span>
                    @if($bayarP?->foto_bukti)
                        <a href="{{ asset('storage/'.$bayarP->foto_bukti) }}" target="_blank"
                           class="ml-3 text-indigo-600 hover:underline">Lihat bukti</a>
                    @endif
                </div>
            </div>

            <div class="px-6 py-5 space-y-3">
                @if($bayarP?->status === 'rejected' && $bayarP?->catatan)
                    <div class="p-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm">
                        Catatan admin: {{ $bayarP->catatan }}
                    </div>
                @endif

                <form class="flex flex-col sm:flex-row sm:items-center gap-3"
                      action="{{ route('pendaftar.payment.store', 'pendaftaran') }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf

                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.webp"
                           @disabled(!$canUploadPendaftaran)
                           class="block w-full sm:max-w-xs text-sm border-gray-300 rounded disabled:bg-gray-100 disabled:text-gray-400">

                    <button type="submit"
                            @disabled(!$canUploadPendaftaran)
                            class="w-full sm:w-auto px-4 py-2 rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload Bukti Pendaftaran
                    </button>
                </form>

                @unless($canUploadPendaftaran)
                    <p class="text-xs text-gray-500">Bukti sudah diterima (accepted). Pengunggahan dinonaktifkan.</p>
                @endunless
            </div>
        </section>

        {{-- ================= DAFTAR JADWAL (placeholder) ================= --}}
        <section class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-lg">Daftar Jadwal</h3>
            <p class="text-sm text-gray-600 mt-2">Jadwal seleksi kamu akan muncul di sini.</p>
        </section>
    </div>
</x-app-layout>
