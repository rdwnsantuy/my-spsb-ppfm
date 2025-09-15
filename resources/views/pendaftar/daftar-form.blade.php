<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Formulir Pendaftaran (Daftar Pesantren)
        </h2>
    </x-slot>

    @php
        // guard untuk kasus view dipanggil tanpa variabel controller
        $isEdit = $isEdit ?? request()->routeIs('pendaftar.data-pendaftar.edit');
        $formAction = $isEdit
            ? route('pendaftar.data-pendaftar.update')
            : route('pendaftar.daftar.store');
        $backUrl = $isEdit
            ? route('pendaftar.data-pendaftar')
            : route('pendaftar.daftar');

        // nilai tanggal format Y-m-d supaya input date tidak minta isi ulang
        $valTanggal = old('tanggal_lahir')
            ?? ($valTanggal ?? (optional($dataDiri ?? null)->tanggal_lahir
                ? \Illuminate\Support\Carbon::parse($dataDiri->tanggal_lahir)->format('Y-m-d')
                : ''));
        // dianggap sudah isi bila salah satu entitas ada
        $sudahIsi = ($dataDiri ?? null) || ($wali ?? null) || ($tujuan ?? null) || ($psb ?? null) || ($prestasi ?? null) || ($kebutuhan ?? null);
    @endphp

    <div class="max-w-5xl mx-auto">
        {{-- flash sukses --}}
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- ===== ACTION BAR ATAS ===== --}}
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-gray-600">
                {{ $sudahIsi ? 'Perbarui data pendaftaranmu bila ada yang berubah.' : 'Lengkapi formulir pendaftaran terlebih dahulu.' }}
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10.828 12 16.95 5.879a1 1 0 1 0-1.414-1.415l-7.07 7.072a1 1 0 0 0 0 1.414l7.07 7.072a1 1 0 1 0 1.414-1.415L10.828 12Z"/>
                    </svg>
                    Kembali
                </a>
                <button form="form-daftar" type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17 5.53 12.7a1 1 0 1 0-1.41 1.41l4.17 4.17a1 1 0 0 0 1.41 0l9.17-9.17a1 1 0 1 0-1.41-1.41L9 16.17Z"/>
                    </svg>
                    Simpan
                </button>
            </div>
        </div>

        {{-- ===== FORM ===== --}}
        <form id="form-daftar" action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- DATA WALI --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Data Wali</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Nama Wali</label>
                        <input name="nama_wali" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('nama_wali', $wali->nama_wali ?? '') }}" required>
                        @error('nama_wali')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Hubungan Wali</label>
                        <select name="hubungan_wali" class="mt-1 w-full rounded border-gray-300" required>
                            <option value="">— Pilih —</option>
                            @foreach($optHubunganWali as $opt)
                                <option value="{{ $opt }}" @selected(old('hubungan_wali', $wali->hubungan_wali ?? '') === $opt)>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                        @error('hubungan_wali')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Rerata Penghasilan (Rp/bln)</label>
                        <input name="rerata_penghasilan" type="number" min="0" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('rerata_penghasilan', $wali->rerata_penghasilan ?? '') }}">
                        @error('rerata_penghasilan')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">No. Telepon Wali</label>
                        <input name="no_telp_wali" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('no_telp_wali', $wali->no_telp ?? '') }}">
                        @error('no_telp_wali')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- DATA DIRI --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Data Diri</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Nama Lengkap</label>
                        <input name="nama_lengkap" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('nama_lengkap', $dataDiri->nama_lengkap ?? auth()->user()->name) }}" required>
                        @error('nama_lengkap')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="mt-1 w-full rounded border-gray-300" required>
                            <option value="">— Pilih —</option>
                            <option value="L" @selected(old('jenis_kelamin', $dataDiri->jenis_kelamin ?? '')==='L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin', $dataDiri->jenis_kelamin ?? '')==='P')>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Kabupaten Lahir</label>
                        <input name="kabupaten_lahir" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('kabupaten_lahir', $dataDiri->kabupaten_lahir ?? '') }}" required>
                        @error('kabupaten_lahir')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Tanggal Lahir</label>
                        <input name="tanggal_lahir" type="date" class="mt-1 w-full rounded border-gray-300"
                               value="{{ $valTanggal }}" required>
                        @error('tanggal_lahir')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">NISN</label>
                        <input name="nisn" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('nisn', $dataDiri->nisn ?? '') }}">
                        @error('nisn')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Alamat Domisili</label>
                        <input name="alamat_domisili" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('alamat_domisili', $dataDiri->alamat_domisili ?? '') }}" required>
                        @error('alamat_domisili')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Foto Diri (jpg/png/webp, max 2MB)</label>
                        <input name="foto_diri" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full rounded border-gray-300">
                        @error('foto_diri')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Foto KK (jpg/png/webp/pdf, max 4MB)</label>
                        <input name="foto_kk" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-1 w-full rounded border-gray-300">
                        @error('foto_kk')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-gray-700">No. KK</label>
                        <input name="no_kk" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('no_kk', $dataDiri->no_kk ?? '') }}">
                        @error('no_kk')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- PENDIDIKAN TUJUAN --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Pendidikan Tujuan</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Pendidikan Tujuan</label>
                        <select name="pendidikan_tujuan" class="mt-1 w-full rounded border-gray-300" required>
                            <option value="">— Pilih —</option>
                            @foreach($optPendidikanTujuan as $opt)
                                <option value="{{ $opt }}" @selected(old('pendidikan_tujuan', $tujuan->pendidikan_tujuan ?? '') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('pendidikan_tujuan')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- INFORMASI PSB --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Informasi PSB</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Mengetahui PSB dari</label>
                        <select name="informasi_psb" class="mt-1 w-full rounded border-gray-300" required>
                            <option value="">— Pilih —</option>
                            @foreach($optInfoPsb as $opt)
                                <option value="{{ $opt }}" @selected(old('informasi_psb', $psb->informasi_psb ?? '') === $opt)>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                        @error('informasi_psb')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- PRESTASI (opsional) --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Prestasi (Opsional)</h3>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Prestasi I</label>
                        <input name="prestasi_i" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('prestasi_i', $prestasi->prestasi_i ?? '') }}">
                        @error('prestasi_i')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Prestasi II</label>
                        <input name="prestasi_ii" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('prestasi_ii', $prestasi->prestasi_ii ?? '') }}">
                        @error('prestasi_ii')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Prestasi III</label>
                        <input name="prestasi_iii" type="text" class="mt-1 w-full rounded border-gray-300"
                               value="{{ old('prestasi_iii', $prestasi->prestasi_iii ?? '') }}">
                        @error('prestasi_iii')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- PENYAKIT & KEBUTUHAN KHUSUS (opsional) --}}
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Penyakit &amp; Kebutuhan Khusus (Opsional)</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-gray-700">Deskripsi</label>
                        <textarea name="deskripsi" class="mt-1 w-full rounded border-gray-300" rows="3">{{ old('deskripsi', $kebutuhan->deskripsi ?? '') }}</textarea>
                        @error('deskripsi')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Tingkat</label>
                        <select name="tingkat" class="mt-1 w-full rounded border-gray-300">
                            <option value="">— Pilih —</option>
                            @foreach($optTingkat as $opt)
                                <option value="{{ $opt }}" @selected(old('tingkat', $kebutuhan->tingkat ?? '') === $opt)>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                        @error('tingkat')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
        </form>
    </div>
</x-app-layout>
