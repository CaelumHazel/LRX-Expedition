@extends('layouts.app')

@section('title', 'UI Test')

@section('content')

<div
    x-data="{ open: true }"
    class="min-h-screen p-8"
>

    <div class="mx-auto max-w-5xl">

        <div class="mb-8">
            <p class="text-sm text-[#A6ADC8]">
                LRX TRACKING
            </p>

            <h1 class="mt-2 text-3xl font-semibold">
                UI Foundation
            </h1>

            <p class="mt-2 text-[#6C7086]">
                Tailwind CSS + Alpine.js + Mocha Dark
            </p>
        </div>


        <div class="grid gap-4 md:grid-cols-3">

            <div
                class="rounded-2xl border border-[#45475A]
                       bg-[#1E1E2E] p-5"
            >
                <p class="text-sm text-[#A6ADC8]">
                    Total Shipments
                </p>

                <p class="mt-3 text-3xl font-semibold">
                    1,248
                </p>

                <p class="mt-2 text-sm text-[#A6E3A1]">
                    +12.4% this month
                </p>
            </div>


            <div
                class="rounded-2xl border border-[#45475A]
                       bg-[#1E1E2E] p-5"
            >
                <p class="text-sm text-[#A6ADC8]">
                    In Transit
                </p>

                <p class="mt-3 text-3xl font-semibold">
                    382
                </p>

                <p class="mt-2 text-sm text-[#89DCEB]">
                    Currently moving
                </p>
            </div>


            <div
                class="rounded-2xl border border-[#45475A]
                       bg-[#1E1E2E] p-5"
            >
                <p class="text-sm text-[#A6ADC8]">
                    Delivered
                </p>

                <p class="mt-3 text-3xl font-semibold">
                    714
                </p>

                <p class="mt-2 text-sm text-[#A6E3A1]">
                    Successfully delivered
                </p>
            </div>

        </div>


        <div class="mt-6 rounded-2xl border border-[#45475A]
                    bg-[#1E1E2E] p-6">

            <div class="flex items-center justify-between">

                <div>
                    <h2 class="font-medium">
                        Alpine.js Test
                    </h2>

                    <p class="mt-1 text-sm text-[#6C7086]">
                        Klik button untuk test Alpine.
                    </p>
                </div>

                <button
                    @click="open = !open"
                    class="rounded-xl bg-[#CBA6F7]
                           px-4 py-2 text-sm font-medium
                           text-[#11111B]
                           transition hover:opacity-90"
                >
                    Toggle
                </button>

            </div>


            <div
                x-show="open"
                x-transition
                class="mt-5 rounded-xl border border-[#45475A]
                       bg-[#181825] p-4 text-sm text-[#A6ADC8]"
            >
                Alpine.js berhasil berjalan.
            </div>

        </div>

    </div>

</div>

@endsection