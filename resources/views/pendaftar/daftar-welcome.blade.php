<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Pesantren</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        @if(session('warn'))
            <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warn') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Selamat datang</h3>
            <p class="text-gray-600 mb-6">
                Silakan lengkapi formulir pendaftaran terlebih dahulu untuk melanjutkan ke proses seleksi.
            </p>
            <a href="{{ route('pendaftar.daftar.form') }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                Isi Formulir Pendaftaran
            </a>
        </div>
    </div>
</x-app-layout>