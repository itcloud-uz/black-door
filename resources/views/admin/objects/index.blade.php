@extends('layouts.app')

@section('title', 'Obyektlar')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Obyektlar</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-building"></i> Obyektlar</h1>
    <a href="{{ route('admin.objects.create') }}" class="skeuo-btn skeuo-btn-primary">
        <i class="bi bi-plus-lg"></i> Yangi obyekt
    </a>
</div>

<div class="grid-auto">
    @forelse($objects ?? [] as $object)
        <div class="skeuo-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <a href="{{ route('admin.objects.show', $object->id) }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="skeuo-card-header">
                        <div class="d-flex items-center gap-sm">
                            <span style="font-size: 1.5rem; color: var(--accent-green);">
                                @switch($object->type->value)
                                    @case('factory') <i class="bi bi-building-gear"></i> @break
                                    @case('construction') <i class="bi bi-cone-striped"></i> @break
                                    @case('warehouse') <i class="bi bi-shop"></i> @break
                                    @endswitch
                            </span>
                            <div>
                                <h3 class="skeuo-card-title" style="margin: 0; font-size: 1.05rem;">{{ $object->name }}</h3>
                                <span class="text-xs text-muted">{{ $object->type->label() }}</span>
                            </div>
                        </div>
                        <span class="skeuo-badge {{ $object->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                            {{ $object->is_active ? 'Faol' : 'Nofaol' }}
                        </span>
                    </div>

                    @if($object->address)
                        <p class="text-sm text-muted mb-sm" style="margin-top: 8px;">📍 {{ $object->address }}</p>
                    @endif

                    @if($object->activeManager)
                        <div class="d-flex items-center gap-sm mt-md" style="margin-top: 12px;">
                            <span class="text-sm"><i class="bi bi-person"></i> Menejer:</span>
                            <strong class="text-sm">{{ $object->activeManager->user->name ?? '—' }}</strong>
                        </div>
                    @else
                        <p class="text-sm text-muted mt-md" style="margin-top: 12px;"><i class="bi bi-person"></i> Menejer biriktirilmagan</p>
                    @endif
                </a>
            </div>

            <div style="margin-top: 16px; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <a href="{{ route('admin.objects.show', $object->id) }}" class="skeuo-btn skeuo-btn-neutral" style="font-size: 0.8rem; padding: 4px 10px;">
                    <i class="bi bi-eye"></i> Tafsilotlar
                </a>
                <div style="display: flex; gap: 6px;">
                    <a href="{{ route('admin.objects.edit', $object->id) }}" class="skeuo-btn skeuo-btn-neutral" style="font-size: 0.8rem; padding: 4px 8px;">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.objects.destroy', $object->id) }}" method="POST" onsubmit="return confirm('Haqiqatdan ham ushbu obyektni o\'chirmoqchimisiz?');" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="skeuo-btn skeuo-btn-red" style="font-size: 0.8rem; padding: 4px 8px; border: none; cursor: pointer;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="skeuo-card" style="grid-column: 1 / -1;">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-building"></i></div>
                <p class="empty-state-text">Hali obyektlar qo'shilmagan</p>
                <a href="{{ route('admin.objects.create') }}" class="skeuo-btn skeuo-btn-primary">
                    <i class="bi bi-plus-lg"></i> Birinchi obyektni qo'shing
                </a>
            </div>
        </div>
    @endforelse
</div>

@endsection
