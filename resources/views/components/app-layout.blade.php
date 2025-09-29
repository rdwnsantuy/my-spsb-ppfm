{{-- resources/views/components/app-layout.blade.php --}}
@props(['title' => config('app.name')])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen antialiased">

    {{-- Sidebar/Topbar (nama file yang benar: layouts/navigation) --}}
    @includeIf('layouts.navigation')

    <main class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

            {{-- Header slot dari <x-slot name="header"> --}}
            @isset($header)
                <div class="mb-4">
                    {{ $header }}
                </div>
            @endisset

            {{-- Flash messages --}}
            @if (session('ok'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                    {{ session('ok') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                    {{ session('warning') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            {{-- INI YANG MENAMPILKAN KONTEN HALAMAN --}}
            {{ $slot }}
        </div>
    </main>

    @includeIf('layouts.footer')
    @stack('scripts')
</body>
</html>
