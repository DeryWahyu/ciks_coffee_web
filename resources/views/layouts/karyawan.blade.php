<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Ciks Coffee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { background-color: rgba(161, 136, 127, 0.15); }
        .sidebar-link.active {
            background-color: rgba(62, 39, 35, 0.1);
            border-right: 3px solid #3E2723;
        }
        .sidebar-section-title {
            font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em;
            text-transform: uppercase; color: #A1887F; padding: 0.75rem 1.5rem 0.4rem;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-cream-light font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="hidden lg:flex lg:flex-col w-64 bg-white border-r border-latte/60 fixed inset-y-0 left-0 z-30" id="sidebar">
            {{-- Brand --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-latte/40">
                <svg class="w-8 h-8 text-espresso" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                    <path d="M11.5 2c-.3 1 .3 2 0 3" opacity="0.55"/>
                    <path d="M14.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                    <path d="M4.5 7h13v5c0 3.3-2.7 6-6 6h-1c-3.3 0-6-2.7-6-6V7z"/>
                    <path d="M17.5 9.5h1a3 3 0 010 6h-1"/>
                    <path d="M3 21h16"/>
                </svg>
                <div>
                    <h1 class="text-sm font-extrabold text-espresso tracking-[0.08em] uppercase">Ciks Coffee</h1>
                    <p class="text-[0.65rem] text-caramel font-medium">Karyawan Panel</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4">
                <div class="sidebar-section-title">Menu Utama</div>
                <a href="{{ route('karyawan.dashboard') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('karyawan.dashboard') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                    Dashboard
                </a>

                {{-- Operasional --}}
                <div class="sidebar-section-title mt-4">Operasional</div>
                <a href="{{ route('karyawan.pos.index') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('karyawan.pos.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228M16.5 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM8.25 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                    </svg>
                    Point of Sales
                </a>
                <a href="{{ route('karyawan.orders.index') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('karyawan.orders.index') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    Antrean Pesanan
                </a>

                <div class="sidebar-section-title mt-4">Riwayat & Data</div>
                <a href="{{ route('karyawan.orders.history') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('karyawan.orders.history') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Riwayat Transaksi
                </a>
                <a href="{{ route('karyawan.income.index') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('karyawan.income.index') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pendapatan Karyawan
                </a>


            </nav>

            {{-- User Info at Bottom --}}
            <div class="border-t border-latte/40 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-caramel/15 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-espresso">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-espresso truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-caramel capitalize">{{ Auth::user()->role }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-caramel hover:text-red-500 transition-colors" title="Logout">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Mobile Header --}}
        <div class="lg:hidden fixed top-0 inset-x-0 z-30 bg-espresso shadow-lg">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <svg class="w-7 h-7 text-cream" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                        <path d="M11.5 2c-.3 1 .3 2 0 3" opacity="0.55"/>
                        <path d="M14.5 2c-.3 1 .3 2 0 3" opacity="0.4"/>
                        <path d="M4.5 7h13v5c0 3.3-2.7 6-6 6h-1c-3.3 0-6-2.7-6-6V7z"/>
                        <path d="M17.5 9.5h1a3 3 0 010 6h-1"/>
                        <path d="M3 21h16"/>
                    </svg>
                    <span class="text-cream font-extrabold tracking-wider text-sm uppercase">Ciks Coffee</span>
                </div>
                <button onclick="document.getElementById('mobile-menu-k').classList.toggle('hidden')" class="text-cream p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
            <div id="mobile-menu-k" class="hidden bg-espresso-light border-t border-espresso/30 pb-3">
                <a href="{{ route('karyawan.dashboard') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.dashboard') ? 'text-cream font-semibold' : 'text-cream/70' }}">Dashboard</a>
                <a href="{{ route('karyawan.pos.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.pos.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Point of Sales</a>
                <a href="{{ route('karyawan.orders.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.orders.index') ? 'text-cream font-semibold' : 'text-cream/70' }}">Antrean Pesanan</a>
                <a href="{{ route('karyawan.orders.history') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.orders.history') ? 'text-cream font-semibold' : 'text-cream/70' }}">Riwayat Transaksi</a>
                <a href="{{ route('karyawan.income.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.income.index') ? 'text-cream font-semibold' : 'text-cream/70' }}">Pendapatan Karyawan</a>


                <div class="border-t border-espresso/30 mt-2 pt-2 px-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-300 text-sm py-2">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <main class="flex-1 lg:ml-64 min-h-screen">
            <header class="bg-white/80 backdrop-blur-md border-b border-latte/40 sticky top-0 z-20 mt-[52px] lg:mt-0">
                <div class="px-6 lg:px-8 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-espresso">
                            @yield('page-title', 'Dashboard')
                        </h2>
                        <p class="text-xs text-caramel mt-0.5">@yield('page-description', '')</p>
                    </div>
                    <div class="flex items-center gap-3">@yield('page-actions')</div>
                </div>
            </header>

            <div class="px-6 lg:px-8 py-6 animate-fade-in">
                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3" id="flash-success">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-green-700 text-sm">{{ session('success') }}</p>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')

    <script>
        const flashEl = document.getElementById('flash-success');
        if (flashEl) {
            setTimeout(() => {
                flashEl.style.transition = 'opacity 0.4s ease';
                flashEl.style.opacity = '0';
                setTimeout(() => flashEl.remove(), 400);
            }, 4000);
        }
    </script>
    @stack('scripts')
</body>
</html>
