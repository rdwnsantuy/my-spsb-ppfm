<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Pesantren</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-2">Selamat datang!</h3>
            <p class="text-gray-600">
                Silakan lengkapi formulir pendaftaran terlebih dahulu sebelum melanjutkan.
            </p>
            <a href="{{ route('pendaftar.daftar.form') }}"
               class="mt-4 inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                Isi Formulir Pendaftaran
            </a>
        </div>
    </div>
</x-app-layout>
