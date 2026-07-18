@extends('layouts.app')

@section('content')
<div class="@if(isset($objectType) && $objectType === 'warehouse') wood-panel @else metal-panel @endif">
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
