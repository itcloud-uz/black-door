<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\Setting::get('company_name', 'Black Door') }} — Face ID Tasdiqlash</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ \App\Models\Setting::get('theme_css_version', '1') }}">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--space-md);
            background-color: var(--bg-color);
            font-family: var(--font-body);
        }

        .face-container {
            width: 100%;
            max-width: 420px;
        }

        .face-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: var(--space-xl);
            box-shadow: var(--shadow-raised);
            position: relative;
            text-align: center;
        }

        .camera-outer {
            width: 240px;
            height: 240px;
            margin: 0 auto 24px auto;
            border-radius: 50%;
            padding: 8px;
            background: var(--bg-color);
            box-shadow: var(--shadow-pressed-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .camera-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #111;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.8);
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        .scanner-line {
            position: absolute;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--color-primary), transparent);
            top: 0;
            animation: scan 2s linear infinite;
            z-index: 10;
        }

        .face-overlay {
            position: absolute;
            inset: 15px;
            border: 2px dashed rgba(255,255,255,0.4);
            border-radius: 50%;
            pointer-events: none;
            z-index: 5;
            transition: border-color 0.3s;
        }

        @keyframes scan {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }

        .liveness-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 16px;
            background: var(--bg-color);
            box-shadow: var(--shadow-pressed-sm);
            text-transform: uppercase;
        }

        .liveness-stage {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-primary);
            min-height: 28px;
            margin-bottom: 8px;
        }

        .liveness-tip {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .btn-fallback {
            margin-top: 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .btn-fallback:hover {
            color: var(--color-primary);
        }
    </style>

    <script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>

<div class="face-container" x-data="faceAuth()">
    <div class="face-card">
        
        <div class="mb-lg">
            <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Face ID</h2>
            <p class="text-muted" style="font-size: 0.9rem; margin-top: 4px;">Sun'iy Intellekt Biometrik Tizimi</p>
        </div>

        <template x-if="isLocked">
            <div>
                <div style="font-size: 3rem; color: var(--color-danger); margin-bottom: 16px;">
                    <i class="bi bi-shield-slash"></i>
                </div>
                <h3 style="color: var(--text-primary);">Tizim Bloklandi</h3>
                <p class="text-muted mb-lg" style="font-size: 0.9rem;">
                    Urinishlar chegarasidan oshganligi sababli biometrika vaqtincha bloklandi.
                </p>
                <div class="liveness-badge" style="color: var(--color-danger);">
                    <i class="bi bi-clock"></i> <span x-text="formatTime(lockSeconds)">15:00</span>
                </div>
                <br>
                <a href="{{ route('finance.pin') }}" class="btn-fallback">
                    <i class="bi bi-arrow-left"></i> Zaxira kod bilan kirish
                </a>
            </div>
        </template>

        <template x-if="!isLocked">
            <div>
                <div class="camera-outer">
                    <div class="camera-inner">
                        <div class="scanner-line" x-show="isScanning"></div>
                        <div class="face-overlay" :style="{ borderColor: overlayColor }"></div>
                        <video x-ref="video" autoplay playsinline muted></video>
                    </div>
                </div>

                <div class="liveness-badge" :style="{ color: statusColor }">
                    <i class="bi" :class="statusIcon"></i>
                    <span x-text="statusText">Model yuklanmoqda...</span>
                </div>

                <div class="liveness-stage" x-text="currentChallengeText"></div>
                <div class="liveness-tip" x-text="tipText">Kamera yuklanmoqda...</div>

                <div x-show="errorMessage" class="skeuo-alert skeuo-alert-danger mb-md" x-text="errorMessage"></div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('finance.pin') }}" class="btn-fallback">
                        <i class="bi bi-arrow-left-circle"></i> PIN kiritishga qaytish
                    </a>
                </div>
            </div>
        </template>

    </div>
</div>

