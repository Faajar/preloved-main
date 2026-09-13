<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Preloved IN') — Barang Lama, Kesempatan Baru.</title>
    <meta name="description" content="@yield('meta_description', 'Preloved IN membantu kamu menemukan dan menitipkan barang preloved dengan mudah.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', 'Preloved IN')))" />
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Barang Lama, Kesempatan Baru.')))" />
    <meta property="og:url" content="@yield('canonical', url()->current())" />
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')" />
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-stone-200 bg-stone-50/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="shrink-0 text-xl font-semibold tracking-tight text-stone-950">
                Preloved <span class="text-emerald-700">IN</span>
            </a>

            <div class="hidden items-center gap-1 md:flex">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Home</a>
                <a href="{{ route('explore') }}" class="nav-link {{ request()->routeIs('explore') ? 'nav-link-active' : '' }}">Explore</a>
                <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}" @if (request()->routeIs('categories.*')) aria-current="page" @endif>Kategori</a>
            </div>

            <div class="flex items-center gap-2">
                @guest
                    <a href="{{ route('login') }}" class="button-secondary text-xs sm:text-sm">Masuk</a>
                    <a href="{{ route('register') }}" class="button-primary text-xs sm:text-sm">Daftar</a>
                @else
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="button-secondary hidden sm:inline-flex">Admin</a>
                    @elseif (auth()->user()->canSell())
                        <a href="{{ route('seller.products.create') }}" class="button-primary text-xs sm:text-sm">Titipkan Barang</a>
                        <a href="{{ route('seller.payments.index') }}" class="nav-link hidden lg:inline-flex">Pembayaran</a>
                        <a href="{{ route('seller.dashboard') }}" class="button-secondary hidden sm:inline-flex">Dashboard Seller</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="button-secondary hidden sm:inline-flex">Profil</a>
                        <form method="POST" action="{{ route('seller.activate') }}">
                            @csrf
                            <button type="submit" class="button-primary text-xs sm:text-sm">Titipkan Barang</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="nav-link text-xs sm:text-sm">Keluar</button>
                    </form>
                @endguest
            </div>
        </nav>

        <div class="border-t border-stone-200 px-4 py-2 md:hidden">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-1">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Home</a>
                <a href="{{ route('explore') }}" class="nav-link {{ request()->routeIs('explore') ? 'nav-link-active' : '' }}">Explore</a>
                <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}" @if (request()->routeIs('categories.*')) aria-current="page" @endif>Kategori</a>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'nav-link-active' : '' }}">Admin</a>
                    @elseif (auth()->user()->canSell())
                        <a href="{{ route('seller.dashboard') }}" class="nav-link {{ request()->routeIs('seller.dashboard') ? 'nav-link-active' : '' }}">Seller</a>
                        <a href="{{ route('seller.products.index') }}" class="nav-link {{ request()->routeIs('seller.products.*') ? 'nav-link-active' : '' }}">Barang</a>
                        <a href="{{ route('seller.payments.index') }}" class="nav-link {{ request()->routeIs('seller.payments.*') ? 'nav-link-active' : '' }}">Bayar</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') || request()->routeIs('profile.*') ? 'nav-link-active' : '' }}">Akun</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <x-flash-message />
        @yield('content')
    </main>

    <footer class="mt-20 border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-stone-600 sm:px-6 lg:px-8">
            <p class="font-medium text-stone-900">Preloved IN</p>
            <p>Barang Lama, Kesempatan Baru.</p>
            <p>Platform listing dan komunikasi langsung. Transaksi barang dilakukan di luar platform.</p>
        </div>
    </footer>
</body>
</html>
