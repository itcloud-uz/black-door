@extends('layouts.app')

@section('title', 'Tizim sozlamalari')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current"><i class="bi bi-gear"></i> Sozlamalar</span></li>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title"><i class="bi bi-gear-fill"></i> Tizim sozlamalari</h1>
</div>

<div class="grid-2 mb-xl">
    {{-- Branding Settings Card --}}
    <div class="skeuo-card">
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-palette-fill text-green"></i> Brending va Tizim Sozlamalari
        </h2>
        
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="company_name">Kompaniya / Tizim nomi</label>
                <input type="text" id="company_name" name="company_name" class="skeuo-input"
                       value="{{ $companyName }}" placeholder="Masalan: Black Door" required>
                @error('company_name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label" for="company_tagline">Tizim shiori (Tagline)</label>
                <input type="text" id="company_tagline" name="company_tagline" class="skeuo-input"
                       value="{{ $companyTagline }}" placeholder="Masalan: Moliyaviy Boshqaruv" required>
                @error('company_tagline') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label class="form-label">Asosiy rang uslubi (Theme Accent)</label>
                <div style="display: flex; gap: 20px; align-items: center; margin-top: 8px;">
                    {{-- Green Accent --}}
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="accent_color" value="green" {{ $accentColor === 'green' ? 'checked' : '' }} style="display: none;" id="color_green">
                        <div class="color-bubble" data-color="green" style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #58d68d, #2ec4b6); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'green' ? 'transform: scale(1.2); border: 2px solid var(--text-primary);' : '' }}"></div>
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-secondary);">Yashil (Mint)</span>
                    </label>

                    {{-- Blue Accent --}}
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="accent_color" value="blue" {{ $accentColor === 'blue' ? 'checked' : '' }} style="display: none;" id="color_blue">
                        <div class="color-bubble" data-color="blue" style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #5dade2, #2e86c1); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'blue' ? 'transform: scale(1.2); border: 2px solid var(--text-primary);' : '' }}"></div>
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-secondary);">Moviy (Ocean)</span>
                    </label>

                    {{-- Red Accent --}}
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="accent_color" value="red" {{ $accentColor === 'red' ? 'checked' : '' }} style="display: none;" id="color_red">
                        <div class="color-bubble" data-color="red" style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #ff8a7a, #e74c3c); transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--shadow-neutral-sm); {{ $accentColor === 'red' ? 'transform: scale(1.2); border: 2px solid var(--text-primary);' : '' }}"></div>
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-secondary);">Qizil (Crimson)</span>
                    </label>
                </div>
                @error('accent_color') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div style="margin-top: 24px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary">
                    <i class="bi bi-check2-circle"></i> Brendingni saqlash
                </button>
            </div>
        </form>
    </div>

    {{-- Security / Password & PIN Code --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">
        {{-- Change Password Panel --}}
        <div class="skeuo-card">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-shield-lock-fill text-copper"></i> Parolni o'zgartirish
            </h2>
            
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="old_password">Hozirgi parol</label>
                    <input type="password" id="old_password" name="old_password" class="skeuo-input" placeholder="••••••••" required>
                    @error('old_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-row" style="margin-top: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="new_password">Yangi parol</label>
                        <input type="password" id="new_password" name="new_password" class="skeuo-input" placeholder="Kamida 6 xona" required>
                        @error('new_password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password_confirmation">Tasdiqlash</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="skeuo-input" placeholder="••••••••" required>
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                    <button type="submit" class="skeuo-btn skeuo-btn-primary">
                        <i class="bi bi-key-fill"></i> Parolni yangilash
                    </button>
                </div>
            </form>
        </div>

        {{-- Change PIN Code Panel --}}
        <div class="skeuo-card">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-dialpad text-gold"></i> Moliya bo'limi PIN kodini yangilash
            </h2>
            
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Tasdiqlash parolingiz</label>
                        <input type="password" id="current_password" name="current_password" class="skeuo-input" placeholder="Hozirgi tizim paroli" required>
                        @error('current_password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_pin">Yangi 4 xonali PIN kod</label>
                        <input type="text" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" class="skeuo-input" placeholder="Masalan: 1234" required>
                        @error('new_pin') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px dashed rgba(0,0,0,0.05); padding-top: 16px;">
                    <button type="submit" class="skeuo-btn skeuo-btn-primary">
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
                    selectedBubble.style.transform = 'scale(1.2)';
                    selectedBubble.style.border = '2px solid var(--text-primary)';
                }
            });
        });
    });
</script>
@endpush
@endsection
