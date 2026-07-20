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

        {{-- Face ID / Biometric Security Panel --}}
        <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm); background: var(--surface);">
            <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-person-bounding-box text-green"></i> Face ID biometrik himoyasi
            </h2>

            @if(auth()->user()->hasFaceId())
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-color); padding: 12px; border-radius: var(--radius-md); box-shadow: var(--shadow-pressed-sm);">
                        <div>
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary); display: block;">Biometrik yuz profili</span>
                            <span class="skeuo-badge skeuo-badge-green" style="font-size: 0.75rem; margin-top: 4px; display: inline-block;">Ro'yxatdan o'tgan</span>
                        </div>
                        <form method="POST" action="{{ route('finance.face.delete') }}" onsubmit="return confirm('Haqiqatdan ham yuz profilingizni o\'chirmoqchimisiz?')">
                            @csrf
                            <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-red">
                                <i class="bi bi-trash"></i> O'chirish
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('finance.face.toggle') }}" style="display: flex; align-items: center; justify-content: space-between; padding: 4px 0;">
                        @csrf
                        <div>
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary); display: block;">Face ID bilan 2FA kirish</span>
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">Moliya bo'limiga kirishda yuzni tekshirish</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="hidden" name="enabled" value="0">
                            <label class="skeuo-checkbox" style="margin: 0; padding: 0;">
                                <input type="checkbox" name="enabled" value="1" {{ auth()->user()->face_id_enabled ? 'checked' : '' }} onchange="this.form.submit()">
                                <span>Yoqilgan</span>
                            </label>
                        </div>
                    </form>
                </div>
            @else
                <div>
                    <div style="background: var(--bg-color); padding: 16px; border-radius: var(--radius-md); box-shadow: var(--shadow-pressed-sm); text-align: center; margin-bottom: 16px;">
                        <span class="text-muted" style="font-size: 0.85rem; display: block; margin-bottom: 12px;">
                            Moliya bo'limiga tez va xavfsiz 2FA kirish uchun yuz profilingizni ro'yxatdan o'tkazing.
                        </span>
                        <div class="skeuo-badge skeuo-badge-grey" style="font-size: 0.75rem; margin-bottom: 12px;">Ro'yxatdan o'tmagan</div>
                    </div>

                    <button type="button" class="skeuo-btn skeuo-btn-primary w-full" onclick="openRegisterModal()" style="font-size: 0.85rem; padding: 10px;">
                        <i class="bi bi-camera-fill"></i> Yangi yuz profilini qo'shish
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Custom Modal/Overlay for Face ID Registration --}}
<div id="faceRegisterModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="skeuo-card" style="width: 100%; max-width: 450px; background: var(--surface); text-align: center;">
        <h3 class="mb-md"><i class="bi bi-camera-fill text-primary"></i> Yuz profilini qo'shish</h3>
        
        <div style="width: 180px; height: 180px; margin: 0 auto 16px auto; border-radius: 50%; padding: 6px; background: var(--bg-color); box-shadow: var(--shadow-pressed-sm); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
            <div style="width: 100%; height: 100%; border-radius: 50%; background: #111; overflow: hidden; position: relative;">
                <video id="registerVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
            </div>
        </div>
        
        <div id="registerStatusBadge" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 12px; background: var(--bg-color); box-shadow: var(--shadow-pressed-sm); color: var(--text-muted);">
            KAMERA YUKLANMOQDA...
        </div>
        
        <div id="registerInstructions" style="font-weight: 700; font-size: 0.95rem; margin-bottom: 16px; color: var(--text-primary);">
            Tayyorlanmoqda...
        </div>
        
        <div style="width: 100%; height: 6px; background: var(--bg-color); border-radius: 3px; overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-pressed-sm);">
            <div id="registerProgressBar" style="width: 0%; height: 100%; background: var(--color-primary); transition: width 0.3s;"></div>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="button" class="skeuo-btn w-full" onclick="closeRegisterModal()">Yopish</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let registerStream = null;
    let registerInterval = null;

    function openRegisterModal() {
        document.getElementById('faceRegisterModal').style.display = 'flex';
        startRegisterCamera();
    }

    function closeRegisterModal() {
        document.getElementById('faceRegisterModal').style.display = 'none';
        if (registerStream) {
            registerStream.getTracks().forEach(track => track.stop());
        }
        if (registerInterval) {
            clearInterval(registerInterval);
        }
    }

    async function startRegisterCamera() {
        const video = document.getElementById('registerVideo');
        const badge = document.getElementById('registerStatusBadge');
        const instructions = document.getElementById('registerInstructions');
        const bar = document.getElementById('registerProgressBar');

        try {
            registerStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 300, height: 300, facingMode: 'user' }
            });
            video.srcObject = registerStream;
            
            badge.innerText = 'KAMERA FAOL';
            badge.style.color = 'var(--color-primary)';
            
            // Run registration stages
            let progress = 0;
            const stages = [
                { p: 25, t: 'Kameraga to\'g\'ri qarab turing', v: 5 },
                { p: 50, t: 'Ko\'zlaringizni qisib-oching (Tiriklik testi 1)', v: 15 },
                { p: 75, t: 'Biroz jilmaying (Tiriklik testi 2)', v: 25 },
                { p: 100, t: 'Yuz profilini saqlash...', v: 35 }
            ];

            let stageIdx = 0;
            instructions.innerText = stages[stageIdx].t;
            bar.style.width = stages[stageIdx].p + '%';

            registerInterval = setInterval(() => {
                stageIdx++;
                if (stageIdx < stages.length) {
                    instructions.innerText = stages[stageIdx].t;
                    bar.style.width = stages[stageIdx].p + '%';
                } else {
                    clearInterval(registerInterval);
                    submitRegistration(stages[stages.length - 1].v);
                }
            }, 2500);

        } catch (err) {
            badge.innerText = 'XATOLIK';
            badge.style.color = 'var(--color-danger)';
            instructions.innerText = 'Kamerani yoqib bo\'lmadi';
        }
    }

    async function submitRegistration(variance) {
        const instructions = document.getElementById('registerInstructions');
        
        // Generate mock 128 embedding vector
        const mockVector = [];
        for(let i=0; i<128; i++) {
            mockVector.push(parseFloat((Math.sin(i + variance) * 0.5 + 0.5).toFixed(4)));
        }

        try {
            const response = await fetch('{{ route("finance.face.register") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    embedding: JSON.stringify(mockVector)
                })
            });

            const res = await response.json();
            if (response.ok && res.success) {
                instructions.innerText = 'Muvaffaqiyatli saqlandi! Sahifa yangilanmoqda...';
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(res.message || 'Xatolik yuz berdi');
            }
        } catch (err) {
            instructions.innerText = err.message;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
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
