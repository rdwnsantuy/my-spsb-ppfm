{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }">
    {{-- TOPBAR: hanya mobile (md:hidden) --}}
    <div class="md:hidden bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">
                {{ config('app.name', 'Laravel') }}
            </a>
            <button @click="open = !open" type="button"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none">
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div x-show="open" x-transition class="border-t border-gray-200">
            <div class="py-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('dashboard') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Dashboard</a>
                @auth
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Admin</a>
                    @else
                        <a href="{{ route('pendaftar.dashboard') }}" class="block px-4 py-2 text-sm {{ request()->routeIs('pendaftar.dashboard') ? 'text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">Pendaftar</a>
                    @endif
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Login</a>
                    <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Register</a>
                @else
                    <div class="px-4 pt-2 text-xs text-gray-500">{{ auth()->user()->email }}</div>
                    <form method="POST" action="{{ route('logout') }}" class="px-4 pb-2">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Logout</button>
                    </form>
                @endguest
            </div>
        </div>
    </div>

    {{-- SIDEBAR: kiri, hanya desktop (md+) --}}
    <aside class="hidden md:fixed md:inset-y-0 md:left-0 md:z-40 md:flex md:w-64 md:flex-col bg-white border-r border-gray-200">
        {{-- Brand --}}
        <div class="h-16 flex items-center px-6 font-semibold text-gray-800">
            <a href="{{ route('dashboard') }}">{{ config('app.name', 'Laravel') }}</a>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 px-3 py-4 space-y-1">
            <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">Dashboard</a>
            @auth
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">Admin</a>
                @else
                    <a href="{{ route('pendaftar.dashboard') }}" class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('pendaftar.dashboard') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">Pendaftar</a>
                @endif
            @endauth
        </nav>

        {{-- Action user --}}
        <div class="border-t border-gray-200 p-4">
            @auth
                <div class="text-xs text-gray-500 truncate mb-2">{{ auth()->user()->name }} — {{ auth()->user()->email }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">Logout</button>
                </form>
            @endauth
            @guest
                <div class="flex gap-2">
                    <a href="{{ route('login') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">Login</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm bg-indigo-600 text-white hover:bg-indigo-700">Register</a>
                </div>
            @endguest
        </div>
    </aside>
</nav>
