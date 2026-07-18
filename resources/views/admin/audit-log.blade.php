@extends('layouts.app')

@section('title', 'Audit jurnal')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Audit jurnal</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">📋 Audit jurnal</h1>
</div>

{{-- Filters --}}
<div class="filter-panel mb-lg">
    <form method="GET" action="{{ route('admin.audit-log') }}">
        <div class="filter-row">
            <div class="filter-group">
                <label class="form-label">Foydalanuvchi</label>
                <select name="user_id" class="skeuo-select">
                    <option value="">Barchasi</option>
                    @foreach($users ?? [] as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label class="form-label">Amal turi</label>
                <select name="action" class="skeuo-select">
                    <option value="">Barchasi</option>
                    <option value="create" {{ request('action') === 'create' ? 'selected' : '' }}>Yaratish</option>
                    <option value="update" {{ request('action') === 'update' ? 'selected' : '' }}>Tahrirlash</option>
                    <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>O'chirish</option>
                    <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Kirish</option>
                    <option value="pin_verify" {{ request('action') === 'pin_verify' ? 'selected' : '' }}>PIN tasdiqlash</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="form-label">Boshlanish sanasi</label>
                <input type="date" name="date_from" class="skeuo-input" value="{{ request('date_from') }}">
            </div>

            <div class="filter-group">
                <label class="form-label">Tugash sanasi</label>
                <input type="date" name="date_to" class="skeuo-input" value="{{ request('date_to') }}">
            </div>

            <div class="filter-group" style="flex: 0 0 auto; align-self: flex-end;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary">🔍 Qidirish</button>
            </div>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="skeuo-card">
    <div class="skeuo-table-wrapper">
        <table class="skeuo-table">
            <thead>
                <tr>
                    <th>Sana</th>
                    <th>Foydalanuvchi</th>
                    <th>Amal</th>
                    <th>Model</th>
                    <th>Tafsilotlar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs ?? [] as $log)
                    <tr x-data="{ expanded: false }">
                        <td class="text-sm mono-number" style="white-space: nowrap;">
                            {{ $log->created_at->format('d.m.Y H:i:s') }}
                        </td>
                        <td>
                            <strong class="text-sm">{{ $log->user->name ?? 'Tizim' }}</strong>
                        </td>
                        <td>
                            @php
                                $actionColors = [
                                    'create' => 'skeuo-badge-green',
                                    'update' => 'skeuo-badge-copper',
                                    'delete' => 'skeuo-badge-red',
                                    'login'  => 'skeuo-badge-steel',
                                ];
                            @endphp
                            <span class="skeuo-badge {{ $actionColors[$log->action] ?? 'skeuo-badge-steel' }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="text-sm text-muted">{{ $log->auditable_type ?? '—' }}</td>
                        <td>
                            @if($log->changes)
                                <button class="skeuo-btn skeuo-btn-sm" @click="expanded = !expanded">
                                    <span x-text="expanded ? '▼ Yopish' : '▶ Ko\\'rish'"></span>
                                </button>
                                <div x-show="expanded" x-transition class="mt-sm" style="background: rgba(0,0,0,0.2); padding: 8px; border-radius: 4px; font-size: 0.75rem;">
                                    <pre class="mono-number" style="white-space: pre-wrap; color: var(--paper-dark);">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @else
                                <span class="text-muted text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted p-xl">
                            <div class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                <p>Audit yozuvlari topilmadi</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(isset($logs) && method_exists($logs, 'links'))
    <div class="skeuo-pagination mt-lg">
        {{ $logs->appends(request()->query())->links() }}
    </div>
@endif

@endsection
