@extends('control.layout')

@section('title', 'Control Dashboard')

@section('content')
<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Tizim holati</h2>
        <span class="text-muted">Sotuv va litsenziyalar boshqaruvi</span>
    </div>

    {{-- Stats Grid --}}
    <div class="grid-4 mb-xl">
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-steel"><i class="bi bi-wallet2"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Tushum (USD)</div>
                <div class="stat-card-value">${{ $totalUsdFormatted }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-green"><i class="bi bi-cash"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Tushum (UZS)</div>
                <div class="stat-card-value" style="font-size: 1.2rem;">{{ $totalUzsFormatted }} UZS</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-steel"><i class="bi bi-key"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Faol litsenziyalar</div>
                <div class="stat-card-value">{{ $activeLicensesCount }} ta</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-green"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Yangi arizalar</div>
                <div class="stat-card-value">{{ $pendingRequestsCount }} ta</div>
            </div>
        </div>
    </div>

    <div class="grid-2 mb-xl">
        {{-- Upcoming Expirations --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-alarm text-primary"></i> Yaqin orada tugaydigan litsenziyalar (30 kunlik)</h3>
            </div>
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Mijoz</th>
                            <th>Tarif</th>
                            <th>Tugash muddati</th>
                            <th>Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingExpirations as $lic)
                            <tr>
                                <td><strong>{{ $lic->client->company_name }}</strong></td>
                                <td><span class="skeuo-badge skeuo-badge-green" style="text-transform: uppercase;">{{ $lic->tariffPlan->code }}</span></td>
                                <td><span class="text-red font-bold">{{ $lic->expires_at->format('d.m.Y') }}</span></td>
                                <td><a href="{{ route('control.clients.show', $lic->client_id) }}" class="skeuo-btn skeuo-btn-sm">Mijoz sahifasi</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Yaqin 30 kun ichida tugaydigan litsenziyalar mavjud emas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Troubled Installations (No contact in 24 hours) --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-wifi-off text-red"></i> Aloqaga chiqmagan o'rnatmalar (>24 soat)</h3>
            </div>
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Mijoz</th>
                            <th>Domen</th>
                            <th>Oxirgi ko'rinish</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($troubledInstallations as $inst)
                            <tr>
                                <td><strong>{{ $inst->license->client->company_name }}</strong></td>
                                <td><a href="http://{{ $inst->domain }}" target="_blank">{{ $inst->domain ?? '—' }}</a></td>
                                <td><span class="text-red font-bold">{{ $inst->last_seen_at ? $inst->last_seen_at->format('d.m.Y H:i') : 'Hech qachon' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Barcha faol o'rnatmalar normal aloqada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Payments & Recent activations --}}
    <div class="grid-2">
        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-cash-stack text-primary"></i> So'nggi to'lovlar</h3>
            </div>
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Mijoz</th>
                            <th>Sana</th>
                            <th>Summa</th>
                            <th>Usul</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td><strong>{{ $payment->license->client->company_name }}</strong></td>
                                <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                                <td style="font-weight: bold;" class="amount-positive">
                                    {{ $payment->currency === 'USD' ? '$' : '' }}{{ number_format($payment->amount / 100, 2, '.', ' ') }}{{ $payment->currency === 'UZS' ? ' UZS' : '' }}
                                </td>
                                <td><span class="skeuo-badge skeuo-badge-green">{{ $payment->payment_method }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">To'lovlar topilmadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="skeuo-card">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-activity text-primary"></i> So'nggi faollashtirishlar</h3>
            </div>
            <div class="table-container">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Mijoz</th>
                            <th>Hardware UUID</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInstallations as $inst)
                            <tr>
                                <td><strong>{{ $inst->license->client->company_name }}</strong></td>
                                <td style="font-family: monospace; font-size: 0.8rem;">{{ Str::limit($inst->hardware_uuid, 20) }}</td>
                                <td>{{ $inst->created_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Faollashtirishlar topilmadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
