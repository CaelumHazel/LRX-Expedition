<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in | LRX</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-mocha-base font-sans text-mocha-text antialiased">
    <div class="lrx-background"></div>
    <main class="flex min-h-screen items-center justify-center p-5">
        <section class="lrx-surface w-full max-w-md rounded-2xl border border-mocha-overlay p-7 shadow-2xl shadow-black/20 sm:p-9">
            <div class="mb-8 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-lrx-purple/10 text-lrx-purple">LRX</span>
                <div>
                    <p class="text-sm font-bold tracking-wider text-mocha-text">LRX TRACKING</p>
                    <p class="mt-1 text-[10px] uppercase tracking-[0.18em] text-mocha-muted">Logistics management</p>
                </div>
            </div>

            <h1 class="text-xl font-semibold text-mocha-text">Welcome back</h1>
            <p class="mt-2 text-xs text-mocha-subtext">Sign in with your username and password.</p>

            @if ($errors->any())
                <div role="alert" class="mt-5 rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-3 text-xs text-lrx-red">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="username" class="mb-2 block text-xs font-medium text-mocha-subtext">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none placeholder:text-mocha-muted focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-xs font-medium text-mocha-subtext">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-mocha-border bg-mocha-mantle px-3.5 py-3 text-sm text-mocha-text outline-none focus:border-lrx-cyan focus:ring-2 focus:ring-lrx-cyan/10">
                </div>
                <label class="flex items-center gap-2 text-xs text-mocha-subtext">
                    <input type="checkbox" name="remember" value="1" class="rounded border-mocha-border bg-mocha-mantle text-lrx-cyan focus:ring-lrx-cyan">
                    Remember me
                </label>
                <button type="submit" class="lrx-button-primary w-full rounded-xl px-4 py-3 text-sm font-semibold">Sign in</button>
            </form>
        </section>
    </main>
</body>
</html>
