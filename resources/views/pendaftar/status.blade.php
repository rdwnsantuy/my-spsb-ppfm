<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Status</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        {{-- Flash --}}
        @if (session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- ================= VARIABEL STATUS (PENDAFTARAN & DAFTAR ULANG) ================= --}}
        @php
            // Pendaftaran
            $statusP   = $bayarP->status ?? null; // accepted|pending|rejected|null
            $labelP    = $statusP ? ucfirst($statusP) : 'Belum ada';
            $noteP     = $bayarP->note ?? $bayarP->catatan ?? null;
            $badgeP    = match($statusP ?? '—') {
                'accepted' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                'pending'  => 'bg-yellow-100 text-yellow-700',
                default    => 'bg-gray-100 text-gray-700'
            };

            // Daftar Ulang
            $statusU   = $bayarU->status ?? null; // accepted|pending|rejected|null
            $labelU    = $statusU ? ucfirst($statusU) : 'Belum ada';
            $noteU     = $bayarU->note ?? $bayarU->catatan ?? null;
            $badgeU    = match($statusU ?? '—') {
                'accepted' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                'pending'  => 'bg-yellow-100 text-yellow-700',
                default    => 'bg-gray-100 text-gray-700'
            };
            $canUploadDU = $statusU !== 'accepted'; // nonaktif jika sudah diterima
        @endphp

        {{-- ================= KARTU TAGIHAN DAFTAR ULANG (STATIS) ================= --}}
        @php
            $biayaDaftarUlang = 'Rp. 1.500.000,00';
            $rekeningResmi = [
                ['1244678209236', 'Mandiri'],
                ['145789276543862', 'BRI'],
                ['1457629875', 'BNI'],
            ];
        @endphp

        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-5 border-b">
                <h3 class="font-semibold text-lg">Lakukan pembayaran daftar ulang untuk finalisasi penerimaan.</h3>
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
                                <td class="px-4 py-3">Pembayaran Daftar Ulang Santri Baru</td>
                                <td class="px-4 py-3 font-medium">{{ $biayaDaftarUlang }}</td>
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
                                    Kanal Resmi Pembayaran Daftar Ulang Santri Baru PPFM
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

        {{-- ================= RINGKASAN PEMBAYARAN PENDAFTARAN ================= --}}
        <section class="bg-white rounded-lg shadow">
            <div class="px-6 py-5 border-b flex items-center justify-between">
                <h3 class="font-semibold text-lg">Pembayaran Pendaftaran</h3>
                <div class="text-sm">
                    <span class="text-gray-600 mr-2">Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badgeP }}">{{ $labelP }}</span>
                    @if($bayarP?->foto_bukti)
                        <a href="{{ asset('storage/'.$bayarP->foto_bukti) }}" target="_blank"
                           class="ml-3 text-indigo-600 hover:underline">Lihat bukti</a>
                    @endif
                </div>
            </div>
            <div class="px-6 py-5 space-y-3">
                @if(($statusP === 'pending' || $statusP === 'rejected') && $noteP)
                    <div class="p-3 rounded border border-yellow-200 bg-yellow-50 text-yellow-800 text-sm">
                        Catatan admin: {{ $noteP }}
                    </div>
                @endif

                <p class="text-xs text-gray-500">
                    * Upload/ganti bukti pendaftaran ada di halaman
                    <a href="{{ route('pendaftar.jadwal') }}" class="text-indigo-600 hover:underline">Jadwal Seleksi</a>.
                </p>
            </div>
        </section>

        {{-- ================= PEMBAYARAN DAFTAR ULANG (UPLOAD) ================= --}}
        <section class="bg-white rounded-lg shadow">
            <div class="px-6 py-5 border-b flex items-center justify-between">
                <h3 class="font-semibold text-lg">Pembayaran Daftar Ulang</h3>
                <div class="text-sm">
                    <span class="text-gray-600 mr-2">Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $badgeU }}">{{ $labelU }}</span>
                    @if($bayarU?->foto_bukti)
                        <a href="{{ asset('storage/'.$bayarU->foto_bukti) }}" target="_blank"
                           class="ml-3 text-indigo-600 hover:underline">Lihat bukti</a>
                    @endif
                </div>
            </div>

            <div class="px-6 py-5 space-y-3">
                @if(($statusU === 'pending' || $statusU === 'rejected') && $noteU)
                    <div class="p-3 rounded border border-yellow-200 bg-yellow-50 text-yellow-800 text-sm">
                        Catatan admin: {{ $noteU }}
                    </div>
                @endif

                <form class="flex flex-col sm:flex-row sm:items-center gap-3"
                      action="{{ route('pendaftar.payment.store', 'daftar_ulang') }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf

                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.webp"
                           @disabled(!$canUploadDU)
                           class="block w-full sm:max-w-xs text-sm border-gray-300 rounded disabled:bg-gray-100 disabled:text-gray-400">

                    <button type="submit"
                            @disabled(!$canUploadDU)
                            class="w-full sm:w-auto px-4 py-2 rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload Bukti Daftar Ulang
                    </button>
                </form>

                @unless($canUploadDU)
                    <p class="text-xs text-gray-500">Bukti sudah diterima (accepted). Pengunggahan dinonaktifkan.</p>
                @endunless
            </div>
        </section>
    </div>
</x-app-layout>
