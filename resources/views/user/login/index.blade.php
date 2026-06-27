@extends('layouts.guest')

@section('title', 'EduNova - Đăng nhập / Đăng ký')

@section('styles')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --indigo-100: #e0e7ff;
            --slate-900: #0f172a;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50:  #f8fafc;
            --red-600:   #dc2626;
            --red-50:    #fef2f2;
            --emerald:   #059669;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #eef2ff;
            overflow: hidden;
        }

        /* ── Animated background circles ── */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .5;
            animation: drift 12s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 520px; height: 520px;
            background: radial-gradient(circle, #c7d2fe 0%, transparent 70%);
            top: -120px; left: -120px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #ddd6fe 0%, transparent 70%);
            bottom: -100px; right: -80px;
            animation-delay: -6s;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.08); }
        }

        /* ── Card ── */
        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 20px 60px rgba(79,70,229,.12), 0 2px 8px rgba(0,0,0,.04);
            animation: fadeUp .5s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Logo ── */
        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 64px; height: 64px;
            background: var(--indigo-600);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            box-shadow: 0 12px 28px rgba(79,70,229,.35);
        }

        .brand-title {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -.5px;
        }
        .brand-sub {
            text-align: center;
            font-size: 13px;
            color: var(--slate-500);
            font-weight: 500;
            margin-top: 4px;
            margin-bottom: 28px;
        }

        /* ── Tabs ── */
        .tabs {
            display: flex;
            background: var(--slate-100);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 24px;
        }
        .tab-btn {
            flex: 1;
            padding: 10px;
            font-size: 13.5px;
            font-weight: 700;
            border: none;
            background: transparent;
            border-radius: 10px;
            cursor: pointer;
            color: var(--slate-500);
            transition: all .22s;
            font-family: inherit;
        }
        .tab-btn.active {
            background: #fff;
            color: var(--indigo-600);
            box-shadow: 0 1px 6px rgba(0,0,0,.09);
        }

        /* ── Form Fields ── */
        .form-group {
            margin-bottom: 14px;
        }
        .field-wrap {
            position: relative;
        }
        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            pointer-events: none;
            width: 16px;
            height: 16px;
        }
        .form-input {
            width: 100%;
            padding: 13px 16px 13px 42px;
            border: 1.5px solid var(--slate-200);
            border-radius: 13px;
            font-size: 13.5px;
            font-weight: 500;
            font-family: inherit;
            background: #fff;
            color: var(--slate-900);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .form-input:focus {
            border-color: var(--indigo-600);
            box-shadow: 0 0 0 3px var(--indigo-100);
        }
        .form-input.is-invalid {
            border-color: var(--red-600);
        }

        /* Collapsible name field */
        .name-field-wrapper {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height .35s cubic-bezier(.22,1,.36,1), opacity .25s;
            margin-bottom: 0;
        }
        .name-field-wrapper.visible {
            max-height: 80px;
            opacity: 1;
            margin-bottom: 14px;
        }

        /* ── Errors ── */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            background: var(--red-50);
            color: var(--red-600);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .field-error {
            font-size: 11px;
            color: var(--red-600);
            font-weight: 600;
            margin-top: 5px;
            padding-left: 4px;
        }

        /* ── Submit Button ── */
        .btn-primary {
            width: 100%;
            padding: 13px 16px;
            background: var(--indigo-600);
            color: #fff;
            border: none;
            border-radius: 13px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(79,70,229,.3);
            transition: background .2s, transform .1s, box-shadow .2s;
            margin-top: 4px;
        }
        .btn-primary:hover { background: var(--indigo-700); box-shadow: 0 8px 24px rgba(79,70,229,.38); }
        .btn-primary:active { transform: scale(.98); }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }

        /* Spinner */
        .spinner {
            width: 16px; height: 16px;
            border: 2.5px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Divider ── */
        .divider {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }
        .divider::before {
            content: '';
            position: absolute;
            inset: 50% 0 auto;
            height: 1px;
            background: var(--slate-200);
        }
        .divider span {
            position: relative;
            background: rgba(255,255,255,.82);
            padding: 0 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--slate-400);
        }

        /* ── Google Button ── */
        .btn-google {
            width: 100%;
            padding: 12px 16px;
            background: #fff;
            border: 1.5px solid var(--slate-200);
            border-radius: 13px;
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            color: var(--slate-900);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .2s, transform .1s;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .btn-google:hover { background: var(--slate-50); }
        .btn-google:active { transform: scale(.98); }
        .btn-google img { width: 18px; height: 18px; }

        /* ── Remember Me ── */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .remember-row input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: var(--indigo-600);
            cursor: pointer;
        }
        .remember-row label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--slate-500);
            cursor: pointer;
            user-select: none;
        }

        /* Arrow icon inline SVG */
        .arrow-icon { flex-shrink: 0; }
    </style>
@endsection

@section('content')

