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
        [x-cloak] { display: none !important; }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    {{-- ✅ Alpine store dùng chung cho ví Coin — mọi component (topbar, chatbot, trang tài liệu...)
         đều đọc/ghi cùng một nguồn số dư để luôn đồng bộ. --}}
    @auth
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('wallet', {
                balance: null,
                options: null,
                modalOpen: false,
                modalType: null, // 'token' | 'download'
                purchasing: false,
                error: null,

                async init() {
                    await this.fetchBalance();
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                },

                async fetchBalance() {
                    try {
                        const res = await fetch('/api/wallet/balance', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data?.success) this.balance = data.balance;
                    } catch (e) {
                        console.warn('Không thể tải số dư coin:', e);
                    }
                },

                async fetchOptions() {
                    try {
                        const res = await fetch('/api/wallet/purchase-options', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data?.success) {
                            this.options = data;
                            this.balance = data.balance;
                        }
                    } catch (e) {
                        console.warn('Không thể tải tỷ lệ quy đổi coin:', e);
                    }
                },

                async openModal(type) {
                    this.modalType = type;
                    this.error = null;
                    this.modalOpen = true;
                    if (!this.options) await this.fetchOptions();
                },

                closeModal() {
                    this.modalOpen = false;
                    this.error = null;
                },

                /**
                 * Thực hiện mua gói token/lượt tải bằng coin.
                 * Trả về dữ liệu phản hồi server (token_limit hoặc download_limit mới)
                 * để component gọi (chatbot, trang tài liệu...) tự cập nhật UI cục bộ.
                 */
                async purchase(type = null) {
                    const targetType = type ?? this.modalType;
                    const url = targetType === 'token' ? '/api/wallet/buy-token' : '/api/wallet/buy-download';

                    this.purchasing = true;
                    this.error = null;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken(),
                            },
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Giao dịch thất bại, vui lòng thử lại.');
                        }
                        this.balance = data.balance;
                        this.modalOpen = false;
                        window.dispatchEvent(new CustomEvent('wallet:purchased', { detail: data }));
                        if (typeof showToast === 'function') showToast(data.message, 'success');
                        return data;
                    } catch (e) {
                        this.error = e.message;
                        if (typeof showToast === 'function') showToast(e.message, 'error');
                        throw e;
                    } finally {
                        this.purchasing = false;
                    }
                },
            });
        });
    </script>
    @endauth

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
        @php
            $userLog = auth()->check() 
                ? \App\Models\UserLog::where('user_id', auth()->id())->first() 
                : null;
            $tokenLimit = $userLog?->token_limit ?? 0;
        @endphp
        @include('layouts.chatbot')

    </div>{{-- end x-data --}}

    {{-- ✅ MODAL TOÀN CỤC: Đổi Coin lấy Token / Lượt tải — dùng chung cho mọi trang --}}
    @auth
    <div x-data x-show="$store.wallet.modalOpen" x-transition x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$store.wallet.closeModal()"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm p-7 space-y-5">

            <template x-if="$store.wallet.modalType === 'token'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i data-lucide="zap" class="w-5 h-5 text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">Đổi Coin lấy Token chat</h2>
                        <p class="text-xs text-slate-400">Hết token chat? Dùng coin để nạp thêm ngay.</p>
                    </div>
                </div>
            </template>
            <template x-if="$store.wallet.modalType === 'download'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
                        <i data-lucide="download" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900">Đổi Coin lấy lượt tải</h2>
                        <p class="text-xs text-slate-400">Hết lượt tải tài liệu? Dùng coin để mua thêm.</p>
                    </div>
                </div>
            </template>

            {{-- Loading options --}}
            <div x-show="!$store.wallet.options" class="py-4 text-center text-xs text-slate-400">
                Đang tải tỷ lệ quy đổi...
            </div>

            <template x-if="$store.wallet.options">
                <div class="space-y-4">
                    {{-- Preview quy đổi --}}
                    <div class="rounded-2xl border p-4 flex items-center justify-between"
                        :class="$store.wallet.modalType === 'token' ? 'border-indigo-100 bg-indigo-50/50' : 'border-amber-100 bg-amber-50/50'">
                        <div class="text-center flex-1">
                            <p class="text-2xl font-black text-slate-900"
                               x-text="($store.wallet.modalType === 'token' ? $store.wallet.options.token.coin_cost : $store.wallet.options.download.coin_cost)"></p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Coin</p>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <div class="text-center flex-1">
                            <p class="text-2xl font-black"
                               :class="$store.wallet.modalType === 'token' ? 'text-indigo-600' : 'text-amber-600'"
                               x-text="($store.wallet.modalType === 'token' ? $store.wallet.options.token.amount : $store.wallet.options.download.amount)"></p>
                            <p class="text-[10px] text-slate-500 mt-0.5" x-text="$store.wallet.modalType === 'token' ? 'Token' : 'Lượt tải'"></p>
                        </div>
                    </div>

                    {{-- Số dư hiện tại --}}
                    <div class="flex items-center justify-between text-xs px-1">
                        <span class="text-slate-400">Số dư hiện tại</span>
                        <span class="font-bold text-slate-700 flex items-center gap-1">
                            <i data-lucide="coins" class="w-3.5 h-3.5 text-yellow-500"></i>
                            <span x-text="($store.wallet.balance ?? 0).toLocaleString('vi-VN')"></span>
                        </span>
                    </div>

                    {{-- Lỗi --}}
                    <p x-show="$store.wallet.error" x-text="$store.wallet.error"
                        class="text-xs text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2"></p>

                    <div class="flex gap-3 pt-1">
                        <button @click="$store.wallet.closeModal()"
                            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                            Hủy
                        </button>
                        <button @click="$store.wallet.purchase()"
                            :disabled="$store.wallet.purchasing"
                            class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                            <svg x-show="$store.wallet.purchasing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span x-show="!$store.wallet.purchasing">Xác nhận đổi</span>
                            <span x-show="$store.wallet.purchasing">Đang xử lý...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
    @endauth

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