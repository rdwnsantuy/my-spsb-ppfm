<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Data Pendaftar
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">

        {{-- Flash --}}
        @if (session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Aksi cepat --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">Ringkasan semua data yang sudah kamu isi.</p>
            <a href="{{ route('pendaftar.data-pendaftar.edit') }}"
               class="inline-flex items-center px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                Ubah / Lengkapi Data
            </a>
        </div>

        {{-- ==================== DATA DIRI ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Data Diri</h3>

                @if($dataDiri)
                    <div class="grid md:grid-cols-12 gap-6">
                        <div class="md:col-span-8">
                            <div class="rounded-md border divide-y divide-gray-200">
                                @php
                                    $rows = [
                                        ['Nama Lengkap', $dataDiri->nama_lengkap],
                                        ['Jenis Kelamin', $dataDiri->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'],
                                        ['Kabupaten Lahir', $dataDiri->kabupaten_lahir],
                                        ['Tanggal Lahir', $dataDiri->tanggal_lahir ? \Illuminate\Support\Carbon::parse($dataDiri->tanggal_lahir)->translatedFormat('d M Y') : '—'],
                                        ['NISN', $dataDiri->nisn ?? '—'],
                                        ['Alamat Domisili', $dataDiri->alamat_domisili],
                                        ['No. KK', $dataDiri->no_kk ?? '—'],
                                    ];
                                @endphp

                                @foreach($rows as [$label, $value])
                                    <div class="flex items-start">
                                        <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">{{ $label }}</div>
                                        <div class="flex-1 px-4 py-2 text-sm">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="md:col-span-4 space-y-6">
                            {{-- Foto Diri --}}
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Foto Diri</div>
                                @if($dataDiri->foto_diri)
                                    <a href="{{ asset('storage/'.$dataDiri->foto_diri) }}" target="_blank">
                                        <img src="{{ asset('storage/'.$dataDiri->foto_diri) }}" class="rounded-md border w-40 h-40 object-cover" alt="Foto Diri">
                                    </a>
                                @else
                                    <div class="text-gray-400 text-sm">Belum diunggah</div>
                                @endif
                            </div>

                            {{-- Foto KK --}}
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Foto KK</div>
                                @if($dataDiri->foto_kk)
                                    @php
                                        $isImage = \Illuminate\Support\Str::endsWith(strtolower($dataDiri->foto_kk), ['.jpg','.jpeg','.png','.webp']);
                                    @endphp
                                    @if($isImage)
                                        <a href="{{ asset('storage/'.$dataDiri->foto_kk) }}" target="_blank">
                                            <img src="{{ asset('storage/'.$dataDiri->foto_kk) }}" class="rounded-md border w-40 h-40 object-cover" alt="Foto KK">
                                        </a>
                                    @else
                                        <a href="{{ asset('storage/'.$dataDiri->foto_kk) }}"
                                           class="text-indigo-600 underline text-sm" target="_blank">Lihat berkas</a>
                                    @endif
                                @else
                                    <div class="text-gray-400 text-sm">Belum diunggah</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Belum ada data.</div>
                @endif
            </div>
        </section>

        {{-- ==================== DATA WALI ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Data Wali</h3>

                @if($wali->count())
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($wali as $w)
                            <div class="rounded-md border divide-y divide-gray-200">
                                <div class="flex items-start">
                                    <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Hubungan</div>
                                    <div class="flex-1 px-4 py-2 text-sm capitalize">{{ $w->hubungan_wali }}</div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Nama</div>
                                    <div class="flex-1 px-4 py-2 text-sm">{{ $w->nama_wali }}</div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Penghasilan</div>
                                    <div class="flex-1 px-4 py-2 text-sm">
                                        {{ $w->rerata_penghasilan ? 'Rp. '.number_format($w->rerata_penghasilan,0,',','.') : '—' }}
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">No. Telp</div>
                                    <div class="flex-1 px-4 py-2 text-sm">{{ $w->no_telp ?? '—' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-gray-500">Belum ada data.</div>
                @endif
            </div>
        </section>

        {{-- ==================== PENDIDIKAN TUJUAN ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Pendidikan Tujuan</h3>

                @if($tujuan)
                    <div class="rounded-md border divide-y divide-gray-200">
                        <div class="flex items-start">
                            <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Tujuan</div>
                            <div class="flex-1 px-4 py-2 text-sm">{{ $tujuan->pendidikan_tujuan ?? '—' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Belum diisi.</div>
                @endif
            </div>
        </section>

        {{-- ==================== INFORMASI PSB ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Informasi PSB</h3>

                @if($psb)
                    <div class="rounded-md border divide-y divide-gray-200">
                        <div class="flex items-start">
                            <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Sumber</div>
                            <div class="flex-1 px-4 py-2 text-sm">{{ $psb->informasi_psb ?? '—' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Belum diisi.</div>
                @endif
            </div>
        </section>

        {{-- ==================== PRESTASI ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Prestasi</h3>

                @php
                    $hasPrestasi = $prestasi && (
                        $prestasi->prestasi_i ||
                        $prestasi->prestasi_ii ||
                        $prestasi->prestasi_iii
                    );
                @endphp

                @if($hasPrestasi)
                    <div class="rounded-md border divide-y divide-gray-200">
                        @if($prestasi->prestasi_i)
                            <div class="flex items-start">
                                <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Prestasi I</div>
                                <div class="flex-1 px-4 py-2 text-sm">{{ $prestasi->prestasi_i }}</div>
                            </div>
                        @endif
                        @if($prestasi->prestasi_ii)
                            <div class="flex items-start">
                                <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Prestasi II</div>
                                <div class="flex-1 px-4 py-2 text-sm">{{ $prestasi->prestasi_ii }}</div>
                            </div>
                        @endif
                        @if($prestasi->prestasi_iii)
                            <div class="flex items-start">
                                <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Prestasi III</div>
                                <div class="flex-1 px-4 py-2 text-sm">{{ $prestasi->prestasi_iii }}</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-gray-500">Belum diisi.</div>
                @endif
            </div>
        </section>

        {{-- ==================== PENYAKIT & KEBUTUHAN KHUSUS ==================== --}}
        <section class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-lg mb-4">Penyakit & Kebutuhan Khusus</h3>

                @if($kebutuhan && ($kebutuhan->deskripsi || $kebutuhan->tingkat))
                    <div class="rounded-md border divide-y divide-gray-200">
                        <div class="flex items-start">
                            <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Deskripsi</div>
                            <div class="flex-1 px-4 py-2 text-sm">{{ $kebutuhan->deskripsi ?? '—' }}</div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-48 shrink-0 bg-gray-50 px-4 py-2 text-sm text-gray-500">Tingkat</div>
                            <div class="flex-1 px-4 py-2 text-sm">{{ $kebutuhan->tingkat ? ucfirst($kebutuhan->tingkat) : '—' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Belum ada data.</div>
                @endif
            </div>
        </section>

    </div>
</x-app-layout>
