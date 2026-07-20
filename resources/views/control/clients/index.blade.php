@extends('control.layout')

@section('title', 'Mijozlar ro\'yxati')

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Mijozlar katalogi</h2>
        <a href="{{ route('control.clients.create') }}" class="skeuo-btn skeuo-btn-primary">
            <i class="bi bi-plus-circle"></i> Yangi mijoz qo'shish
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
                        <th>Kompaniya nomi</th>
                        <th>Mas'ul shaxs</th>
                        <th>Telefon</th>
                        <th>Litsenziyalar</th>
                        <th>Holati</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td><strong>{{ $client->company_name }}</strong></td>
                            <td>{{ $client->contact_name }}</td>
                            <td>{{ $client->phone ?? '—' }}</td>
                            <td>{{ $client->licenses_count }} ta</td>
                            <td>
                                <span class="skeuo-badge {{ $client->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $client->is_active ? 'Faol' : 'Bloklangan' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('control.clients.show', $client->id) }}" class="skeuo-btn skeuo-btn-sm">
                                    <i class="bi bi-eye"></i> Profil & Litsenziya
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Mijozlar mavjud emas. Yangi mijoz qo'shing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
