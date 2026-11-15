@extends('layouts.app')

@section('title', 'Add Warehouse')
@section('breadcrumb', 'Operations / Warehouses / Add')
@section('page-title', 'Add Warehouse')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <h1 class="text-2xl font-semibold text-mocha-text">Add warehouse</h1>
    @if ($errors->any())
        <div class="rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-4 text-xs text-lrx-red">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ route('warehouses.store') }}" class="space-y-5">
        @include('warehouses.form', ['warehouse' => new \App\Models\Warehouse, 'submitLabel' => 'Create warehouse'])
    </form>
</div>
@endsection
