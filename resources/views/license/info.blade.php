@extends('layouts.app')

@section('title', 'Litsenziya ma\'lumotlari')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Boshqaruv</a></li>
    <li class="breadcrumb-item active">Litsenziya</li>
@endsection

@section('content')
<div class="container-fluid py-lg">

    @if(session('success'))
        <div class="skeuo-alert skeuo-alert-success mb-md">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            <i class="bi bi-exclamation-triangle"></i>
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="grid-2 mb-xl">
        {{-- License Details Card --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-shield-lock text-primary"></i> Faol Litsenziya</h3>
                <span class="skeuo-badge skeuo-badge-green">Faol</span>
            </div>

            @if($license)
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 8px;">
                        <span class="text-muted">Mijoz (Kompaniya):</span>
                        <strong>{{ $license->client_name }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 8px;">
                        <span class="text-muted">Litsenziya kaliti:</span>
                        <strong style="font-family: monospace;">{{ $license->license_key }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 8px;">
                        <span class="text-muted">Tarif rejasi:</span>
                        <strong style="text-transform: uppercase;" class="text-primary">{{ $license->tariff_plan_code }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 8px;">
                        <span class="text-muted">Boshlanish sanasi:</span>
                        <strong>{{ $license->starts_at->format('d.m.Y') }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 8px;">
                        <span class="text-muted">Tugash sanasi:</span>
                        <strong>{{ $license->expires_at ? $license->expires_at->format('d.m.Y') : 'Muddatsiz (Umrbod)' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                        <span class="text-muted">Oxirgi sinxronizatsiya:</span>
                        <strong>{{ $license->last_successful_heartbeat_at->format('d.m.Y H:i') }}</strong>
                    </div>
                </div>
            @else
                <div class="text-center py-lg text-muted">Litsenziya ma'lumotlari topilmadi.</div>
            @endif
        </div>

        {{-- Limits and Features Card --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-sliders text-primary"></i> Limitlar va Modullar</h3>
            </div>

            @if($license)
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    {{-- User limit --}}
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem;">
                            <span class="text-muted">Foydalanuvchilar soni:</span>
                            <strong>{{ \App\Models\User::count() }} / {{ $license->max_users }}</strong>
                        </div>
                        <div style="height: 10px; background: var(--shadow-dark); border-radius: 5px; overflow: hidden; box-shadow: var(--shadow-pressed-sm);">
                            <div style="height: 100%; width: {{ min(100, (\App\Models\User::count() / $license->max_users) * 100) }}%; background: var(--primary); border-radius: 5px;"></div>
                        </div>
                    </div>

                    {{-- Object limit --}}
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem;">
                            <span class="text-muted">Obyektlar soni:</span>
                            <strong>{{ \App\Models\Obj::count() }} / {{ $license->max_objects }}</strong>
                        </div>
                        <div style="height: 10px; background: var(--shadow-dark); border-radius: 5px; overflow: hidden; box-shadow: var(--shadow-pressed-sm);">
                            <div style="height: 100%; width: {{ min(100, (\App\Models\Obj::count() / $license->max_objects) * 100) }}%; background: var(--primary); border-radius: 5px;"></div>
                        </div>
                    </div>

                    {{-- Feature Flags --}}
                    <div>
                        <span class="text-muted" style="display: block; margin-bottom: 10px; font-size: 0.9rem; font-weight: 700;">YOQILGAN MODULLAR:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <span class="skeuo-badge {{ $license->hasFeature('mobile_api') ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                <i class="bi bi-phone"></i> Mobil ilova (API)
                            </span>
                            <span class="skeuo-badge {{ $license->hasFeature('reports') ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                <i class="bi bi-graph-up-arrow"></i> Hisobotlar generatori
                            </span>
                            <span class="skeuo-badge {{ $license->hasFeature('real_time') ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                <i class="bi bi-lightning"></i> Real-time hodisalar
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- System Sync Card --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header mb-md">
            <h3 class="skeuo-card-title"><i class="bi bi-arrow-repeat text-primary"></i> Litsenziyani yangilash (Sinxronizatsiya)</h3>
        </div>
        <div class="grid-2" style="align-items: center;">
            <div>
                <p class="text-muted" style="margin: 0 0 10px 0;">O'rnatma ID: <strong style="font-family: monospace;">{{ $deviceUuid }}</strong></p>
                <p class="text-muted" style="margin: 0;">Litsenziya serveri: <strong style="font-family: monospace;">{{ env('CONTROL_SERVER_URL', 'http://127.0.0.1:9090') }}</strong></p>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <form action="{{ route('admin.license.refresh') }}" method="POST">
                    @csrf
                    <button type="submit" class="skeuo-btn skeuo-btn-primary">
                        <i class="bi bi-cloud-arrow-down"></i> Serverdan yangilash
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
