<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Formulir Pendaftaran (Daftar Pesantren)
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        {{-- flash sukses --}}
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        @php
            // dianggap "sudah pernah isi" jika salah satu entitas ada
            $sudahIsi = ($dataDiri ?? null) || ($wali ?? null) || ($tujuan ?? null) || ($psb ?? null) || ($prestasi ?? null) || ($kebutuhan ?? null);
            // logika 2 lapis:
            // - jika user sudah pernah isi -> default tampil FORM (edit) kecuali dipaksa ?form=0
            // - jika user belum isi -> default tampil WELCOME, tampil FORM jika ?form=1
            $showForm = request()->has('form')
                ? request()->boolean('form')
                : $sudahIsi; // default
        @endphp

        {{-- ===================== LAPIS 1: WELCOME / AJAKAN MENGISI ===================== --}}
        @unless ($showForm)
            <section class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-1">Selamat datang 👋</h3>
                <p class="text-gray-600">
                    Untuk mengikuti proses PSB, silakan lengkapi <span class="font-medium">Formulir Pendaftaran</span> terlebih dahulu.
                </p>

                <ul class="mt-4 list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li>Isi <span class="font-medium">Data Diri</span> dan <span class="font-medium">Data Wali</span>.</li>
                    <li>Pilih <span class="font-medium">Pendidikan Tujuan</span> dan asal <span class="font-medium">Informasi PSB</span>.</li>
                    <li>Opsional: unggah foto dan isi <span class="font-medium">Prestasi</span> / <span class="font-medium">Keterangan Kebutuhan Khusus</span>.</li>
                </ul>

                <div class="mt-6 flex items-center gap-3">
                    <a href="{{ url()->current() }}?form=1"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                        Mulai isi formulir
                    </a>
                    <a href="{{ route('pendaftar.jadwal') }}"
                       class="text-sm text-gray-600 hover:text-gray-800">
                        Lihat jadwal seleksi
                    </a>
                </div>
            </section>
        @endunless

        {{-- ===================== LAPIS 2: FORMULIR ===================== --}}
        @if ($showForm)
            {{-- banner kecil bila sudah pernah isi --}}
            @if ($sudahIsi)
                <div class="mb-4 p-3 rounded bg-blue-50 text-blue-700 border border-blue-200">
                    Kamu sudah pernah mengisi formulir. Silakan <b>perbarui</b> datamu jika ada yang berubah.
                    <a href="{{ route('pendaftar.jadwal') }}" class="underline ml-1">Lihat jadwal seleksi</a>
                </div>
            @endif

            <form action="{{ route('pendaftar.daftar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf

                {{-- ========== DATA WALI ========== --}}
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

                {{-- ========== DATA DIRI ========== --}}
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
                                   value="{{ old('tanggal_lahir', $dataDiri->tanggal_lahir ?? '') }}" required>
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

                {{-- ========== PENDIDIKAN TUJUAN ========== --}}
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

                {{-- ========== INFORMASI PSB ========== --}}
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

                {{-- ========== PRESTASI (opsional) ========== --}}
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

                {{-- ========== PENYAKIT & KEBUTUHAN KHUSUS (opsional) ========== --}}
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

                <div class="flex items-center justify-between">
                    <a href="{{ url()->current() }}?form=0" class="text-sm text-gray-600 hover:text-gray-800">
                        Kembali ke halaman pengantar
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Simpan Formulir
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
