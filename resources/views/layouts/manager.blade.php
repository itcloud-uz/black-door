@extends('layouts.app')

@php
    $user = auth()->user();
    $object = null;
    if ($user) {
        if ($user->role->value === 'manager') {
            $mgr = \App\Models\ObjectManager::where('user_id', $user->id)->first();
            $object = $mgr ? $mgr->object : null;
        } elseif ($user->role->value === 'employee') {
            $emp = \App\Models\ObjectEmployee::where('user_id', $user->id)->first();
            $object = $emp ? $emp->object : null;
        }
    }
    $objectType = $object?->type->value ?? 'factory';
@endphp

@section('content')
<div class="@if($objectType === 'warehouse') wood-panel @else metal-panel @endif">
    @if(!isset($objectType) || $objectType !== 'warehouse')
        {{-- Industrial rivets --}}
        <div class="rivets-top">
            <span class="rivet"></span>
            <span class="rivet"></span>
            <span class="rivet"></span>
            <span class="rivet"></span>
        </div>
    @endif

    @yield('manager-content')

    @if(!isset($objectType) || $objectType !== 'warehouse')
        <div class="warning-stripe mt-lg"></div>
    @endif
</div>
@endsection
