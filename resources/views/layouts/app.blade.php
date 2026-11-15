
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'LRX Tracking') | LRX</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <style>
        [x-cloak] {
            display: none !important;
        }

    </style>
</head>

<body class="min-h-screen bg-transparent font-sans text-mocha-text antialiased">
    <div class="lrx-background"></div>

    <div
        x-data="{
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            mobileSidebarOpen: false,
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
            }
        }"
        class="min-h-screen"
    >

        {{-- Mobile overlay --}}
        <div
            x-cloak
            x-show="mobileSidebarOpen"
            x-transition.opacity
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
        ></div>

        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Main area --}}
        <div
            class="min-h-screen transition-all duration-300"
            :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'"
        >

            {{-- Topbar --}}
            @include('layouts.partials.topbar')

            {{-- Page content --}}
            <main class="min-h-[calc(100vh-4.5rem)] p-5 sm:p-7 lg:p-8">

                <div class="mx-auto max-w-[1600px]">

                    @if (session('success'))
                        <div class="mb-5 rounded-xl border border-lrx-green/20 bg-lrx-green/10 px-4 py-3 text-sm text-lrx-green">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 rounded-xl border border-lrx-red/20 bg-lrx-red/10 px-4 py-3 text-sm text-lrx-red">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')

                </div>

            </main>

            {{-- Footer --}}
            <footer class="border-t border-mocha-overlay px-5 py-4 sm:px-8">
                <div class="mx-auto flex max-w-[1600px] items-center justify-between text-xs text-mocha-muted">
                    <span>LRX Tracking System</span>
                    <span>Operations Dashboard</span>
                </div>
            </footer>

        </div>

    </div>

</body>
</html>
