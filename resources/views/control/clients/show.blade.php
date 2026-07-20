@extends('control.layout')

@section('title', 'Mijoz: ' . $client->company_name)

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">{{ $client->company_name }}</h2>
            <p class="text-muted">Mijoz kartochkasi va litsenziyalari</p>
        </div>
        <a href="{{ route('control.clients.index') }}" class="skeuo-btn text-muted">
            <i class="bi bi-arrow-left"></i> Ro'yxatga qaytish
        </a>
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

    <div class="grid-3 mb-xl">
        {{-- Client Profile info --}}
        <div class="skeuo-card" style="grid-column: span 1;">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-person-badge text-primary"></i> Mijoz profili</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.9rem;">
                <div><span class="text-muted">Mas'ul shaxs:</span> <br><strong>{{ $client->contact_name }}</strong></div>
                <div><span class="text-muted">Telefon:</span> <br><strong>{{ $client->phone ?? '—' }}</strong></div>
                <div><span class="text-muted">Telegram:</span> <br><strong>{{ $client->telegram ?? '—' }}</strong></div>
                <div><span class="text-muted">Email:</span> <br><strong>{{ $client->email ?? '—' }}</strong></div>
                <div><span class="text-muted">Manzil:</span> <br><strong>{{ $client->address ?? '—' }}</strong></div>
                <div><span class="text-muted">Eslatmalar:</span> <br><span class="text-muted">{{ $client->notes ?? '—' }}</span></div>
            </div>
        </div>

        {{-- Add License Form --}}
        <div class="skeuo-card" style="grid-column: span 2;">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-key-fill text-primary"></i> Yangi Litsenziya yaratish</h3>
            </div>
            <form method="POST" action="{{ route('control.clients.license.store', $client->id) }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Mahsulot:</label>
                        <select name="product_id" class="skeuo-input" required>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarif rejasi:</label>
                        <select name="tariff_plan_id" class="skeuo-input" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->product->name }} — {{ $plan->name }} ({{ strtoupper($plan->code) }} / {{ $plan->currency }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Boshlanish sanasi:</label>
                        <input type="date" name="starts_at" class="skeuo-input" required value="{{ now()->toDateString() }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tugash sanasi (muddatsiz uchun bo'sh qoldiring):</label>
                        <input type="date" name="expires_at" class="skeuo-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Faollashtirish limiti (serverlar soni):</label>
                        <input type="number" name="activation_limit" class="skeuo-input" required value="1" min="1">
                    </div>
                </div>

                <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="font-weight: bold;">
                    Litsenziya kalitini generatsiya qilish
                </button>
            </form>
        </div>
    </div>

    {{-- Licenses List --}}
    <div class="skeuo-card mb-xl">
        <div class="skeuo-card-header mb-md">
            <h3 class="skeuo-card-title"><i class="bi bi-shield-check text-primary"></i> Mijoz litsenziyalari</h3>
        </div>
        <div class="table-container">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Litsenziya kaliti</th>
                        <th>Mahsulot / Tarif</th>
                        <th>Muddatlar</th>
                        <th>O'rnatmalar</th>
                        <th>Holat</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->licenses as $lic)
                        <tr>
                            <td><strong style="font-family: monospace; font-size: 1.05rem;" class="text-primary">{{ $lic->license_key }}</strong></td>
                            <td>{{ $lic->product->name }}<br><span class="skeuo-badge skeuo-badge-green" style="text-transform: uppercase;">{{ $lic->tariffPlan->code }}</span></td>
                            <td>
                                Boshlanish: {{ $lic->starts_at->format('d.m.Y') }}<br>
                                Tugash: {{ $lic->expires_at ? $lic->expires_at->format('d.m.Y') : 'Muddatsiz' }}
                            </td>
                            <td>
                                Limit: {{ $lic->activation_limit }} ta o'rnatma<br>
                                Faol o'rnatmalar: <strong>{{ $lic->installations_count }} ta</strong>
                            </td>
                            <td>
                                <span class="skeuo-badge {{ $lic->status === 'active' ? 'skeuo-badge-green' : ($lic->status === 'suspended' ? 'skeuo-badge-red' : 'skeuo-badge-grey') }}">
                                    {{ $lic->status === 'active' ? 'Faol' : ($lic->status === 'suspended' ? 'Bloklangan' : 'Faollashtirilmagan') }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <form action="{{ route('control.licenses.toggle', $lic->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="skeuo-btn skeuo-btn-sm {{ $lic->status === 'suspended' ? 'skeuo-btn-green' : 'skeuo-btn-red' }}">
                                            {{ $lic->status === 'suspended' ? 'Blokdan ochish' : 'Bloklash' }}
                                        </button>
                                    </form>
                                    
                                    {{-- Payment modal trigger trigger button --}}
                                    <button class="skeuo-btn skeuo-btn-sm skeuo-btn-primary" onclick="openPaymentForm({{ $lic->id }}, '{{ $lic->license_key }}')">
                                        To'lov
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @if($lic->installations->count() > 0)
                            <tr style="background: rgba(0,0,0,0.02);">
                                <td colspan="6" style="padding: 10px 24px;">
                                    <div style="font-size: 0.8rem;">
                                        <strong class="text-muted">O'rnatmalar ro'yxati:</strong>
                                        <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                                            @foreach($lic->installations as $inst)
                                                <li>
                                                    Domen: <strong>{{ $inst->domain ?? '—' }}</strong> | 
                                                    IP: <strong>{{ $inst->ip_address ?? '—' }}</strong> | 
                                                    UUID: <span style="font-family: monospace;">{{ $inst->hardware_uuid }}</span> | 
                                                    Oxirgi aloqa: <strong>{{ $inst->last_seen_at ? $inst->last_seen_at->format('d.m.Y H:i') : 'Hech qachon' }}</strong>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Ushbu mijozda litsenziyalar mavjud emas. Yangisini yaratishingiz mumkin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Custom Modal/Overlay for Payment logs --}}
