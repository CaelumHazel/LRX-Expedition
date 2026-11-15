
<aside
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-mocha-overlay bg-mocha-mantle transition-all duration-300 lg:translate-x-0"
    :class="[
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
>

    {{-- Brand --}}
    <div class="flex h-[72px] items-center border-b border-mocha-overlay px-5">

        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-lrx-purple/10 text-lrx-purple">
            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                <path d="M4 7.5L12 3L20 7.5V16.5L12 21L4 16.5V7.5Z"/>
                <path d="M4.5 7.8L12 12L19.5 7.8M12 12V20.5"/>
            </svg>
        </div>

        <div
            x-show="!sidebarCollapsed"
            x-transition.opacity
            class="ml-3 min-w-0"
        >
            <h1 class="text-sm font-bold tracking-[0.18em] text-mocha-text">
                LRX TRACKING
            </h1>
            <p class="mt-0.5 text-[10px] tracking-wider text-mocha-muted">
                LOGISTICS MANAGEMENT
            </p>
        </div>

        <button
            @click="mobileSidebarOpen = false"
            class="ml-auto rounded-lg p-2 text-mocha-muted hover:bg-mocha-overlay hover:text-mocha-text lg:hidden"
            aria-label="Close sidebar"
        >
            ✕
        </button>

    </div>


    {{-- Navigation --}}
    <nav class="flex-1 space-y-7 overflow-y-auto px-3 py-6">

        {{-- Overview --}}
        <div>
            <p
                x-show="!sidebarCollapsed"
                class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-mocha-muted"
            >
                Overview
            </p>

            <a
                href="{{ route('dashboard') }}"
                @click="mobileSidebarOpen = false"
                title="Dashboard"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('dashboard') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <span class="shrink-0">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="3" width="8" height="8" rx="2"/>
                        <rect x="13" y="3" width="8" height="5" rx="2"/>
                        <rect x="13" y="10" width="8" height="11" rx="2"/>
                        <rect x="3" y="13" width="8" height="8" rx="2"/>
                    </svg>
                </span>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Dashboard
                </span>
            </a>
        </div>


        {{-- Shipment --}}
        <div>
            <p
                x-show="!sidebarCollapsed"
                class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-mocha-muted"
            >
                Shipment
            </p>

            <a
                href="{{ route('shipments.index') }}"
                @click="mobileSidebarOpen = false"
                title="Tracking & Shipments"
                class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('shipments.*') || request()->routeIs('tracking.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <path d="M3 7L12 3L21 7V17L12 21L3 17V7Z"/>
                    <path d="M3.5 7.5L12 12L20.5 7.5M12 12V21"/>
                </svg>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Shipments
                </span>
            </a>

            <a
                href="{{ route('stock.index') }}"
                @click="mobileSidebarOpen = false"
                title="Shipment Stock"
                class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('stock.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 5H20V19H4V5ZM8 9H16M8 13H16M8 17H13"/>
                </svg>
                <span x-show="!sidebarCollapsed" x-transition.opacity>Shipment Stock</span>
            </a>

        </div>


        {{-- Operations --}}
        <div>
            <p
                x-show="!sidebarCollapsed"
                class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-mocha-muted"
            >
                Operations
            </p>

            <a
                href="{{ route('warehouses.index') }}"
                @click="mobileSidebarOpen = false"
                title="Warehouse"
                class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('warehouses.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <path d="M3 10L12 3L21 10V21H3V10Z"/>
                    <path d="M8 21V13H16V21M8 9H8.01M12 9H12.01M16 9H16.01"/>
                </svg>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Warehouse
                </span>
            </a>

            <a
                href="{{ route('items.index') }}"
                @click="mobileSidebarOpen = false"
                title="Inventory"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('items.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 7L12 3L20 7L12 11L4 7Z"/>
                    <path d="M4 7V17L12 21L20 17V7M12 11V21"/>
                </svg>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Inventory
                </span>
            </a>
        </div>


        {{-- Management --}}
        <div>
            <p
                x-show="!sidebarCollapsed"
                class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-mocha-muted"
            >
                Management
            </p>

            <a
                href="{{ route('customers.index') }}"
                @click="mobileSidebarOpen = false"
                title="Customers"
                class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('customers.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <circle cx="9" cy="8" r="4"/>
                    <path d="M2 21V19C2 15.7 4.7 13 8 13H10C13.3 13 16 15.7 16 19V21M17 4.5C19 5.3 20 7 20 9S19 12.7 17 13.5M19 15C21 16 22 17.5 22 20V21"/>
                </svg>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Customers
                </span>
            </a>

            <a
                href="{{ route('couriers.index') }}"
                @click="mobileSidebarOpen = false"
                title="Couriers"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('couriers.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                    <path d="M3 7H15V17H3V7ZM15 11H19L22 14V17H15V11Z"/>
                    <circle cx="7" cy="18" r="2"/>
                    <circle cx="18" cy="18" r="2"/>
                </svg>

                <span x-show="!sidebarCollapsed" x-transition.opacity>
                    Couriers
                </span>
            </a>

            @if (auth()->user()?->role === 'admin')
                <a
                    href="{{ route('shipping-rates.index') }}"
                    @click="mobileSidebarOpen = false"
                    title="Shipping Rates"
                    class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                        {{ request()->routeIs('shipping-rates.*') ? 'lrx-nav-active' : 'text-mocha-subtext hover:bg-mocha-overlay hover:text-mocha-text' }}"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 3V21M17 7H9.5A3.5 3.5 0 009.5 14H14A3.5 3.5 0 0114 21H6"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity>Shipping Rates</span>
                </a>
            @endif
        </div>

    </nav>


    {{-- Sidebar bottom --}}
    <div class="border-t border-mocha-overlay p-3">

        <button
            @click="toggleSidebar()"
            class="hidden w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-mocha-subtext transition hover:bg-mocha-overlay hover:text-mocha-text lg:flex"
            :class="sidebarCollapsed ? 'justify-center' : ''"
            title="Toggle sidebar"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-5 w-5 shrink-0 transition-transform"
                :class="sidebarCollapsed ? 'rotate-180' : ''"
                stroke="currentColor"
                stroke-width="1.7"
            >
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <path d="M9 4V20M14 9L17 12L14 15"/>
            </svg>

            <span x-show="!sidebarCollapsed" x-transition.opacity>
                Collapse Sidebar
            </span>
        </button>

        <div
            x-show="!sidebarCollapsed"
            class="mt-3 rounded-xl border border-mocha-overlay bg-mocha-surface p-3"
        >
            <p class="text-xs font-medium text-mocha-text">
                LRX System
            </p>
            <p class="mt-1 text-[10px] text-mocha-muted">
                Logistics Management v1.0
            </p>
        </div>

    </div>

</aside>
