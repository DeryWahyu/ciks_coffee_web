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
        #sidebar { transition: transform 0.35s cubic-bezier(.4,0,.2,1), opacity 0.3s ease; }
        #sidebar.collapsed { transform: translateX(-100%); }
        #main-content { transition: margin-left 0.35s cubic-bezier(.4,0,.2,1); }
        .dropdown-toggle { cursor: pointer; transition: all 0.2s ease; }
        .dropdown-toggle:hover { background-color: rgba(161, 136, 127, 0.1); }
        .dropdown-icon { transition: transform 0.3s ease; }
        .dropdown-toggle.open .dropdown-icon { transform: rotate(180deg); }
        .dropdown-menu { max-height: 0; overflow: hidden; transition: max-height 0.35s cubic-bezier(.4,0,.2,1); }
        .dropdown-menu.open { max-height: 500px; }
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
                {{-- Operasional Dropdown --}}
                @php $operasionalOpen = request()->routeIs('karyawan.pos.*') || request()->routeIs('karyawan.orders.index') || request()->routeIs('karyawan.tables.*'); @endphp
                <div class="mt-4">
                    <button id="btn-operasional" onclick="toggleDropdown('operasional')" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 sidebar-section-title !py-2.5 !px-5 rounded-lg mx-1" style="width:calc(100% - 0.5rem)">
                        <span>Operasional</span>
                        <svg class="dropdown-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="dropdown-operasional" class="dropdown-menu">
                        <a href="{{ route('karyawan.pos.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('karyawan.pos.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.21-1.886L21 5.25H6.228M16.5 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM8.25 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            </svg>
                            Point of Sales
                        </a>
                        <a href="{{ route('karyawan.orders.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('karyawan.orders.index') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                            Antrean Pesanan
                        </a>
                        <a href="{{ route('karyawan.tables.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('karyawan.tables.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 21V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25V21M3 21h18M8.25 7.5h.008v.008H8.25V7.5zm3.75 0h.008v.008H12V7.5zm3.75 0h.008v.008H15.75V7.5zM8.25 11.25h.008v.008H8.25v-.008zm3.75 0h.008v.008H12v-.008zm3.75 0h.008v.008H15.75v-.008zM8.25 15h.008v.008H8.25V15zm3.75 0h.008v.008H12V15zm3.75 0h.008v.008H15.75V15z"/>
                            </svg>
                            Ketersediaan Meja
                        </a>
                    </div>
                </div>

                {{-- Riwayat & Data Dropdown --}}
                @php $riwayatOpen = request()->routeIs('karyawan.orders.history') || request()->routeIs('karyawan.income.*'); @endphp
                <div class="mt-1">
                    <button id="btn-riwayat" onclick="toggleDropdown('riwayat')" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 sidebar-section-title !py-2.5 !px-5 rounded-lg mx-1" style="width:calc(100% - 0.5rem)">
                        <span>Riwayat &amp; Data</span>
                        <svg class="dropdown-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="dropdown-riwayat" class="dropdown-menu">
                        <a href="{{ route('karyawan.orders.history') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('karyawan.orders.history') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Riwayat Transaksi
                        </a>
                        <a href="{{ route('karyawan.income.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('karyawan.income.index') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pendapatan Karyawan
                        </a>
                    </div>
                </div>

                <script>
                    (function() {
                        const menus = [
                            { id: 'operasional', defaultOpen: {{ $operasionalOpen ? 'true' : 'false' }} },
                            { id: 'riwayat', defaultOpen: {{ $riwayatOpen ? 'true' : 'false' }} }
                        ];

                        menus.forEach(menu => {
                            const state = localStorage.getItem('dropdown_' + menu.id);
                            const isOpen = state === 'open' || (state === null && menu.defaultOpen);

                            if (isOpen) {
                                const btn = document.getElementById('btn-' + menu.id);
                                const dropdown = document.getElementById('dropdown-' + menu.id);
                                if (btn) btn.classList.add('open');
                                if (dropdown) dropdown.classList.add('open');
                            }
                        });
                    })();
                </script>


            </nav>
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
                <a href='{{ route('karyawan.tables.index') }}' class='block px-4 py-2.5 text-sm {{ request()->routeIs('karyawan.tables.*') ? 'text-cream font-semibold' : 'text-cream/70' }}'>Ketersediaan Meja</a>
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
        <main class="flex-1 lg:ml-64 min-h-screen" id="main-content">
            <header class="bg-white/80 backdrop-blur-md border-b border-latte/40 sticky top-0 z-20 mt-[52px] lg:mt-0">
                <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-4 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSidebar()" id="sidebar-toggle" class="hidden lg:flex w-9 h-9 items-center justify-center rounded-xl border border-latte/60 hover:bg-latte/20 transition-all duration-200 text-espresso" title="Buka/tutup sidebar" aria-label="Buka/tutup sidebar">
                            <svg class="w-4 h-4 transition-transform duration-300" id="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <div>
                            <h2 class="text-lg font-bold text-espresso">
                                @yield('page-title', 'Dashboard')
                            </h2>
                            <p class="text-xs text-caramel mt-0.5">@yield('page-description', '')</p>
                        </div>
                    </div>
                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end sm:gap-3">
                        @yield('page-actions')
                        <div class="relative" id="order-notification-root">
                            <button type="button" id="order-notification-trigger" aria-expanded="false" aria-controls="order-notification-panel" aria-label="Notifikasi pesanan" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-latte/60 bg-white text-espresso transition hover:bg-latte/20 focus:outline-none focus:ring-2 focus:ring-caramel/40">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018.75 9.75V9A6.75 6.75 0 005.25 9v.75a8.967 8.967 0 01-1.56 5.022 23.848 23.848 0 005.454 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                <span id="order-notification-badge" class="absolute -right-1 -top-1 hidden min-w-5 rounded-full bg-red-500 px-1 text-center text-[0.58rem] font-bold leading-5 text-white shadow-sm"></span>
                            </button>
                            <div id="order-notification-panel" class="absolute right-0 z-[70] mt-2 hidden w-[min(21rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-latte/60 bg-cream-light shadow-2xl">
                                <div class="border-b border-latte/50 bg-white px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-espresso">Notifikasi pesanan</p>
                                            <p id="order-notification-status" class="text-[0.65rem] text-caramel">Menghubungkan realtime…</p>
                                        </div>
                                        <span class="rounded-full bg-caramel/10 px-2 py-1 text-[0.58rem] font-bold uppercase tracking-wider text-caramel">Mobile</span>
                                    </div>
                                </div>
                                <div class="space-y-3 p-4">
                                    <div class="rounded-xl border border-latte/50 bg-white/80 p-3">
                                        <p class="text-[0.62rem] font-bold uppercase tracking-[0.12em] text-caramel">Pesanan terakhir</p>
                                        <p id="order-notification-last-order" class="mt-1 text-xs leading-5 text-espresso">Belum ada pesanan mobile baru pada sesi ini.</p>
                                    </div>
                                    <button type="button" id="order-notification-enable-sound" class="flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-espresso px-3 py-2 text-xs font-bold text-cream transition hover:bg-espresso-light">
                                        Aktifkan suara
                                    </button>
                                    <label class="flex cursor-pointer items-center justify-between gap-3 text-xs text-espresso">
                                        <span>Bisukan notifikasi</span>
                                        <input id="order-notification-mute" type="checkbox" class="h-4 w-4 rounded border-latte text-espresso focus:ring-caramel">
                                    </label>
                                    <label class="block text-xs text-espresso">
                                        <span class="mb-2 block">Volume</span>
                                        <input id="order-notification-volume" type="range" min="0" max="100" step="5" class="h-1.5 w-full cursor-pointer accent-espresso">
                                    </label>
                                    <a href="{{ route('karyawan.orders.index') }}" class="flex min-h-10 items-center justify-center rounded-xl border border-latte/70 bg-white px-3 py-2 text-xs font-bold text-espresso transition hover:bg-latte/20">Buka antrean pesanan</a>
                                </div>
                            </div>
                        </div>
                        <div class="hidden h-8 w-px bg-latte/50 lg:block" aria-hidden="true"></div>
                        <div class="hidden min-w-0 items-center gap-3 lg:flex">
                            <div class="w-9 h-9 shrink-0 bg-caramel/15 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold text-espresso">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0 max-w-40">
                                <p class="text-sm font-semibold text-espresso truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-caramel capitalize">{{ Auth::user()->role }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-caramel hover:text-red-500 transition-colors" title="Keluar" aria-label="Keluar">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="animate-fade-in px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3" id="flash-success">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-green-700 text-sm">{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3" id="flash-error">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-red-700 text-sm">{{ session('error') }}</p>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
    @include('components.ciks-alert-system')

    <script>
        window.CiksEmployeeNotifications = {
            userId: @json((int) Auth::id()),
            queueUrl: @json(route('karyawan.orders.index')),
            reverb: {
                key: @json(config('broadcasting.connections.reverb.key')),
                host: @json(request()->getHost()),
                port: @json(request()->isSecure() ? 443 : request()->getPort()),
                scheme: @json(request()->getScheme()),
            },
        };
    </script>

    <script>
        const flashSuccess = document.getElementById('flash-success');
        if (flashSuccess) {
            setTimeout(() => {
                flashSuccess.style.transition = 'opacity 0.4s ease';
                flashSuccess.style.opacity = '0';
                setTimeout(() => flashSuccess.remove(), 400);
            }, 4000);
        }
        
        const flashError = document.getElementById('flash-error');
        if (flashError) {
            setTimeout(() => {
                flashError.style.transition = 'opacity 0.4s ease';
                flashError.style.opacity = '0';
                setTimeout(() => flashError.remove(), 400);
            }, 4000);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main-content');
            const collapsed = sidebar.classList.toggle('collapsed');
            main.style.marginLeft = collapsed ? '0' : '';
            localStorage.setItem('sidebarCollapsed', collapsed);
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                const sidebar = document.getElementById('sidebar');
                const main = document.getElementById('main-content');
                sidebar.classList.add('collapsed');
                main.style.marginLeft = '0';
            }
        });

        function toggleDropdown(name) {
            const menu = document.getElementById('dropdown-' + name);
            const button = document.getElementById('btn-' + name) || menu.previousElementSibling;
            const isOpen = menu.classList.toggle('open');
            if (button) button.classList.toggle('open');
            localStorage.setItem('dropdown_' + name, isOpen ? 'open' : 'closed');
        }
    </script>
    @stack('scripts')
</body>
</html>