<div id="paymentModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="skeuo-card" style="width: 100%; max-width: 450px; background: var(--surface);">
        <h3 class="mb-md"><i class="bi bi-wallet2 text-primary"></i> To'lovni qayd etish</h3>
        <p class="text-muted mb-md" id="paymentLicKey"></p>
        
        <form method="POST" id="paymentForm" action="">
            @csrf
            <div class="form-group mb-sm">
                <label class="form-label">Sana:</label>
                <input type="date" name="payment_date" class="skeuo-input" required value="{{ now()->toDateString() }}">
            </div>
            
            <div class="grid-2 mb-sm">
                <div class="form-group">
                    <label class="form-label">Summa:</label>
                    <input type="number" step="0.01" name="amount" class="skeuo-input" required placeholder="100.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Valyuta:</label>
                    <select name="currency" class="skeuo-input" required>
                        <option value="USD">USD</option>
                        <option value="UZS">UZS</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-sm">
                <label class="form-label">To'lov usuli:</label>
                <select name="payment_method" class="skeuo-input" required>
                    <option value="cash">Naqd</option>
                    <option value="bank">Bank o'tkazmasi</option>
                    <option value="card">Plastik karta</option>
                </select>
            </div>

            <div class="form-group mb-md">
                <label class="form-label">Izoh:</label>
                <input type="text" name="notes" class="skeuo-input" placeholder="Shartnoma bo'yicha to'lov">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary" style="flex: 1;">Saqlash</button>
                <button type="button" class="skeuo-btn" onclick="closePaymentForm()">Yopish</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPaymentForm(licenseId, licenseKey) {
        document.getElementById('paymentForm').action = '/control/licenses/' + licenseId + '/payment';
        document.getElementById('paymentLicKey').innerText = 'Litsenziya kaliti: ' + licenseKey;
        document.getElementById('paymentModal').style.display = 'flex';
    }
    function closePaymentForm() {
        document.getElementById('paymentModal').style.display = 'none';
    }
</script>
@endsection
