@extends('layouts.finance')

@section('title', 'Moliya sozlamalari')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current"><i class="bi bi-gear"></i> Sozlamalar</span></li>
@endsection

@section('finance-content')
<div class="page-header" style="margin-bottom: 20px; border-bottom: 1px dashed rgba(0,0,0,0.08); padding-bottom: 12px;">
    <h1 class="page-title" style="margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">
        <i class="bi bi-gear-fill text-gold"></i> Xavfsiz sozlamalar bo'limi
    </h1>
</div>

<div class="grid-2 mb-xl" style="align-items: start;">
    {{-- Branding Settings Card (Only Super Admin) --}}
    @if(auth()->user()->isAdmin())
        <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm); background: var(--surface);">
            <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-palette-fill text-green"></i> Brending va Tizim Sozlamalari
            </h2>
            
            <form method="POST" action="{{ route('finance.settings.update') }}" enctype="multipart/form-data">
                @csrf
                
                <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px;">
                    {{-- Current Logo Preview --}}
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <span class="form-label" style="margin-bottom: 8px; font-size: 0.75rem;">Joriy Logo</span>
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 12px; background: var(--surface); box-shadow: var(--shadow-pressed-sm); overflow: hidden; padding: 4px;">
                            <img src="{{ file_exists(public_path('branding/custom_mark.png')) ? asset('branding/custom_mark.png') : asset('branding/mark.png') }}?v={{ time() }}" alt="Current Logo" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>

                    {{-- Upload File Input --}}
                    <div style="flex-grow: 1;">
                        <label class="form-label" for="logo" style="font-size: 0.85rem;">Yangi logotip (PNG, JPG)</label>
                        <input type="file" id="logo" name="logo" class="skeuo-input" accept="image/png, image/jpeg, image/jpg" style="padding: 6px 10px; height: auto; font-size: 0.85rem;">
                        <span style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 4px; display: block; line-height: 1.3;">
                            Tavsiya: shaffof fondagi kvadrat shakl.
                        </span>
                        @error('logo') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="company_name" style="font-size: 0.85rem;">Kompaniya / Tizim nomi</label>
                    <input type="text" id="company_name" name="company_name" class="skeuo-input"
                           value="{{ $companyName }}" placeholder="Masalan: Black Door" required style="font-size: 0.9rem;">
                    @error('company_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label class="form-label" for="company_tagline" style="font-size: 0.85rem;">Tizim shiori (Tagline)</label>
                    <input type="text" id="company_tagline" name="company_tagline" class="skeuo-input"
                           value="{{ $companyTagline }}" placeholder="Masalan: Moliyaviy Boshqaruv" required style="font-size: 0.9rem;">
                    @error('company_tagline') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label" style="font-size: 0.85rem;">Asosiy rang uslubi (Theme Accent)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; margin-top: 8px;">
                        {{-- Green Accent --}}
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="accent_color" value="green" {{ $accentColor === 'green' ? 'checked' : '' }} style="display: none;">
                            <div class="color-bubble" data-color="green" style="width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(135deg, #58d68d, #2ec4b6); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'green' ? 'transform: scale(1.25); border: 2px solid var(--text-primary);' : '' }}"></div>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary);">Yashil</span>
                        </label>

                        {{-- Blue Accent --}}
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="accent_color" value="blue" {{ $accentColor === 'blue' ? 'checked' : '' }} style="display: none;">
                            <div class="color-bubble" data-color="blue" style="width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(135deg, #5dade2, #2e86c1); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'blue' ? 'transform: scale(1.25); border: 2px solid var(--text-primary);' : '' }}"></div>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary);">Moviy</span>
                        </label>

                        {{-- Red Accent --}}
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="accent_color" value="red" {{ $accentColor === 'red' ? 'checked' : '' }} style="display: none;">
                            <div class="color-bubble" data-color="red" style="width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(135deg, #ff8a7a, #e74c3c); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'red' ? 'transform: scale(1.25); border: 2px solid var(--text-primary);' : '' }}"></div>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary);">Qizil</span>
                        </label>
                    </div>
                    @error('accent_color') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div style="margin-top: 24px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                    <button type="submit" class="skeuo-btn skeuo-btn-primary" style="font-size: 0.85rem; padding: 8px 16px;">
                        <i class="bi bi-check2-circle"></i> Sozlamalarni saqlash
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Security Panels --}}
    <div style="display: flex; flex-direction: column; gap: 20px; {{ !auth()->user()->isAdmin() ? 'grid-column: span 2;' : '' }}">
        {{-- Change Password Panel --}}
        <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm); background: var(--surface);">
            <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-shield-lock-fill text-copper"></i> Parolni o'zgartirish
            </h2>
            
            <form method="POST" action="{{ route('finance.settings.update') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="old_password" style="font-size: 0.85rem;">Hozirgi parol</label>
                    <input type="password" id="old_password" name="old_password" class="skeuo-input" placeholder="••••••••" required style="font-size: 0.9rem;">
                    @error('old_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-row" style="margin-top: 12px; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="new_password" style="font-size: 0.85rem;">Yangi parol</label>
                        <input type="password" id="new_password" name="new_password" class="skeuo-input" placeholder="Min. 6 xona" required style="font-size: 0.9rem;">
                        @error('new_password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password_confirmation" style="font-size: 0.85rem;">Tasdiqlash</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="skeuo-input" placeholder="••••••••" required style="font-size: 0.9rem;">
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                    <button type="submit" class="skeuo-btn skeuo-btn-primary" style="font-size: 0.85rem; padding: 8px 16px;">
                        <i class="bi bi-key-fill"></i> Parolni yangilash
                    </button>
                </div>
            </form>
        </div>

        {{-- Change PIN Code Panel --}}
        <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm); background: var(--surface);">
            <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-dialpad text-gold"></i> Moliya PIN kodini yangilash
            </h2>
            
            <form method="POST" action="{{ route('finance.settings.update') }}">
                @csrf
                
                <div class="form-row" style="gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="current_password" style="font-size: 0.85rem;">Tasdiqlash parolingiz</label>
                        <input type="password" id="current_password" name="current_password" class="skeuo-input" placeholder="Hozirgi tizim paroli" required style="font-size: 0.9rem;">
                        @error('current_password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_pin" style="font-size: 0.85rem;">Yangi 4 xonali PIN</label>
                        <input type="text" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" class="skeuo-input" placeholder="Masalan: 1234" required style="font-size: 0.9rem;">
                        @error('new_pin') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                    <button type="submit" class="skeuo-btn skeuo-btn-primary" style="font-size: 0.85rem; padding: 8px 16px;">
                        <i class="bi bi-shield-check"></i> PIN kodni yangilash
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Dynamic theme preview bubbles
        const inputs = document.querySelectorAll('input[name="accent_color"]');
        const bubbles = document.querySelectorAll('.color-bubble');

        inputs.forEach(input => {
            input.addEventListener('change', (e) => {
                bubbles.forEach(b => {
                    b.style.transform = '';
                    b.style.border = '';
                });
                const selectedBubble = document.querySelector(`.color-bubble[data-color="${e.target.value}"]`);
                if (selectedBubble) {
                    selectedBubble.style.transform = 'scale(1.25)';
                    selectedBubble.style.border = '2px solid var(--text-primary)';
                }
            });
        });
    });
</script>
@endpush
@endsection
