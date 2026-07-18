@extends('layouts.finance')

@section('title', 'Kategoriyalar')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current">Kategoriyalar</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg">
    <h2 class="handwriting-title">📂 Kategoriyalar Daraxti</h2>
</div>

<div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
    {{-- Form --}}
    <div class="skeuo-card-paper">
        <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px;">📂 Yangi Kategoriya</h3>
        
        <form method="POST" action="{{ route('finance.categories.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label form-label-paper">Kategoriya nomi</label>
                <input type="text" name="name" class="skeuo-input skeuo-input-paper" value="{{ old('name') }}" placeholder="Masalan: Ish haqi, ijara..." required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Turi</label>
                <select name="type" class="skeuo-select skeuo-select-paper" required>
                    <option value="expense">Chiqim</option>
                    <option value="income">Kirim</option>
                </select>
                @error('type') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Ota kategoriya (ixtiyoriy)</label>
                <select name="parent_id" class="skeuo-select skeuo-select-paper">
                    <option value="">— Mustaqil Kategoriya —</option>
                    @foreach($categories ?? [] as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            [{{ $parent->type === 'income' ? 'Kirim' : 'Chiqim' }}] {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;">💾 Saqlash</button>
        </form>
    </div>

    {{-- Tree list --}}
    <div class="skeuo-card-paper">
        <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px;">📜 Mavjud kategoriyalar</h3>

        <div style="font-family: var(--font-handwriting); font-size: 1.2rem; color: var(--paper-dark);">
            @forelse($categories ?? [] as $cat)
                <div style="margin-bottom: var(--space-md); border-bottom: 1px dashed var(--paper-line); padding-bottom: 8px;">
                    <div class="d-flex justify-between items-center">
                        <div>
                            <span>{{ $cat->type === 'income' ? '📈 Kirim' : '📉 Chiqim' }} — <strong>{{ $cat->name }}</strong></span>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('finance.categories.destroy', $cat->id) }}" onsubmit="return confirm('Kategoriyani o'chirishni xohlaysizmi?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-danger" style="padding: 2px 6px;">❌ O'chirish</button>
                            </form>
                        </div>
                    </div>

                    {{-- Children --}}
                    @if($cat->children->count() > 0)
                        <div style="margin-left: 24px; margin-top: 6px;">
                            @foreach($cat->children as $child)
                                <div class="d-flex justify-between items-center p-xs" style="border-left: 2px solid var(--paper-line); padding-left: 12px; margin-bottom: 4px;">
                                    <span>↳ {{ $child->name }}</span>
                                    <form method="POST" action="{{ route('finance.categories.destroy', $child->id) }}" onsubmit="return confirm('Kategoriyani o'chirishni xohlaysizmi?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-danger" style="padding: 2px 6px;">❌ O'chirish</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted text-center py-md">Kategoriyalar topilmadi</p>
            @endforelse
        </div>
    </div>
</div>

@endsection