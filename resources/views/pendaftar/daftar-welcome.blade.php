<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Pesantren</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        {{-- Flash messages --}}
        @if(session('ok'))
            <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif
        @if(session('warn'))
            <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warn') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Selamat datang</h3>
            <p class="text-gray-600">
                Silakan lengkapi formulir pendaftaran terlebih dahulu untuk melanjutkan ke proses seleksi.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                {{-- CTA utama --}}
                <a href="{{ route('pendaftar.daftar.form') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Isi Formulir Pendaftaran
                </a>

                {{-- CTA opsional, aman jika route tersedia --}}
                @if(\Illuminate\Support\Facades\Route::has('pendaftar.data-pendaftar'))
                    <a href="{{ route('pendaftar.data-pendaftar') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200">
                        Lihat Data Saya
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('pendaftar.jadwal'))
                    <a href="{{ route('pendaftar.jadwal') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Ke Jadwal Seleksi
                    </a>
                @endif
            </div>

            <hr class="my-6">

            <div class="text-sm text-gray-600 space-y-2">
                <p><span class="font-medium">Catatan:</span> siapkan data berikut sebelum mengisi:</p>
                <ul class="list-disc list-inside">
                    <li>Data diri lengkap (NISN, alamat domisili, tanggal lahir).</li>
                    <li>Foto diri & kartu keluarga (KK) dalam format JPG/PNG/PDF.</li>
                    <li>Nomor telepon wali yang aktif.</li>
                </ul>
            </div>
        </section>
    </div>
</x-app-layout>