<div class="card">

    {{-- Logo --}}
    <div class="logo-wrap">
        <div class="logo">Σ</div>
    </div>
    <h1 class="brand-title">EduNova</h1>
    <p class="brand-sub">Hệ thống Quản lý Học tập Thông minh</p>

    {{-- Tabs --}}
    @php $mode = session('mode', old('_mode', 'login')); @endphp
    <div class="tabs">
        <button type="button" class="tab-btn {{ $mode !== 'register' ? 'active' : '' }}"
                onclick="switchMode('login')">Đăng nhập</button>
        <button type="button" class="tab-btn {{ $mode === 'register' ? 'active' : '' }}"
                onclick="switchMode('register')">Đăng ký</button>
    </div>

    {{-- Global auth error --}}
    @if ($errors->has('auth'))
        <div class="alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ $errors->first('auth') }}
        </div>
    @endif

    {{-- ── LOGIN FORM ── --}}
    <div id="login-form" style="{{ $mode === 'register' ? 'display:none' : '' }}">
        <form method="POST" action="{{ route('login') }}" id="form-login">
            @csrf
            <input type="hidden" name="_mode" value="login">

            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <input type="email" name="email" placeholder="Email"
                           value="{{ old('email') }}"
                           class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           required autocomplete="email">
                </div>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" name="password" placeholder="Mật khẩu"
                           class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           required autocomplete="current-password">
                </div>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn-primary" id="btn-login">
                <span class="spinner" id="spinner-login"></span>
                <span id="label-login">Đăng nhập</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </button>
        </form>
    </div>

    {{-- ── REGISTER FORM ── --}}
    <div id="register-form" style="{{ $mode !== 'register' ? 'display:none' : '' }}">
        <form method="POST" action="{{ route('register') }}" id="form-register">
            @csrf
            <input type="hidden" name="_mode" value="register">

            {{-- Name field (always visible inside register form) --}}
            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" name="name" placeholder="Họ và tên"
                           value="{{ old('name') }}"
                           class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           required autocomplete="name">
                </div>
                @error('name') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <input type="email" name="email" placeholder="Email"
                           value="{{ old('email') }}"
                           class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           required autocomplete="email">
                </div>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" name="password" placeholder="Mật khẩu"
                           class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           required autocomplete="new-password">
                </div>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <div class="field-wrap">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" name="password_confirmation" placeholder="Xác nhận mật khẩu"
                           class="form-input" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-primary" id="btn-register">
                <span class="spinner" id="spinner-register"></span>
                <span id="label-register">Tạo tài khoản</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </button>
        </form>
    </div>

    {{-- Divider --}}
    <div class="divider"><span>Hoặc</span></div>

    {{-- Google Login --}}
    <button class="btn-google" id="google-login-btn">
        <img src="https://www.google.com/favicon.ico" alt="Google">
        Tiếp tục với Google
    </button>

</div>{{-- /.card --}}

@endsection
@section('scripts')
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js";
    import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js";

    const firebaseConfig = { 
        apiKey: "AIzaSyAE545BFngvwgRH0GSUcFO9k6CbejqKpPY",
        authDomain: "edunova-60ce7.firebaseapp.com",
        projectId: "edunova-60ce7",
        storageBucket: "edunova-60ce7.firebasestorage.app",
        messagingSenderId: "927498046071",
        appId: "1:927498046071:web:a27ba4c1d54d11d7671472",
        measurementId: "G-P9TWJKVPV8" };
    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const provider = new GoogleAuthProvider();

    document.getElementById('google-login-btn').addEventListener('click', async () => {
        try {
            const result = await signInWithPopup(auth, provider);
            const idToken = await result.user.getIdToken();

            const response = await axios.post("{{ route('register.google') }}", {
                idToken: idToken
            }, {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            if (response.data.status === 'success') {
                window.location.href = '/dashboard';
            }
        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đăng nhập thất bại!");
        }
    });
</script>
<script>
    function switchMode(mode) {
        const loginEl    = document.getElementById('login-form');
        const registerEl = document.getElementById('register-form');
        const tabs       = document.querySelectorAll('.tab-btn');

        if (mode === 'login') {
            loginEl.style.display    = '';
            registerEl.style.display = 'none';
            tabs[0].classList.add('active');
            tabs[1].classList.remove('active');
        } else {
            loginEl.style.display    = 'none';
            registerEl.style.display = '';
            tabs[0].classList.remove('active');
            tabs[1].classList.add('active');
        }
    }

    // Loading state on submit
    function setupLoading(formId, spinnerId, labelId, btnId) {
        document.getElementById(formId).addEventListener('submit', function() {
            document.getElementById(spinnerId).style.display = 'block';
            document.getElementById(labelId).textContent = 'Đang xử lý...';
            document.getElementById(btnId).disabled = true;
        });
    }

    setupLoading('form-login',    'spinner-login',    'label-login',    'btn-login');
    setupLoading('form-register', 'spinner-register', 'label-register', 'btn-register');
</script>
@endsection