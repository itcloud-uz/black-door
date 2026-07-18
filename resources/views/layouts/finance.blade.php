@extends('layouts.app')

@section('content')
<div class="leather-cover leather-spine">
    {{-- Metal Clasp --}}
    <div class="metal-clasp">
        <span class="clasp-icon"><i class="bi bi-lock"></i></span>
        <span class="clasp-text">Moliya Bo'limi — Qora Daftar</span>
    </div>

    {{-- Notebook Rings --}}
    <div class="notebook-rings">
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="ring"></div>
        <div class="ring"></div>
    </div>

    {{-- Paper Page --}}
    <div class="paper-page">
        <div class="page-fold"></div>

        @yield('finance-content')
    </div>
</div>
@endsection
