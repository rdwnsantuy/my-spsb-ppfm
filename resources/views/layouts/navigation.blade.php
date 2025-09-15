{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }">
    @php
        $u = auth()->user();
        $isAdmin   = $u && $u->role === 'admin';
        // Cek apakah pendaftar sudah isi formulir (punya DataDiri)
        $hasFilled = $u && \App\Models\DataDiri::where('user_id', $u->id)->exists();
    @endphp

    {{-- ========== TOPBAR (mobile only) ========== --}}
    <div class="md:hidden bg-white border-b border-gray-200">
        <div class="px-4 h-16 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">
                {{ config('app.name', 'Laravel') }}
            </a>

            <button @click="open = !open" type="button"
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none">
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-cloak x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-show="open" x-transition class="border-t border-gray-200">
            <div class="py-2 space-y-1">

                @auth
                    @if ($isAdmin)
                        <a href="{{ route('admin.dashboard') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Dashboard</a>
                        <a href="{{ route('admin.verifikasi-pembayaran') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.verifikasi-pembayaran') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Verifikasi Pembayaran</a>
                        <a href="{{ route('admin.jadwal-seleksi') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.jadwal-seleksi') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Jadwal Seleksi</a>
                        <a href="{{ route('admin.data-pendaftar') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.data-pendaftar') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Data Pendaftar</a>
                        <a href="{{ route('admin.soal-seleksi') }}"
                           class="block px-4 py-2 text-sm {{ request()->routeIs('admin.soal-seleksi') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Soal Seleksi</a>
                    @else
                        {{-- Pendaftar --}}
                        @if (! $hasFilled)
                            <a href="{{ route('pendaftar.daftar') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('pendaftar.daftar*') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Daftar Pesantren</a>
                        @else
                            <a href="{{ route('pendaftar.jadwal') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('pendaftar.jadwal') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Jadwal Seleksi</a>
                            <a href="{{ route('pendaftar.data-pendaftar') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('pendaftar.data-pendaftar*') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Data Pendaftar</a>
                            <a href="{{ route('pendaftar.status') }}"
                               class="block px-4 py-2 text-sm {{ request()->routeIs('pendaftar.status') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Status</a>
                        @endif

                        <div class="px-4 pt-2 text-xs text-gray-500">{{ auth()->user()->email }}</div>
                        <form method="POST" action="{{ route('logout') }}" class="px-4 pb-2">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Logout
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Login</a>
                    <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Register</a>
                @endauth

            </div>
        </div>
    </div>

    {{-- ========== SIDEBAR KIRI (desktop) ========== --}}
    <aside class="hidden md:fixed md:inset-y-0 md:left-0 md:z-40 md:flex md:w-64 md:flex-col bg-gray-100 border-r border-gray-200">

        {{-- Header user --}}
        <div class="h-16 flex items-center px-6 bg-gray-700 text-white">
            @auth
                <span class="font-semibold tracking-wide">{{ $u->name }}</span>
                <span class="ml-3 text-sm font-medium text-gray-300">{{ ucfirst($u->role) }}</span>
            @else
                <span class="font-semibold">{{ config('app.name', 'Laravel') }}</span>
            @endauth
        </div>

        {{-- Menu --}}
        <nav class="flex-1 px-3 py-4 space-y-1">
            @auth
                @if ($isAdmin)
                    <a href="{{ route('admin.dashboard') }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Dashboard</a>
                    <a href="{{ route('admin.verifikasi-pembayaran') }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.verifikasi-pembayaran') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Verifikasi Pembayaran</a>
                    <a href="{{ route('admin.jadwal-seleksi') }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.jadwal-seleksi') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Jadwal Seleksi</a>
                    <a href="{{ route('admin.data-pendaftar') }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.data-pendaftar') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Data Pendaftar</a>
                    <a href="{{ route('admin.soal-seleksi') }}"
                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.soal-seleksi') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Soal Seleksi</a>
                @else
                    @if (! $hasFilled)
                        <a href="{{ route('pendaftar.daftar') }}"
                           class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('pendaftar.daftar*') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Daftar Pesantren</a>
                    @else
                        <a href="{{ route('pendaftar.jadwal') }}"
                           class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('pendaftar.jadwal') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Jadwal Seleksi</a>
                        <a href="{{ route('pendaftar.data-pendaftar') }}"
                           class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('pendaftar.data-pendaftar*') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Data Pendaftar</a>
                        <a href="{{ route('pendaftar.status') }}"
                           class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('pendaftar.status') ? 'bg-gray-300 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-200' }}">Status</a>
                    @endif
                @endif
            @endauth
        </nav>

        {{-- Footer (login/logout) --}}
        <div class="border-t border-gray-200 p-4">
            @auth
                <div class="text-xs text-gray-500 truncate mb-2">{{ auth()->user()->email }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md text-sm bg-gray-200 hover:bg-gray-300 text-gray-800">
                        Logout
                    </button>
                </form>
            @else
                <div class="flex gap-2">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md text-sm bg-gray-200 hover:bg-gray-300 text-gray-800">Login</a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center px-3 py-2 rounded-md text-sm bg-indigo-600 text-white hover:bg-indigo-700">Register</a>
                </div>
            @endauth
        </div>
    </aside>
</nav>
