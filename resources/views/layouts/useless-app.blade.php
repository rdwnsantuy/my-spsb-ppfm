<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    @includeIf('layouts.navigation')

    <main class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            {{-- flash messages (opsional) --}}
            @if (session('ok'))
              <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
              </div>
            @endif
            @if ($errors->any())
              <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                <ul class="ms-4 list-disc">
                  @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            @yield('content')
        </div>
    </main>

    @includeIf('layouts.footer')

    @stack('scripts')
</body>
</html>
