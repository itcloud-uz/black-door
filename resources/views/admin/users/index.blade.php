@extends('layouts.app')

@section('title', 'Foydalanuvchilar')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Foydalanuvchilar</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">👥 Foydalanuvchilar</h1>
    <a href="{{ route('admin.users.create') }}" class="skeuo-btn skeuo-btn-primary">
        ➕ Yangi foydalanuvchi
    </a>
</div>

{{-- Filter --}}
<div class="filter-panel mb-lg" x-data="tableFilter">
    <div class="filter-row">
        <div class="filter-group">
            <label class="form-label">Qidirish</label>
            <input
                type="text"
                class="skeuo-input"
                placeholder="Ism, email, telefon..."
                x-model="searchQuery"
                @input="filterRows()"
            >
        </div>
        <div class="filter-group" style="max-width: 200px;">
            <label class="form-label">Rol</label>
            <select class="skeuo-select" onchange="window.location.href='?role='+this.value">
                <option value="">Barchasi</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Admin</option>
                <option value="financier" {{ request('role') === 'financier' ? 'selected' : '' }}>Moliyachi</option>
                <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Menejer</option>
                <option value="employee" {{ request('role') === 'employee' ? 'selected' : '' }}>Xodim</option>
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="skeuo-table-wrapper mt-lg">
        <table class="skeuo-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ism</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Holat</th>
                    <th>Amallar</th>
                </tr>
            </thead>
            <tbody x-ref="tableBody">
                @forelse($users ?? [] as $index => $user)
                    <tr>
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td class="text-sm">{{ $user->email }}</td>
                        <td class="text-sm">{{ $user->phone ?? '—' }}</td>
                        <td><x-role-badge :role="$user->role" /></td>
                        <td>
                            <span class="skeuo-badge {{ $user->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                {{ $user->is_active ? 'Faol' : 'Nofaol' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-xs">
                                <a href="#" class="skeuo-btn skeuo-btn-sm" title="Tahrirlash">✏️</a>
                                @if($user->is_active)
                                    <button class="skeuo-btn skeuo-btn-sm skeuo-btn-danger" title="O'chirish">🚫</button>
                                @else
                                    <button class="skeuo-btn skeuo-btn-sm skeuo-btn-success" title="Faollashtirish">✅</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted p-xl">
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <p>Foydalanuvchilar topilmadi</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(isset($users) && method_exists($users, 'links'))
    <div class="skeuo-pagination">
        {{ $users->links() }}
    </div>
@endif

@endsection
