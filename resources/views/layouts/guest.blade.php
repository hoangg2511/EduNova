<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduNova - Đăng nhập')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --slate-900: #0f172a;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --red-600: #dc2626;
            --red-50: #fef2f2;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #eef2ff;
            overflow: hidden;
        }

        /* Animated background circles */
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
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, #c7d2fe 0%, transparent 70%);
            top: -120px;
            left: -120px;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #ddd6fe 0%, transparent 70%);
            bottom: -100px;
            right: -80px;
            animation-delay: -6s;
        }

        @keyframes drift {
            from {
                transform: translate(0, 0) scale(1);
            }
            to {
                transform: translate(40px, 30px) scale(1.08);
            }
        }

        /* Content wrapper */
        .guest-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }
    </style>

    @yield('styles')
</head>
<body>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js" defer></script>

    <div class="guest-container">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
