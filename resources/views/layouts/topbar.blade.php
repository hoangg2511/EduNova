<header class="h-14 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center px-5 justify-between z-30 shrink-0">

    {{-- LEFT: Menu + Breadcrumb --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen"
            class="w-[34px] h-[34px] rounded-xl border border-slate-200 flex items-center justify-center
                   text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>
    </div>

    {{-- RIGHT --}}
    @auth
    @if(auth()->user()->isAdmin())
        {{-- ══════════════════════════════════════
             ADMIN: chỉ hiển thị Avatar + Name
        ══════════════════════════════════════ --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 cursor-pointer group">
                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center
                            font-semibold text-sm group-hover:ring-2 ring-indigo-200 transition-all">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="hidden sm:block">
                    <p class="text-xs font-semibold text-slate-700 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-slate-400 leading-tight">Quản trị viên</p>
                </div>
            </div>
        </div>
    @else
        {{-- ══════════════════════════════════════
             USER THƯỜNG: Streak + Plan + Coin + Notif + User
        ══════════════════════════════════════ --}}
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
            @php $userLog = auth()->user()->userLog; @endphp
            <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200
                        rounded-full text-xs text-slate-500 relative"
                x-data="{ planOpen: false }"
                @click.outside="planOpen = false"
                title="gói đăng ký hiện tại">
                <button type="button" @click.prevent="planOpen = !planOpen"
                    class="flex items-center gap-1.5 text-left">
                    <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
                    <span class="font-semibold text-slate-700">{{ $plan->name ?? 'Free' }}</span>
                    <i data-lucide="chevron-down" class="w-3 h-3 opacity-60"></i>
                </button>
                <div x-show="planOpen" x-transition x-cloak
                    class="absolute right-0 top-full mt-2 w-72 bg-white border border-slate-200 rounded-2xl shadow-xl p-3 z-50">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 font-semibold mb-3">Thông tin gói</div>
                    <div class="space-y-2 text-sm text-slate-700">
                        <div class="flex justify-between">
                            <span>Token AI</span>
                            <span>{{ number_format($userLog?->token_limit ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Bài kiến thức</span>
                            <span>{{ number_format($userLog?->knowledge_limit ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Lượt tải</span>
                            <span>{{ number_format($userLog?->download_limit ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Thời hạn gói</span>
                            <span>{{ $userLog && $userLog->duration_days > 0 ? $userLog->duration_days . ' ngày' : 'Không giới hạn' }}</span>
                        </div>
                    </div>
                </div>
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

        {{-- ✅ MODAL: Đổi Coin lấy Token / Lượt tải — chỉ render cho user thường. --}}
<template x-teleport="body">
    <div x-data   x-init="$watch('$store.wallet.modalOpen', value => { if (value) $nextTick(() => lucide.createIcons()) })" x-show="$store.wallet.modalOpen" x-transition x-cloak
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

            <div x-show="!$store.wallet.options" class="py-4 text-center text-xs text-slate-400">
                Đang tải tỷ lệ quy đổi...
            </div>

            <template x-if="$store.wallet.options">
                <div class="space-y-4">

                    {{-- Bộ chọn số lượng --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Số lượng gói</p>
                        <div class="flex items-center gap-2">
                            <button @click="$store.wallet.decrement()"
                                :disabled="$store.wallet.quantity <= 1"
                                class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center justify-center">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>

                            <input type="number" min="1" :max="$store.wallet.maxQuantity"
                                x-model.number="$store.wallet.quantity"
                                @change="$store.wallet.setQuantity($event.target.value)"
                                class="w-full text-center text-lg font-black text-slate-900 border border-slate-200 rounded-xl py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">

                            <button @click="$store.wallet.increment()"
                                :disabled="$store.wallet.quantity >= $store.wallet.maxQuantity"
                                class="w-10 h-10 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all flex items-center justify-center">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <div class="flex items-center gap-1.5 mt-2.5">
                            <template x-for="n in [1, 3, 5, 10]" :key="n">
                                <button @click="$store.wallet.setQuantity(n)"
                                    :class="$store.wallet.quantity === n
                                        ? (($store.wallet.modalType === 'token') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-amber-500 text-white border-amber-500')
                                        : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50'"
                                    class="flex-1 py-1.5 rounded-lg text-xs font-bold border transition-all"
                                    x-text="'x' + n">
                                </button>
                            </template>
                            <button @click="$store.wallet.setMax()"
                                class="flex-1 py-1.5 rounded-lg text-xs font-bold border border-dashed border-slate-300 text-slate-500 hover:bg-slate-50 transition-all">
                                Tối đa
                            </button>
                        </div>
                    </div>

                    {{-- Preview quy đổi --}}
                    <div class="rounded-2xl border p-4 flex items-center justify-between"
                        :class="$store.wallet.modalType === 'token' ? 'border-indigo-100 bg-indigo-50/50' : 'border-amber-100 bg-amber-50/50'">
                        <div class="text-center flex-1">
                            <p class="text-2xl font-black text-slate-900"
                               x-text="$store.wallet.totalCost().toLocaleString('vi-VN')"></p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Coin</p>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <div class="text-center flex-1">
                            <p class="text-2xl font-black"
                               :class="$store.wallet.modalType === 'token' ? 'text-indigo-600' : 'text-amber-600'"
                               x-text="$store.wallet.totalAmount().toLocaleString('vi-VN')"></p>
                            <p class="text-[10px] text-slate-500 mt-0.5" x-text="$store.wallet.modalType === 'token' ? 'Token' : 'Lượt tải'"></p>
                        </div>
                    </div>

                    {{-- Đơn giá tham khảo --}}
                    <p class="text-[11px] text-slate-400 text-center -mt-2">
                        Đơn giá:
                        <span class="font-semibold text-slate-500" x-text="$store.wallet.unitCost().toLocaleString('vi-VN') + ' coin'"></span>
                        /
                        <span class="font-semibold text-slate-500" x-text="$store.wallet.unitAmount().toLocaleString('vi-VN') + ' ' + ($store.wallet.modalType === 'token' ? 'token' : 'lượt tải')"></span>
                    </p>

                    {{-- Số dư hiện tại --}}
                    <div class="flex items-center justify-between text-xs px-1">
                        <span class="text-slate-400">Số dư hiện tại</span>
                        <span class="font-bold text-slate-700 flex items-center gap-1">
                            <i data-lucide="coins" class="w-3.5 h-3.5 text-yellow-500"></i>
                            <span x-text="($store.wallet.balance ?? 0).toLocaleString('vi-VN')"></span>
                        </span>
                    </div>

                    {{-- Cảnh báo không đủ số dư --}}
                    <p x-show="!$store.wallet.canAfford()"
                        class="text-xs text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                        Số dư không đủ cho số lượng đã chọn. Vui lòng giảm số lượng hoặc nạp thêm Coin.
                    </p>

                    {{-- Lỗi từ server --}}
                    <p x-show="$store.wallet.error" x-text="$store.wallet.error"
                        class="text-xs text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2"></p>

                    <div class="flex gap-3 pt-1">
                        <button @click="$store.wallet.closeModal()"
                            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                            Hủy
                        </button>
                        <button @click="$store.wallet.purchase()"
                            :disabled="$store.wallet.purchasing || !$store.wallet.canAfford()"
                            class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                            <svg x-show="$store.wallet.purchasing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span x-show="!$store.wallet.purchasing">
                                Xác nhận đổi <span x-text="$store.wallet.quantity > 1 ? ('(x' + $store.wallet.quantity + ')') : ''"></span>
                            </span>
                            <span x-show="$store.wallet.purchasing">Đang xử lý...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
    @endif
    @endauth
</header>