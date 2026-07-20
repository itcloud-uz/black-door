@extends('control.layout')

@section('title', 'Mahsulotlar katalogi')

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Mahsulotlar katalogi</h2>
        <a href="{{ route('control.products.create') }}" class="skeuo-btn skeuo-btn-primary">
            <i class="bi bi-plus-circle"></i> Yangi mahsulot qo'shish
        </a>
    </div>

    @if(session('success'))
        <div class="skeuo-alert skeuo-alert-success mb-md">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="skeuo-card">
        <div class="table-container">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Mahsulot nomi</th>
                        <th>Mahsulot kodi</th>
                        <th>Versiyalar soni</th>
                        <th>Tarif rejalari soni</th>
                        <th>Holati</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $prod)
                        <tr>
                            <td><strong>{{ $prod->name }}</strong></td>
                            <td style="font-family: monospace;">{{ $prod->code }}</td>
                            <td>{{ $prod->versions_count }} ta</td>
                            <td>{{ $prod->tariff_plans_count }} ta</td>
                            <td>
                                <span class="skeuo-badge {{ $prod->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $prod->is_active ? 'Faol' : 'Nofaol' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('control.products.show', $prod->id) }}" class="skeuo-btn skeuo-btn-sm">
                                    <i class="bi bi-eye"></i> Tafsilotlar (Plan/Versiya)
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Katalogda mahsulotlar mavjud emas. Yangi mahsulot qo'shing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
