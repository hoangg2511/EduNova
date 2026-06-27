<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduNova - Quản lý Học tập')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        primary: { 50:'#eff6ff', 100:'#dbeafe', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        h1,h2,h3,h4,h5,h6 { font-family: 'Space Grotesk', sans-serif; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-panel { background-color: rgba(255,255,255,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .frosted-card { background-color:#ffffff; border:1px solid #e2e8f0; border-radius:24px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.03),0 2px 4px -1px rgba(0,0,0,0.015); transition:all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .frosted-card:hover { box-shadow:0 12px 20px -3px rgba(99,102,241,0.08),0 4px 6px -2px rgba(99,102,241,0.03); transform:translateY(-2px); }
        .bg-blur-circles::before { content:''; position:fixed; top:-10%; left:-10%; width:40%; height:40%; background:radial-gradient(circle,#c7d2fe 0%,transparent 70%); border-radius:50%; filter:blur(120px); z-index:-10; pointer-events:none; }
        .bg-blur-circles::after { content:''; position:fixed; bottom:-10%; right:-10%; width:50%; height:50%; background:radial-gradient(circle,#ddd6fe 0%,transparent 70%); border-radius:50%; filter:blur(150px); z-index:-10; pointer-events:none; }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen bg-blur-circles">

    <div  x-data="{ 
        sidebarOpen: true, 
        chatOpen: localStorage.getItem('chatOpen') === 'true',
        init() {
            // Lưu chatOpen vào localStorage mỗi khi nó thay đổi
            this.$watch('chatOpen', val => localStorage.setItem('chatOpen', val));
        }
        }" x-init="lucide.createIcons(); init()" class="flex min-h-screen overflow-hidden">
        
        {{-- Sidebar --}}
        @include('layouts.sidebar')

        {{-- Main Content: thu hẹp sang trái khi chat mở --}}
        <div
            :class="{
                'pl-64': sidebarOpen,
                'pl-24': !sidebarOpen,
                'pr-[420px]': chatOpen
            }"
            class="flex-1 flex flex-col min-w-0 transition-all duration-300">

            {{-- Topbar --}}
            @include('layouts.topbar')
            
            {{-- Main Content --}}
            <main class="flex-1 p-6 md:p-10 overflow-y-auto">
                @if (session('success'))
                    <div class="p-4 rounded-xl mb-6 flex items-center gap-3 bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-4 rounded-xl mb-6 flex items-center gap-3 bg-red-50 text-red-800 border border-red-200">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="p-4 rounded-xl mb-6 flex items-center gap-3 bg-amber-50 text-amber-800 border border-amber-200">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if (session('info'))
                    <div class="p-4 rounded-xl mb-6 flex items-center gap-3 bg-blue-50 text-blue-800 border border-blue-200">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        {{-- ✅ Chatbot nằm TRONG cùng x-data wrapper --}}
        @include('layouts.chatbot')

    </div>{{-- end x-data --}}

    <script>
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
        document.addEventListener('alpine:initialized', () => lucide.createIcons());

        // Đóng chat từ inner scope
        document.addEventListener('close-chat', () => {
            const wrapper = document.querySelector('[x-data]');
            if (wrapper && wrapper._x_dataStack) {
                wrapper._x_dataStack[0].chatOpen = false;
                setTimeout(() => lucide.createIcons(), 50);
            }
        });
    </script>

    <!-- Global Toast -->
    <div id="globalToast" style="display:none;"
        class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2">
        <i id="globalToastIcon" data-lucide="check-circle" class="w-4 h-4"></i>
        <span id="globalToastMessage"></span>
    </div>

    <script>
        function showToast(message, type = 'success', duration = 3000) {
            try {
                const el = document.getElementById('globalToast');
                const icon = document.getElementById('globalToastIcon');
                const msg = document.getElementById('globalToastMessage');
                if (!el || !icon || !msg) return console.warn('Toast element missing');

                // set type classes
                el.classList.remove('bg-emerald-600', 'bg-red-500', 'bg-blue-600', 'bg-amber-600');
                if (type === 'success') el.classList.add('bg-emerald-600');
                else if (type === 'error') el.classList.add('bg-red-500');
                else if (type === 'info') el.classList.add('bg-blue-600');
                else if (type === 'warning') el.classList.add('bg-amber-600');

                // set icon
                const iconName = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-circle' : (type === 'warning' ? 'alert-triangle' : 'info'));
                icon.setAttribute('data-lucide', iconName);
                msg.textContent = message;

                // show
                el.style.display = 'flex';
                lucide.createIcons();

                // hide after duration
                clearTimeout(window._globalToastTimer);
                window._globalToastTimer = setTimeout(() => {
                    el.style.display = 'none';
                }, duration);
            } catch (e) { console.warn('showToast error', e); }
        }
    </script>

    @stack('scripts')
</body>
</html>