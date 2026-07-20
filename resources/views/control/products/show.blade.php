@extends('control.layout')

@section('title', $product->name)

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">{{ $product->name }}</h2>
            <p class="text-muted">Kodi: <strong style="font-family: monospace;">{{ $product->code }}</strong> | Boshqaruv paneli</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('control.products.edit', $product->id) }}" class="skeuo-btn skeuo-btn-primary">
                <i class="bi bi-pencil"></i> Tahrirlash
            </a>
            <form action="{{ route('control.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Haqiqatdan ham ushbu mahsulotni o\'chirmoqchimisiz? Barcha tariflar, versiyalar va litsenziyalar ham o\'chib ketadi!')">
                @csrf
                @method('DELETE')
                <button type="submit" class="skeuo-btn skeuo-btn-red">
                    <i class="bi bi-trash"></i> O'chirish
                </button>
            </form>
            <a href="{{ route('control.products.index') }}" class="skeuo-btn text-muted">
                <i class="bi bi-arrow-left"></i> Katalogga qaytish
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="skeuo-alert skeuo-alert-success mb-md">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="grid-2 mb-xl">
        {{-- Version History Section --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-git text-primary"></i> Versiyalar tarixi</h3>
            </div>
            
            {{-- Add Version Form --}}
            <form method="POST" action="{{ route('control.products.version.store', $product->id) }}" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; background: var(--bg-color); padding: 12px; border-radius: var(--radius-md); box-shadow: var(--shadow-pressed-sm);">
                @csrf
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" style="font-size: 0.75rem;">Versiya (e.g. v1.0.0):</label>
                    <input type="text" name="version" class="skeuo-input-style" placeholder="v1.0.0" required>
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" style="font-size: 0.75rem;">Sana:</label>
                    <input type="date" name="release_date" class="skeuo-input-style" required value="{{ now()->toDateString() }}">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" style="font-size: 0.75rem;">Reliz eslatmalari:</label>
                    <input type="text" name="release_notes" class="skeuo-input-style" placeholder="Yangi funksiyalar tavsifi...">
                </div>
                <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="grid-column: span 2; margin-top: 8px;">
                    Versiya qo'shish
                </button>
            </form>

            {{-- Versions Table --}}
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Versiya</th>
                            <th>Reliz sanasi</th>
                            <th>Izohlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->versions->sortByDesc('release_date') as $ver)
                            <tr>
                                <td><strong style="font-family: monospace;">{{ $ver->version }}</strong></td>
                                <td>{{ $ver->release_date->format('d.m.Y') }}</td>
                                <td>{{ $ver->release_notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Hozircha versiyalar qo'shilmagan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tariff Plans Section --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-sliders text-primary"></i> Tarif rejalari (Planlar)</h3>
            </div>

            {{-- Add Plan Form --}}
            <form method="POST" action="{{ route('control.products.plan.store', $product->id) }}" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; background: var(--bg-color); padding: 16px; border-radius: var(--radius-md); box-shadow: var(--shadow-pressed-sm);">
                @csrf
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Tarif nomi:</label>
                        <input type="text" name="name" class="skeuo-input-style" placeholder="Standart" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Tarif kodi (Slug):</label>
                        <input type="text" name="code" class="skeuo-input-style" placeholder="standard" required>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Narxi:</label>
                        <input type="number" step="0.01" name="price" class="skeuo-input-style" placeholder="99.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Valyuta:</label>
                        <select name="currency" class="skeuo-input-style" required>
                            <option value="USD">USD</option>
                            <option value="UZS">UZS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Muddati (Kun):</label>
                        <input type="number" name="duration_days" class="skeuo-input-style" placeholder="Bo'sh qolsa = muddatsiz">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Maks. userlar soni:</label>
                        <input type="number" name="max_users" class="skeuo-input-style" placeholder="10" required value="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Maks. obyektlar soni:</label>
                        <input type="number" name="max_objects" class="skeuo-input-style" placeholder="5" required value="5">
                    </div>
                </div>

                {{-- Feature flags --}}
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">YOQILADIGAN FUNKSIYALAR (FEATURES):</label>
                    <div style="display: flex; gap: 16px; margin-top: 6px;">
                        <label class="skeuo-checkbox">
                            <input type="checkbox" name="features[mobile_api]" checked>
                            <span>Mobil ilova (API)</span>
                        </label>
                        <label class="skeuo-checkbox">
                            <input type="checkbox" name="features[reports]" checked>
                            <span>Hisobotlar</span>
                        </label>
                        <label class="skeuo-checkbox">
                            <input type="checkbox" name="features[real_time]" checked>
                            <span>Real-time</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="margin-top: 8px;">
                    Tarif rejasi yaratish
                </button>
            </form>

            {{-- Plans list --}}
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Tarif</th>
                            <th>Muddati</th>
                            <th>Narxi</th>
                            <th>Limitlar (User/Obj)</th>
                            <th>Modullar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->tariffPlans as $plan)
                            <tr>
                                <td><strong style="text-transform: uppercase;">{{ $plan->code }}</strong><br><small class="text-muted">{{ $plan->name }}</small></td>
                                <td>{{ $plan->duration_days ? $plan->duration_days . ' kun' : 'Muddatsiz' }}</td>
                                <td style="font-weight: bold;">
                                    {{ $plan->currency === 'USD' ? '$' : '' }}{{ number_format($plan->price / 100, 2, '.', ' ') }}{{ $plan->currency === 'UZS' ? ' UZS' : '' }}
                                </td>
                                <td>Users: {{ $plan->max_users }}<br>Objects: {{ $plan->max_objects }}</td>
                                <td>
                                    @if($plan->features)
                                        @foreach($plan->features as $feat => $enabled)
                                            @if($enabled)
                                                <span class="skeuo-badge skeuo-badge-green" style="font-size: 0.6rem; padding: 2px 4px;">{{ $feat }}</span>
                                            @endif
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Hozircha tarif rejalari yo'q.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
