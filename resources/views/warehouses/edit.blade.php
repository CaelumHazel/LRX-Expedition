@extends('layouts.app')

@section('title', 'Edit Warehouse')
@section('breadcrumb', 'Operations / Warehouses / Edit')
@section('page-title', 'Edit Warehouse')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <h1 class="text-2xl font-semibold text-mocha-text">Edit {{ $warehouse->name }}</h1>
    @if ($errors->any())
        <div class="rounded-xl border border-lrx-red/30 bg-lrx-red/10 p-4 text-xs text-lrx-red">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ route('warehouses.update', $warehouse) }}" class="space-y-5">
        @method('PUT')
        @include('warehouses.form', ['submitLabel' => 'Save warehouse'])
    </form>
</div>
@endsection
