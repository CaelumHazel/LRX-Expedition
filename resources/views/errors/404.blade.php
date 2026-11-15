@extends('layouts.app')

@section('title', ($feature ?? null) ? $feature.' | Not found' : 'Page not found')
@section('breadcrumb', 'System / Not Found')
@section('page-title', $feature ?? 'Page not found')

@section('content')
<div class="mx-auto max-w-3xl py-8">
    <section class="lrx-surface relative overflow-hidden rounded-3xl border border-mocha-overlay px-6 py-12 text-center shadow-2xl shadow-black/20 sm:px-12 sm:py-16">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-lrx-purple/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-56 w-56 rounded-full bg-lrx-cyan/10 blur-3xl"></div>

        <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-lrx-cyan/20 bg-lrx-cyan/10 text-lrx-cyan shadow-[0_0_35px_var(--color-lrx-cyan)]">
            <svg viewBox="0 0 24 24" fill="none" class="h-8 w-8" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z"/>
                <path d="M3.5 7.8L12 12L20.5 7.8M12 12V21"/>
            </svg>
        </div>

        @if ($feature ?? null)
            <p class="relative mt-6 text-[10px] font-semibold uppercase tracking-[0.22em] text-lrx-cyan">Feature under development</p>
            <h1 class="relative mt-3 text-3xl font-semibold tracking-tight text-mocha-text">{{ $feature }}</h1>
            <p class="relative mx-auto mt-3 max-w-lg text-sm leading-6 text-mocha-subtext">{{ $description }}</p>
            <p class="relative mt-5 text-xs text-mocha-muted">This section is not available yet. Use the sidebar to continue working in another area.</p>
        @else
            <p class="relative mt-6 text-[10px] font-semibold uppercase tracking-[0.22em] text-lrx-purple">Route not found</p>
            <h1 class="relative mt-3 text-3xl font-semibold tracking-tight text-mocha-text">404</h1>
            <p class="relative mx-auto mt-3 max-w-lg text-sm leading-6 text-mocha-subtext">The page you requested could not be found.</p>
        @endif

        <a href="{{ route('dashboard') }}" class="lrx-button-primary relative mt-8 inline-flex items-center gap-2 rounded-xl px-5 py-3 text-xs font-semibold transition">
            <span aria-hidden="true">←</span>
            Back to Dashboard
        </a>
    </section>
</div>
@endsection
