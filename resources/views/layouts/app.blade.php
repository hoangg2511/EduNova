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

    {{-- ✅ Alpine store dùng chung cho ví Coin — chỉ cần cho user thường (không phải admin),
         vì Coin/Modal chỉ hiển thị trong topbar với user thường. --}}
    @auth
        @unless(auth()->user()->isAdmin())
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('wallet', {
                    balance: null,
                    options: null,
                    modalOpen: false,
                    modalType: null, // 'token' | 'download'
                    purchasing: false,
                    error: null,
                    quantity: 1,
                    maxQuantity: 20,

                    async init() {
                        await this.fetchBalance();
                    },

                    csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                    },

                    async fetchBalance() {
                        try {
                            const res = await fetch('/user/wallet/balance', { headers: { 'Accept': 'application/json' } });
                            const data = await res.json();
                            if (data?.success) this.balance = data.balance;
                        } catch (e) {
                            console.warn('Không thể tải số dư coin:', e);
                        }
                    },

                    async fetchOptions() {
                        try {
                            const res = await fetch('/user/wallet/purchase-options', { headers: { 'Accept': 'application/json' } });
                            const data = await res.json();
                            if (data?.success) {
                                this.options = data;
                                this.balance = data.balance;

                                // ✅ Đợi Alpine render xong khối x-if (giờ mới thực sự có options) rồi mới vẽ icon
                                Alpine.nextTick(() => {
                                    if (window.lucide) lucide.createIcons();
                                });
                            }
                        } catch (e) {
                            console.warn('Không thể tải tỷ lệ quy đổi coin:', e);
                        }
                    },

                    async openModal(type) {
                        this.modalType = type;
                        this.error = null;
                        this.quantity = 1;
                        this.modalOpen = true;
                        if (!this.options) await this.fetchOptions();
                    },

                    closeModal() {
                        this.modalOpen = false;
                        this.error = null;
                    },

                    currentOption() {
                        if (!this.options) return null;
                        return this.modalType === 'token' ? this.options.token : this.options.download;
                    },
                    unitCost() {
                        return this.currentOption()?.coin_cost ?? 0;
                    },
                    unitAmount() {
                        return this.currentOption()?.amount ?? 0;
                    },
                    totalCost() {
                        return this.unitCost() * this.quantity;
                    },
                    totalAmount() {
                        return this.unitAmount() * this.quantity;
                    },
                    canAfford() {
                        return this.balance !== null && this.totalCost() <= this.balance;
                    },
                    affordableMax() {
                        const unit = this.unitCost();
                        if (!unit || this.balance === null) return 1;
                        return Math.max(1, Math.min(this.maxQuantity, Math.floor(this.balance / unit)));
                    },

                    increment() {
                        if (this.quantity < this.maxQuantity) this.quantity++;
                    },
                    decrement() {
                        if (this.quantity > 1) this.quantity--;
                    },
                    setQuantity(val) {
                        const n = parseInt(val) || 1;
                        this.quantity = Math.min(Math.max(n, 1), this.maxQuantity);
                    },
                    setMax() {
                        this.quantity = this.affordableMax();
                    },

                    async purchase(type = null) {
                        const targetType = type ?? this.modalType;
                        const url = targetType === 'token' ? '/user/wallet/buy-token' : '/user/wallet/buy-download';

                        if (!this.canAfford()) {
                            this.error = 'Số dư Coin không đủ để thực hiện giao dịch này.';
                            return;
                        }

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
                                body: JSON.stringify({ quantity: this.quantity }),
                            });
                            const data = await res.json();
                            if (!res.ok || !data.success) {
                                throw new Error(data.message || 'Giao dịch thất bại, vui lòng thử lại.');
                            }
                            this.balance = data.balance;
                            this.modalOpen = false;
                            this.quantity = 1;
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
        @endunless
    @endauth

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen bg-blur-circles">

    <div  x-data="{ 
        sidebarOpen: true, 
        chatOpen: localStorage.getItem('chatOpen') === 'true',
        init() {
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

    <script>
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
        document.addEventListener('alpine:initialized', () => lucide.createIcons());

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

                el.classList.remove('bg-emerald-600', 'bg-red-500', 'bg-blue-600', 'bg-amber-600');
                if (type === 'success') el.classList.add('bg-emerald-600');
                else if (type === 'error') el.classList.add('bg-red-500');
                else if (type === 'info') el.classList.add('bg-blue-600');
                else if (type === 'warning') el.classList.add('bg-amber-600');

                const iconName = type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-circle' : (type === 'warning' ? 'alert-triangle' : 'info'));
                icon.setAttribute('data-lucide', iconName);
                msg.textContent = message;

                el.style.display = 'flex';
                lucide.createIcons();

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