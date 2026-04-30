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
        .sidebar-link.active { background-color: rgba(62, 39, 35, 0.1); border-right: 3px solid #3E2723; }
        /* Sidebar collapse */
        #sidebar { transition: transform 0.35s cubic-bezier(.4,0,.2,1), opacity 0.3s ease; }
        #sidebar.collapsed { transform: translateX(-100%); }
        #main-content { transition: margin-left 0.35s cubic-bezier(.4,0,.2,1); }
        /* Dropdown */
        .dropdown-toggle { cursor: pointer; transition: all 0.2s ease; }
        .dropdown-toggle:hover { background-color: rgba(161, 136, 127, 0.1); }
        .dropdown-icon { transition: transform 0.3s ease; }
        .dropdown-toggle.open .dropdown-icon { transform: rotate(180deg); }
        .dropdown-menu { max-height: 0; overflow: hidden; transition: max-height 0.35s cubic-bezier(.4,0,.2,1); }
        .dropdown-menu.open { max-height: 500px; }
        .sidebar-section-title { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: #A1887F; padding: 0.75rem 1.5rem 0.4rem; }
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
                    <p class="text-[0.65rem] text-caramel font-medium">Management System</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4">
                {{-- Main --}}
                <div class="sidebar-section-title">Menu Utama</div>
                <a href="{{ route('pemilik.dashboard') }}" class="sidebar-link flex items-center gap-3 px-6 py-2.5 text-sm {{ request()->routeIs('pemilik.dashboard') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                    Dashboard
                </a>

                {{-- Kelola Data Dropdown --}}
                @php $kelolaOpen = request()->routeIs('pemilik.users.*') || request()->routeIs('pemilik.products.*') || request()->routeIs('pemilik.categories.*') || request()->routeIs('pemilik.materials.*') || request()->routeIs('pemilik.tables.*'); @endphp
                <div class="mt-4">
                    <button onclick="toggleDropdown('kelola')" class="dropdown-toggle {{ $kelolaOpen ? 'open' : '' }} w-full flex items-center justify-between px-6 py-2 sidebar-section-title !py-2.5 !px-5 rounded-lg mx-1" style="width:calc(100% - 0.5rem)">
                        <span>Kelola Data</span>
                        <svg class="dropdown-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="dropdown-kelola" class="dropdown-menu {{ $kelolaOpen ? 'open' : '' }}">
                        <a href="{{ route('pemilik.users.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.users.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            Data Pengguna
                        </a>
                        <a href="{{ route('pemilik.products.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.products.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                            Produk &amp; Harga
                        </a>
                        <a href="{{ route('pemilik.materials.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.materials.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                            Bahan Baku
                        </a>
                        <a href="{{ route('pemilik.tables.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.tables.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 10.5h16.5M10.5 3.75v16.5"/></svg>
                            Data Meja
                        </a>
                    </div>
                </div>

                {{-- Laporan Dropdown --}}
                @php $laporanOpen = request()->routeIs('pemilik.reports.*'); @endphp
                <div class="mt-1">
                    <button onclick="toggleDropdown('laporan')" class="dropdown-toggle {{ $laporanOpen ? 'open' : '' }} w-full flex items-center justify-between px-6 py-2 sidebar-section-title !py-2.5 !px-5 rounded-lg mx-1" style="width:calc(100% - 0.5rem)">
                        <span>Laporan</span>
                        <svg class="dropdown-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="dropdown-laporan" class="dropdown-menu {{ $laporanOpen ? 'open' : '' }}">
                        <a href="{{ route('pemilik.reports.sales') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.reports.sales') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                            Laporan Penjualan
                        </a>
                        <a href="{{ route('pemilik.reports.inventory') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.reports.inventory') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                            Inventori &amp; Stok
                        </a>
                    </div>
                </div>

                {{-- Analisis Dropdown --}}
                @php $analisisOpen = request()->routeIs('pemilik.analytics.*') || request()->routeIs('pemilik.exports.*'); @endphp
                <div class="mt-1">
                    <button onclick="toggleDropdown('analisis')" class="dropdown-toggle {{ $analisisOpen ? 'open' : '' }} w-full flex items-center justify-between px-6 py-2 sidebar-section-title !py-2.5 !px-5 rounded-lg mx-1" style="width:calc(100% - 0.5rem)">
                        <span>Analisis</span>
                        <svg class="dropdown-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="dropdown-analisis" class="dropdown-menu {{ $analisisOpen ? 'open' : '' }}">
                        <a href="{{ route('pemilik.analytics.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.analytics.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                            Analisis Bisnis
                        </a>
                        <a href="{{ route('pemilik.exports.index') }}" class="sidebar-link flex items-center gap-3 pl-8 pr-6 py-2 text-sm {{ request()->routeIs('pemilik.exports.*') ? 'active text-espresso font-semibold' : 'text-espresso/70 hover:text-espresso' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Ekspor Data
                        </a>
                    </div>
                </div>
            </nav>

            {{-- User Info at Bottom --}}
            <div class="border-t border-latte/40 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-espresso/10 rounded-full flex items-center justify-center">
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
                <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-cream p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
            {{-- Mobile Menu --}}
            <div id="mobile-menu" class="hidden bg-espresso-light border-t border-espresso/30 pb-3">
                <a href="{{ route('pemilik.dashboard') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.dashboard') ? 'text-cream font-semibold' : 'text-cream/70' }}">Dashboard</a>
                <a href="{{ route('pemilik.users.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.users.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Data Pengguna</a>
                <a href="{{ route('pemilik.products.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.products.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Produk & Harga</a>
                <a href="{{ route('pemilik.materials.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.materials.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Bahan Baku</a>
                <a href="{{ route('pemilik.tables.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.tables.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Data Meja</a>
                <a href="{{ route('pemilik.reports.sales') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.reports.sales') ? 'text-cream font-semibold' : 'text-cream/70' }}">Laporan Penjualan</a>
                <a href="{{ route('pemilik.reports.inventory') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.reports.inventory') ? 'text-cream font-semibold' : 'text-cream/70' }}">Inventori & Stok</a>
                <a href="{{ route('pemilik.analytics.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.analytics.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Analisis Bisnis</a>
                <a href="{{ route('pemilik.exports.index') }}" class="block px-4 py-2.5 text-sm {{ request()->routeIs('pemilik.exports.*') ? 'text-cream font-semibold' : 'text-cream/70' }}">Ekspor Data</a>
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
            {{-- Top Bar --}}
            <header class="bg-white/80 backdrop-blur-md border-b border-latte/40 sticky top-0 z-20 mt-[52px] lg:mt-0">
                <div class="px-6 lg:px-8 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSidebar()" id="sidebar-toggle" class="hidden lg:flex w-9 h-9 items-center justify-center rounded-xl border border-latte/60 hover:bg-latte/20 transition-all duration-200 text-espresso" title="Toggle Sidebar">
                            <svg class="w-4 h-4 transition-transform duration-300" id="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        <div>
                            <h2 class="text-lg font-bold text-espresso">
                                @yield('page-title', 'Dashboard')
                            </h2>
                            <p class="text-xs text-caramel mt-0.5">@yield('page-description', '')</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @yield('page-actions')
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <div class="px-6 lg:px-8 py-6 animate-fade-in">
                {{-- Flash Messages --}}
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

    <script>
        // Auto-dismiss flash messages
        ['flash-success', 'flash-error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                setTimeout(() => {
                    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-8px)';
                    setTimeout(() => el.remove(), 400);
                }, 4000);
            }
        });

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main-content');
            const collapsed = sidebar.classList.toggle('collapsed');
            main.style.marginLeft = collapsed ? '0' : '';
            localStorage.setItem('sidebarCollapsed', collapsed);
        }
        // Restore sidebar state
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                const sidebar = document.getElementById('sidebar');
                const main = document.getElementById('main-content');
                sidebar.classList.add('collapsed');
                main.style.marginLeft = '0';
            }
        });

        // Dropdown toggle — each dropdown is independent, only closes when clicked
        function toggleDropdown(name) {
            const menu = document.getElementById('dropdown-' + name);
            const btn = menu.previousElementSibling;
            menu.classList.toggle('open');
            btn.classList.toggle('open');
        }
    </script>
    @stack('scripts')
</body>
</html>
