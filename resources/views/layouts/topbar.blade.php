<header class="h-14 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center px-5 justify-between z-30 shrink-0">

    {{-- LEFT: Menu + Breadcrumb --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen"
            class="w-[34px] h-[34px] rounded-xl border border-slate-200 flex items-center justify-center
                   text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>

        <!-- <div class="flex items-center gap-1.5 text-sm text-slate-400">
            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
            <span>EduNova</span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-40"></i>
            <span class="text-slate-700 font-medium">{{ $pageTitle ?? 'Dashboard' }}</span>
        </div> -->
    </div>

    {{-- RIGHT: Search + Streak + Plan + Coin + Notif + User --}}
    @auth
    @php $plan = auth()->user()->currentPlan(); @endphp
    <div class="flex items-center gap-2" x-data="{ coinMenuOpen: false }" @click.outside="coinMenuOpen = false">

        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        {{-- Streak --}}
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-amber-50 border border-amber-200
                    rounded-full text-xs font-semibold text-amber-700"
             title="Chuỗi học liên tiếp">
            <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-500"></i>
            <span x-text="(typeof streakDays !== 'undefined' ? streakDays : {{ auth()->user()->streak_days }}) + ' ngày'"></span>
        </div>

        {{-- Plan + Tokens --}}
        <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200
                    rounded-full text-xs text-slate-500">
            <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
            <span class="font-semibold text-slate-700">{{ $plan->name ?? 'Free' }}</span>
            <!-- <span class="text-slate-300">·</span>
            <span>{{ number_format($plan->token_limit ?? 0) }} tokens</span> -->
        </div>

        {{-- ✅ Coin badge --}}
        <div class="relative">
            <button @click="coinMenuOpen = !coinMenuOpen"
                class="flex items-center gap-1.5 px-2.5 py-1.5 bg-yellow-50 border border-yellow-200
                       rounded-full text-xs font-bold text-yellow-700 hover:bg-yellow-100 transition-all active:scale-95"
                title="Số dư Coin">
                <i data-lucide="coins" class="w-3.5 h-3.5 text-yellow-500"></i>
                <template x-if="$store.wallet.balance === null">
                    <span class="inline-block w-6 h-3 rounded bg-yellow-200 animate-pulse"></span>
                </template>
                <template x-if="$store.wallet.balance !== null">
                    <span x-text="$store.wallet.balance.toLocaleString('vi-VN')"></span>
                </template>
                <i data-lucide="chevron-down" class="w-3 h-3 opacity-60"></i>
            </button>

            {{-- Dropdown nhanh: nạp token / lượt tải bằng coin --}}
            <div x-show="coinMenuOpen" x-transition x-cloak
                class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl p-3 z-50">
                <div class="flex items-center justify-between px-1 pb-2 mb-2 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Ví Coin</span>
                    <span class="text-sm font-black text-yellow-600 flex items-center gap-1">
                        <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                      <span x-text="(typeof $store.wallet !== 'undefined' ? $store.wallet.balance : 0).toLocaleString('vi-VN')"></span>
                    </span>
                </div>
                <button @click="coinMenuOpen = false; $store.wallet.openModal('token')"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="zap" class="w-4 h-4 text-indigo-500"></i> Đổi coin lấy token chat
                </button>
                <button @click="coinMenuOpen = false; $store.wallet.openModal('download')"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="download" class="w-4 h-4 text-amber-500"></i> Đổi coin lấy lượt tải
                </button>
                <p class="text-[10px] text-slate-400 px-1 pt-2 mt-1 border-t border-slate-100">
                    Kiếm thêm coin bằng cách upload tài liệu được duyệt.
                </p>
            </div>
        </div>

        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        {{-- Notification --}}
        @include('layouts.notification')

        {{-- Avatar + Name --}}
        <div class="flex items-center gap-2 cursor-pointer group">
            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center
                        font-semibold text-sm group-hover:ring-2 ring-indigo-200 transition-all">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="hidden sm:block">
                <p class="text-xs font-semibold text-slate-700 leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-slate-400 leading-tight">Học viên</p>
            </div>
        </div>

    </div>
    @endauth
</header>