
<header class="sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-mocha-overlay bg-mocha-base/90 px-5 backdrop-blur-xl sm:px-7 lg:px-8">

    {{-- Left section --}}
    <div class="flex min-w-0 items-center gap-4">

        {{-- Mobile menu --}}
        <button
            @click="mobileSidebarOpen = true"
            class="rounded-xl border border-mocha-overlay p-2 text-mocha-subtext transition hover:bg-mocha-surface hover:text-mocha-text lg:hidden"
            aria-label="Open menu"
        >
            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                <path d="M4 6H20M4 12H20M4 18H20"/>
            </svg>
        </button>

        {{-- Breadcrumb --}}
        <div class="min-w-0">
            <div class="hidden items-center gap-2 text-xs text-mocha-muted sm:flex">
                <span>LRX</span>
                <span>/</span>
                <span class="text-mocha-subtext">
                    @yield('breadcrumb', 'Overview')
                </span>
            </div>

            <h2 class="truncate text-base font-semibold text-mocha-text">
                @yield('page-title', 'Dashboard')
            </h2>
        </div>

    </div>


    {{-- Right section --}}
    <div class="flex items-center gap-2 sm:gap-4">

        {{-- Search --}}
        <form method="GET" action="{{ route('shipments.index') }}" class="relative hidden md:block">
            <svg
                viewBox="0 0 24 24"
                fill="none"
                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-mocha-muted"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <circle cx="11" cy="11" r="7"/>
                <path d="M16 16L21 21"/>
            </svg>

            <input
                type="search"
                name="search"
                aria-label="Search shipments"
                placeholder="Search shipments..."
                class="h-10 w-64 rounded-xl border border-mocha-overlay bg-mocha-mantle pl-10 pr-4 text-xs text-mocha-text outline-none transition placeholder:text-mocha-muted focus:border-lrx-purple/60 focus:ring-2 focus:ring-lrx-purple/10"
            >
        </form>


        {{-- Notification --}}
        <button
            type="button"
            class="relative rounded-xl border border-mocha-overlay p-2.5 text-mocha-subtext transition hover:bg-mocha-surface hover:text-mocha-text"
            aria-label="Notifications"
        >
            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.7">
                <path d="M18 8A6 6 0 006 8C6 15 3 15 3 17H21C21 15 18 15 18 8Z"/>
                <path d="M10 21H14"/>
            </svg>

            <span class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-lrx-red"></span>
        </button>


        {{-- Divider --}}
        <div class="hidden h-8 w-px bg-mocha-overlay sm:block"></div>


        {{-- Profile --}}
        <div
            x-data="{ profileOpen: false }"
            class="relative"
        >

            <button
                @click="profileOpen = !profileOpen"
                @click.outside="profileOpen = false"
                class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-mocha-surface"
            >

                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-lrx-purple/15 text-xs font-semibold text-lrx-purple">
                    {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                </div>

                <div class="hidden text-left sm:block">
                    <p class="text-xs font-medium text-mocha-text">
                        {{ auth()->user()->name ?? 'Administrator' }}
                    </p>

                    <p class="mt-0.5 text-[10px] text-mocha-muted">
                        {{ ucfirst(auth()->user()->role ?? 'staff') }}
                    </p>
                </div>

                <svg viewBox="0 0 24 24" fill="none" class="hidden h-4 w-4 text-mocha-muted sm:block" stroke="currentColor" stroke-width="1.8">
                    <path d="M7 10L12 15L17 10"/>
                </svg>

            </button>


            {{-- Profile dropdown --}}
            <div
                x-cloak
                x-show="profileOpen"
                x-transition.origin.top.right
                class="absolute right-0 top-full mt-3 w-52 rounded-xl border border-mocha-border bg-mocha-surface p-2 shadow-2xl shadow-black/30"
            >

                <div class="border-b border-mocha-overlay px-3 py-2">
                    <p class="text-xs font-medium text-mocha-text">
                        {{ auth()->user()->name ?? 'Administrator' }}
                    </p>
                    <p class="mt-1 truncate text-[10px] text-mocha-muted">
                        {{ auth()->user()->email ?? 'admin@lrx.local' }}
                    </p>
                </div>

                <a
                    href="#"
                    class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-mocha-subtext transition hover:bg-mocha-overlay hover:text-mocha-text"
                >
                    <span>⚙</span>
                    Account Settings
                </a>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs text-lrx-red transition hover:bg-lrx-red/10"
                        >
                            <span>↪</span>
                            Sign Out
                        </button>
                    </form>
                @endauth

            </div>

        </div>

    </div>

</header>