<script>
    function faceAuth() {
        return {
            isLocked: {{ $isLocked ? 'true' : 'false' }},
            lockSeconds: {{ $lockTimer ?? 900 }},
            isScanning: false,
            statusText: 'AI Model yuklanmoqda',
            statusIcon: 'bi-cpu',
            statusColor: 'var(--text-muted)',
            overlayColor: 'rgba(255,255,255,0.4)',
            currentChallengeText: 'Iltimos, kutib turing...',
            tipText: 'Neyron tarmoq modellari tayyorlanmoqda',
            errorMessage: '',
            
            stream: null,
            modelsLoaded: false,
            detectLoopActive: false,
            
            // Dynamic EAR Liveness parameters
            blinkState: 'CALIBRATE', // CALIBRATE -> WAITING_BLINK -> EYE_CLOSED -> VERIFIED
            livenessVerified: false,
            earHistory: [],
            baseEar: 0.25,
            closeThreshold: 0.18,
            openThreshold: 0.23,

            // Scale-invariant relative landmark distances (OLV movement check)
            distanceHistory: [],

            init() {
                if (this.isLocked) {
                    this.startLockCountdown();
                    return;
                }
                this.loadAiModels();
            },

            formatTime(seconds) {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            },

            startLockCountdown() {
                const timer = setInterval(() => {
                    if (this.lockSeconds <= 1) {
                        clearInterval(timer);
                        this.isLocked = false;
                        this.loadAiModels();
                    } else {
                        this.lockSeconds--;
                    }
                }, 1000);
            },

            async loadAiModels() {
                try {
                    this.statusText = 'AI Modellar yuklanmoqda...';
                    const MODEL_URL = '{{ asset("vendor/face-api/models") }}';

                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);

                    this.modelsLoaded = true;
                    this.startCamera();
                } catch (err) {
                    console.error('FaceAPI load error:', err);
                    this.statusText = 'AI Model Xatoligi';
                    this.statusColor = 'var(--color-danger)';
                    this.errorMessage = 'Sun\'iy intellekt modellarini yuklab bo\'lmadi.';
                }
            },

            async startCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 320, height: 320, facingMode: 'user' }
                    });
                    this.$refs.video.srcObject = this.stream;
                    this.isScanning = true;
                    this.statusText = 'Kamera faol. Yuzni ko\'rsating.';
                    this.statusColor = 'var(--color-primary)';
                    this.statusIcon = 'bi-camera-video';
                    this.currentChallengeText = 'Kalibratsiya qilinmoqda';
                    this.tipText = 'Kameraga to\'g\'ri qarab turing.';
                    
                    this.detectLoopActive = true;
                    this.runFaceDetectionLoop();

                } catch (err) {
                    this.statusText = 'Kamera Xatosi';
                    this.statusColor = 'var(--color-danger)';
                    this.statusIcon = 'bi-exclamation-triangle';
                    this.currentChallengeText = 'Kamera topilmadi';
                    this.tipText = 'Biometrik tekshiruv uchun kameraga kirishga ruxsat bering.';
                    this.errorMessage = 'Kamera ruxsati berilmadi yoki drayver ishlamayapti.';
                }
            },

            calculateEar(eye) {
                const p1 = eye[0], p2 = eye[1], p3 = eye[2], p4 = eye[3], p5 = eye[4], p6 = eye[5];
                const distV1 = Math.hypot(p2.x - p6.x, p2.y - p6.y);
                const distV2 = Math.hypot(p3.x - p5.x, p3.y - p5.y);
                const distH = Math.hypot(p1.x - p4.x, p1.y - p4.y);
                if (distH === 0) return 0;
                return (distV1 + distV2) / (2.0 * distH);
            },

            async runFaceDetectionLoop() {
                const videoEl = this.$refs.video;
                const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
                const collectedDescriptors = [];

                while (this.detectLoopActive) {
                    if (!videoEl || videoEl.paused || videoEl.ended) {
                        await new Promise(r => setTimeout(r, 200));
                        continue;
                    }

                    try {
                        const detection = await faceapi.detectSingleFace(videoEl, options)
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        if (!detection) {
                            this.overlayColor = 'rgba(255, 255, 255, 0.4)';
                            this.statusText = 'Yuz qidirilmoqda...';
                            this.statusColor = 'var(--text-muted)';
                            await new Promise(r => setTimeout(r, 150));
                            continue;
                        }

                        this.overlayColor = 'var(--color-primary)';
                        const landmarks = detection.landmarks;
                        const leftEye = landmarks.getLeftEye();
                        const rightEye = landmarks.getRightEye();

                        const earLeft = this.calculateEar(leftEye);
                        const earRight = this.calculateEar(rightEye);
                        const avgEar = (earLeft + earRight) / 2.0;

                        // Organic micro-movement tracking (scale-invariant relative landmark distances)
                        const noseTip = landmarks.positions[30];
                        const leftEyeCorner = landmarks.positions[36];
                        const rawDist = Math.hypot(noseTip.x - leftEyeCorner.x, noseTip.y - leftEyeCorner.y);
                        const boxWidth = detection.detection.box.width;
                        const relDist = rawDist / (boxWidth || 1);
                        this.distanceHistory.push(relDist);

                        if (this.distanceHistory.length > 30) {
                            this.distanceHistory.shift();
                        }

                        // Liveness check logic
                        if (!this.livenessVerified) {
                            // Method A: Dynamic Calibrated Eye Blink Detection
                            if (this.blinkState === 'CALIBRATE') {
                                this.earHistory.push(avgEar);
                                this.currentChallengeText = 'Kameraga to\'g\'ri qarab turing...';
                                this.statusText = `Kalibratsiya qilinmoqda (${this.earHistory.length}/5)`;
                                
                                if (this.earHistory.length >= 5) {
                                    const sum = this.earHistory.reduce((a, b) => a + b, 0);
                                    this.baseEar = sum / this.earHistory.length;
                                    
                                    // Safety fallback bounds
                                    if (this.baseEar < 0.16 || this.baseEar > 0.40) {
                                        this.baseEar = 0.25;
                                    }
                                    
                                    this.closeThreshold = this.baseEar * 0.80; // 20% relative drop
                                    this.openThreshold = this.baseEar * 0.90;  // 10% relative drop
                                    this.blinkState = 'WAITING_BLINK';
                                }
                            } else if (this.blinkState === 'WAITING_BLINK') {
                                this.currentChallengeText = 'Ko\'zlaringizni qisib-oching!';
                                this.statusText = 'Tiriklik testi boshlandi...';
                                this.tipText = 'Ko\'z qisishingiz kutilmoqda.';
                                
                                if (avgEar <= this.closeThreshold) {
                                    this.blinkState = 'EYE_CLOSED';
                                }
                            } else if (this.blinkState === 'EYE_CLOSED') {
                                if (avgEar >= this.openThreshold) {
                                    this.livenessVerified = true;
                                    this.blinkState = 'VERIFIED';
                                }
                            }

                            // Method B: Parallel Organic Micro-Movement Check (OLV)
                            // Runs after 15 frames to check standard deviation of relative distances
                            if (!this.livenessVerified && this.distanceHistory.length >= 15) {
                                const mean = this.distanceHistory.reduce((a, b) => a + b, 0) / this.distanceHistory.length;
                                const variance = this.distanceHistory.reduce((sum, val) => sum + Math.pow(val - mean, 2), 0) / this.distanceHistory.length;
                                const stdDev = Math.sqrt(variance);

                                // If standard deviation shows healthy human micro-movement/tremor
                                if (stdDev > 0.0008 && stdDev < 0.05) {
                                    this.livenessVerified = true;
                                    this.blinkState = 'VERIFIED';
                                }
                            }

                            if (this.livenessVerified) {
                                this.statusText = 'Tiriklik testi o\'tdi!';
                                this.statusColor = 'var(--color-primary)';
                                this.currentChallengeText = 'Solishtirilmoqda...';
                            }
                        }

                        // Collect descriptors once liveness is verified
                        if (this.livenessVerified) {
                            collectedDescriptors.push(Array.from(detection.descriptor));
                            if (collectedDescriptors.length >= 2) {
                                this.detectLoopActive = false;
                                
                                const finalDescriptor = new Array(128).fill(0);
                                for (let i = 0; i < 128; i++) {
                                    let sum = 0;
                                    for (let j = 0; j < collectedDescriptors.length; j++) {
                                        sum += collectedDescriptors[j][i];
                                    }
                                    finalDescriptor[i] = parseFloat((sum / collectedDescriptors.length).toFixed(6));
                                }

                                await this.submitAuth(finalDescriptor);
                                break;
                            }
                        }

                    } catch (e) {
                        console.error('Detection frame error:', e);
                    }

                    await new Promise(r => setTimeout(r, 100));
                }
            },

            async submitAuth(descriptorVector) {
                try {
                    this.statusText = 'Serverda solishtirilmoqda...';
                    this.statusColor = 'var(--color-primary)';

                    const response = await fetch('{{ route("finance.face.verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            embedding: JSON.stringify(descriptorVector),
                            liveness_verified: this.livenessVerified
                        })
                    });

                    const res = await response.json();

                    if (response.ok && res.success) {
                        this.statusText = 'Muvaffaqiyatli';
                        this.statusColor = 'var(--color-primary)';
                        this.currentChallengeText = 'Yuz tasdiqlandi!';
                        this.tipText = `O'xshashlik: ${Math.round(res.similarity * 100)}%. Yo'naltirilmoqda...`;
                        
                        if (this.stream) {
                            this.stream.getTracks().forEach(track => track.stop());
                        }

                        setTimeout(() => {
                            window.location.href = res.redirect_url;
                        }, 800);
                    } else {
                        throw res;
                    }

                } catch (err) {
                    this.errorMessage = err.message || 'Face ID tekshiruvi muvaffaqiyatsiz bo\'ldi.';
                    this.statusText = 'Rad etildi';
                    this.statusColor = 'var(--color-danger)';
                    this.currentChallengeText = 'Tanish Rad Etildi';
                    
                    if (err.lockout) {
                        this.isLocked = true;
                        this.lockSeconds = 900;
                        this.startLockCountdown();
                    } else {
                        setTimeout(() => {
                            this.errorMessage = '';
                            this.livenessVerified = false;
                            this.blinkState = 'CALIBRATE';
                            this.earHistory = [];
                            this.distanceHistory = [];
                            this.detectLoopActive = true;
                            this.runFaceDetectionLoop();
                        }, 3000);
                    }
                }
            }
        };
    }
</script>
</body>
</html>
