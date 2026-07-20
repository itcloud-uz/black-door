@extends('control.layout')

@section('title', 'Litsenziya sotib olish arizalari')

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Arizalar paneli</h2>
        <span class="text-muted">Mijozlar tomonidan qoldirilgan litsenziya sotib olish so'rovlari</span>
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
                        <th>Mijoz kompaniya</th>
                        <th>Kontakt shaxs</th>
                        <th>Telefon / Email</th>
                        <th>Mahsulot / Tarif</th>
                        <th>Status</th>
                        <th>Eslatma</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td><strong>{{ $req->company_name }}</strong></td>
                            <td>{{ $req->contact_name }}</td>
                            <td>{{ $req->phone }}<br><small class="text-muted">{{ $req->email }}</small></td>
                            <td>{{ $req->product->name }}<br><span class="skeuo-badge skeuo-badge-green" style="text-transform: uppercase;">{{ $req->tariffPlan->code }}</span></td>
                            <td>
                                <span class="skeuo-badge {{ $req->status === 'pending' ? 'skeuo-badge-grey' : ($req->status === 'contacted' ? 'skeuo-badge-green' : ($req->status === 'approved' ? 'skeuo-badge-green' : 'skeuo-badge-red')) }}">
                                    {{ $req->status === 'pending' ? 'Kutilmoqda' : ($req->status === 'contacted' ? 'Bog\'lanildi' : ($req->status === 'approved' ? 'Tasdiqlandi' : 'Rad etildi')) }}
                                </span>
                            </td>
                            <td>{{ $req->notes ?? '—' }}</td>
                            <td>
                                <form action="{{ route('control.requests.status.update', $req->id) }}" method="POST" style="display: inline-flex; gap: 4px;">
                                    @csrf
                                    <select name="status" class="skeuo-input-style" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 4px 8px;">
                                        <option value="pending" {{ $req->status === 'pending' ? 'selected' : '' }}>Kutilmoqda</option>
                                        <option value="contacted" {{ $req->status === 'contacted' ? 'selected' : '' }}>Bog'lanildi</option>
                                        <option value="approved" {{ $req->status === 'approved' ? 'selected' : '' }}>Tasdiqlandi</option>
                                        <option value="rejected" {{ $req->status === 'rejected' ? 'selected' : '' }}>Rad etildi</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Sotib olish uchun yuborilgan arizalar mavjud emas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
