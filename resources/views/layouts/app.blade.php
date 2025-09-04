<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name','Laravel') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <!-- padding kiri 64 (256px) saat desktop -->
    <div class="min-h-screen bg-gray-100 md:pl-64">
        @include('layouts.navigation')  {{-- penting --}}

@isset($header)
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto h-16 flex items-center px-4 sm:px-6 lg:px-8">
        {{ $header }}
    </div>
</header>
@endisset


        <main class="py-6">
            <div class="px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>

</html>
