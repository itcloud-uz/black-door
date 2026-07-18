{{--
    Flash Message Component
    Displays session flash messages with stamp-like styling
--}}

@if(session('success'))
    <div class="flash-message flash-success" x-data="{ show: true }" x-show="show" x-transition>
        <span class="ink-stamp ink-stamp-approved animate-stamp">✓</span>
        <span>{{ session('success') }}</span>
        <button class="flash-close" @click="show = false">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="flash-message flash-error" x-data="{ show: true }" x-show="show" x-transition>
        <span class="ink-stamp ink-stamp-rejected animate-stamp">✕</span>
        <span>{{ session('error') }}</span>
        <button class="flash-close" @click="show = false">&times;</button>
    </div>
@endif

@if(session('warning'))
    <div class="flash-message flash-warning" x-data="{ show: true }" x-show="show" x-transition>
        <span><i class="bi bi-exclamation-triangle"></i></span>
        <span>{{ session('warning') }}</span>
        <button class="flash-close" @click="show = false">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="flash-message flash-error" x-data="{ show: true }" x-show="show" x-transition>
        <span class="ink-stamp ink-stamp-rejected animate-stamp">✕</span>
        <div>
            <strong>Xatoliklar mavjud:</strong>
            <ul style="margin: 4px 0 0 16px; list-style: disc;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button class="flash-close" @click="show = false">&times;</button>
    </div>
@endif
