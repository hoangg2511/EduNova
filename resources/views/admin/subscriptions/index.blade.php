@extends('layouts.app')
@section('title', 'Quản lý gói đăng ký - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }

    /* Plan card gradient border */
    .plan-card { position: relative; transition: all .25s; }
    .plan-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(99,102,241,.12); }
    .plan-card.featured::before {
        content: '';
        position: absolute; inset: -2px;
        border-radius: 22px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        z-index: -1;
    }

    /* Toggle switch */
    .toggle-track {
        width: 36px; height: 20px; border-radius: 99px;
        background: #e2e8f0; transition: background .2s; cursor: pointer;
        position: relative;
    }
    .toggle-track.on { background: #6366f1; }
    .toggle-thumb {
        position: absolute; top: 2px; left: 2px;
        width: 16px; height: 16px; border-radius: 50%;
        background: white; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        transition: transform .2s;
    }
    .toggle-track.on .toggle-thumb { transform: translateX(16px); }

    /* Sparkline */
    .spark-bar { display:inline-flex; align-items:flex-end; gap:2px; height:28px; }
    .spark-bar span { width:4px; border-radius:2px; background:currentColor; opacity:.5; transition:opacity .15s; }
    .spark-bar span:last-child { opacity:1; }
</style>
@endpush

@section('content')
<div x-data="subscriptionManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Gói đăng ký</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Quản lý gói đăng ký</h1>
            <p class="text-slate-500 text-sm mt-0.5">Thiết lập gói, theo dõi doanh thu và quản lý subscription</p>
        </div>
        <button @click="openPlanModal()"
            class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm
                   hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Thêm gói mới
        </button>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-for="kpi in kpis" :key="kpi.key">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="`background:${kpi.color}15`">
                        <i :data-lucide="kpi.icon" class="w-4 h-4" :style="`color:${kpi.color}`"></i>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                        :class="kpi.delta >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                        x-text="(kpi.delta>=0?'+':'')+kpi.delta+'%'">
                    </span>
                </div>
                <p class="text-2xl font-black text-slate-900" x-text="kpi.value"></p>
                <p class="text-xs text-slate-500 mt-0.5" x-text="kpi.label"></p>
                <div class="spark-bar mt-2" :style="`color:${kpi.color}`">
                    <template x-for="(v,i) in kpi.spark" :key="i">
                        <span :style="`height:${v}%`"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- TABS --}}
    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-2xl p-1.5 w-fit">
        <template x-for="t in tabs" :key="t.key">
            <button @click="activeTab=t.key"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
                :class="activeTab===t.key ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-700'">
                <i :data-lucide="t.icon" class="w-4 h-4"></i>
                <span x-text="t.label"></span>
            </button>
        </template>
    </div>

    {{-- ═══════════════════════
         TAB: PLANS (setup gói)
    ═══════════════════════ --}}
    <div x-show="activeTab==='plans'" class="space-y-5">

        {{-- Plan cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <template x-for="plan in plans" :key="plan.id">
                <div class="plan-card bg-white rounded-2xl border border-slate-200 p-6 relative"
                    :class="[plan.is_featured ? 'featured' : '', !plan.is_active ? 'opacity-80' : '']">

                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-black text-slate-900" x-text="plan.name"></h3>
                                <span x-show="plan.is_featured"
                                    class="text-[9px] font-black px-2 py-0.5 rounded-full bg-indigo-600 text-white">
                                    ★ NỔI BẬT
                                </span>
                            </div>
                            <p class="text-xs text-slate-500" x-text="'/' + plan.slug"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div x-show="!plan.is_active"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-semibold text-slate-500">
                                <i data-lucide="eye-off" class="w-3 h-3"></i>
                                <span>Đã tắt</span>
                            </div>
                            {{-- Active toggle --}}
                            <div class="toggle-track" :class="plan.is_active ? 'on' : ''"
                                @click="toggleActive(plan)" title="Bật/tắt gói">
                                <div class="toggle-thumb"></div>
                            </div>
                            <button @click="openPlanModal(plan)" class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="mb-4">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-900"
                                x-text="plan.price==0 ? 'Miễn phí' : formatPrice(plan.price)">
                            </span>
                            <span x-show="plan.price > 0" class="text-xs text-slate-400">/tháng</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5"
                            x-text="plan.duration_days > 0 ? `Hiệu lực ${plan.duration_days} ngày` : 'Vĩnh viễn'">
                        </p>
                    </div>

                    {{-- Limits --}}
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="zap" class="w-3 h-3 text-amber-400"></i> AI Token
                            </span>
                            <span class="font-bold text-slate-700"
                                x-text="plan.token_limit==0 ? '∞ Không giới hạn' : plan.token_limit.toLocaleString()">
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="book" class="w-3 h-3 text-indigo-400"></i> Kiến thức
                            </span>
                            <span class="font-bold text-slate-700"
                                x-text="plan.knowledge_limit==0 ? '∞ Không giới hạn' : plan.knowledge_limit + ' bài'">
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="download" class="w-3 h-3 text-emerald-400"></i> Tải xuống
                            </span>
                            <span class="font-bold text-slate-700"
                                x-text="plan.download_limit==0 ? '∞ Không giới hạn' : plan.download_limit + ' file/tháng'">
                            </span>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="border-t border-slate-100 pt-4 mb-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Tính năng</p>
                        <div class="space-y-1.5">
                            <template x-for="feat in plan.features" :key="feat">
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <i data-lucide="check" class="w-3 h-3 text-emerald-500 shrink-0"></i>
                                    <span x-text="feat"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Subscriber count --}}
                    <div class="bg-slate-50 rounded-xl p-3 flex items-center justify-between">
                        <div>
                            <p class="text-lg font-black text-slate-900" x-text="plan.subscriber_count.toLocaleString()"></p>
                            <p class="text-[10px] text-slate-400">học viên hiện tại</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-indigo-600"
                                x-text="plan.price==0 ? '—' : formatRevenue(plan.price * plan.subscriber_count)">
                            </p>
                            <p class="text-[10px] text-slate-400">doanh thu/tháng</p>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        {{-- Revenue breakdown bar --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-base font-black text-slate-900 mb-5">Phân bố doanh thu theo gói</h2>
            <div class="space-y-4">
                <template x-for="plan in plans.filter(p=>p.price>0)" :key="plan.id">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full" :style="`background:${plan.color}`"></div>
                                <span class="text-sm font-bold text-slate-700" x-text="plan.name"></span>
                                <span class="text-xs text-slate-400" x-text="`${plan.subscriber_count} HV`"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-black text-slate-800"
                                    x-text="formatRevenue(plan.price * plan.subscriber_count)">
                                </span>
                                <span class="text-xs text-slate-400"
                                    x-text="revenuePercent(plan) + '%'">
                                </span>
                            </div>
                        </div>
                        <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                :style="`width:${revenuePercent(plan)}%;background:${plan.color}`">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════
         TAB: STATISTICS
    ═══════════════════════ --}}
    <div x-show="activeTab==='stats'" class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Monthly revenue chart --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Doanh thu theo tháng</h2>
                        <p class="text-xs text-slate-400 mt-0.5">6 tháng gần nhất</p>
                    </div>
                    <span class="text-sm font-black text-indigo-600" x-text="formatRevenue(totalRevenue)"></span>
                </div>
                <div class="flex items-end justify-between gap-2" style="height:140px">
                    <template x-for="(bar, i) in revenueChart" :key="i">
                        <div class="flex-1 flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-bold text-slate-400" x-text="bar.label_val"></span>
                            <div class="w-full flex items-end justify-center" style="height:110px">
                                <div class="w-full rounded-t-lg transition-all duration-500"
                                    :style="`height:${bar.pct}%;background:${i===revenueChart.length-1?'#6366f1':'#e0e7ff'}`">
                                </div>
                            </div>
                            <span class="text-[9px] font-semibold text-slate-400" x-text="bar.month"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Plan distribution donut-style --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-black text-slate-900 mb-5">Tỉ lệ học viên theo gói</h2>
                <div class="space-y-3">
                    <template x-for="plan in plans" :key="plan.id">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full" :style="`background:${plan.color}`"></div>
                                    <span class="text-sm font-semibold text-slate-700" x-text="plan.name"></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="font-black text-slate-800" x-text="plan.subscriber_count.toLocaleString() + ' HV'"></span>
                                    <span class="text-slate-400 w-8 text-right" x-text="subscriberPercent(plan) + '%'"></span>
                                </div>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                    :style="`width:${subscriberPercent(plan)}%;background:${plan.color}`">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-slate-900" x-text="totalSubscribers.toLocaleString()"></p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Tổng học viên</p>
                    </div>
                    <div class="bg-indigo-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-indigo-700" x-text="formatRevenue(totalRevenue)"></p>
                        <p class="text-[10px] text-indigo-400 mt-0.5">Doanh thu tháng này</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Churn + renewal stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <template x-for="st in churnStats" :key="st.key">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="`background:${st.color}15`">
                            <i :data-lucide="st.icon" class="w-4 h-4" :style="`color:${st.color}`"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-500" x-text="st.label"></span>
                    </div>
                    <p class="text-2xl font-black text-slate-900" x-text="st.value"></p>
                    <p class="text-[10px] text-slate-400 mt-1" x-text="st.sub"></p>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══════════════════════
         TAB: SUBSCRIPTIONS
    ═══════════════════════ --}}
    <div x-show="activeTab==='subscriptions'" class="space-y-4">

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-48">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="subSearch" type="text" placeholder="Tìm học viên, email..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <select x-model="subFilterPlan" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả gói</option>
                <template x-for="p in plans" :key="p.id">
                    <option :value="p.slug" x-text="p.name"></option>
                </template>
            </select>
            <select x-model="subFilterStatus" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="expired">Đã hết hạn</option>
                <option value="cancelled">Đã hủy</option>
            </select>
            <select x-model="subFilterMethod" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả thanh toán</option>
                <option value="sepay">SePay</option>
                <option value="bank">Chuyển khoản</option>
                <option value="admin">Admin cấp</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Học viên</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Gói</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Bắt đầu</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Hết hạn</th>
                            <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                            <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Thanh toán</th>
                            <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Giao dịch</th>
                            <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="sub in filteredSubscriptions" :key="sub.id">
                            <tr class="tbl-row border-b border-slate-50">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black text-white shrink-0"
                                            :style="`background:${sub.color}`" x-text="sub.user_name[0]">
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800" x-text="sub.user_name"></p>
                                            <p class="text-[10px] text-slate-400" x-text="sub.user_email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                        :style="`background:${planColor(sub.plan_slug)}15;color:${planColor(sub.plan_slug)}`"
                                        x-text="planName(sub.plan_slug)">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600" x-text="sub.starts_at"></td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs" :class="isExpiringSoon(sub) ? 'text-amber-600 font-bold' : 'text-slate-600'"
                                        x-text="sub.ends_at || '—'">
                                    </span>
                                    <span x-show="isExpiringSoon(sub)"
                                        class="ml-1 text-[9px] font-bold bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full">
                                        Sắp hết
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                        :class="{
                                            'bg-emerald-50 text-emerald-600': sub.status==='active',
                                            'bg-slate-100 text-slate-500':   sub.status==='expired',
                                            'bg-rose-50 text-rose-600':      sub.status==='cancelled',
                                        }"
                                        x-text="{'active':'Hoạt động','expired':'Hết hạn','cancelled':'Đã hủy'}[sub.status]">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"
                                        x-text="{'sepay':'SePay','bank':'Bank','admin':'Admin'}[sub.payment_method]">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="text-[10px] font-mono text-slate-400" x-text="sub.transaction_id || '—'"></span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openGrantModal(sub)"
                                            class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all" title="Gia hạn / đổi gói">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        </button>
                                        <button @click="cancelSub(sub)"
                                            class="p-1.5 rounded-lg hover:bg-rose-50 transition-all" title="Hủy subscription">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-500"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredSubscriptions.length===0">
                            <td colspan="8" class="py-14 text-center">
                                <i data-lucide="inbox" class="w-10 h-10 text-slate-200 mx-auto mb-3"></i>
                                <p class="text-sm text-slate-400">Không có subscription nào</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400"
                    x-text="`Hiển thị ${filteredSubscriptions.length} / ${subscriptions.length} subscription`">
                </span>
                <button class="text-xs font-semibold text-indigo-600 hover:underline" @click="openGrantModal()">
                    + Cấp gói thủ công
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════
         MODAL: Thêm / Chỉnh sửa gói
    ═══════════════════════════════ --}}
    <div x-show="planModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="planModalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 space-y-5 overflow-y-auto max-h-[90vh]">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900"
                    x-text="editingPlan ? 'Chỉnh sửa gói: ' + editingPlan.name : 'Thêm gói mới'">
                </h2>
                <button @click="planModalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            {{-- Color accent bar --}}
            <div class="h-1 rounded-full" :style="`background:${planForm.color || '#6366f1'}`"></div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tên gói <span class="text-rose-500">*</span></label>
                    <input x-model="planForm.name" type="text" placeholder="VD: Pro"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Slug <span class="text-rose-500">*</span></label>
                    <input x-model="planForm.slug" type="text" placeholder="pro"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Giá (VNĐ)</label>
                    <input x-model.number="planForm.price" type="number" min="0" placeholder="0 = Miễn phí"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Thời hạn (ngày)</label>
                    <input x-model.number="planForm.duration_days" type="number" min="0" placeholder="0 = Vĩnh viễn"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">AI Token limit</label>
                    <input x-model.number="planForm.token_limit" type="number" min="0" placeholder="0 = ∞"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <p class="text-[10px] text-slate-400 mt-1">0 = không giới hạn</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Knowledge limit</label>
                    <input x-model.number="planForm.knowledge_limit" type="number" min="0" placeholder="0 = ∞"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <p class="text-[10px] text-slate-400 mt-1">Số bài kiến thức</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Download limit</label>
                    <input x-model.number="planForm.download_limit" type="number" min="0" placeholder="0 = ∞"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <p class="text-[10px] text-slate-400 mt-1">File/tháng</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả</label>
                <textarea x-model="planForm.description" rows="2" placeholder="Mô tả ngắn về gói..."
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                </textarea>
            </div>

            {{-- Features --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tính năng</label>
                <div class="space-y-2 mb-2">
                    <template x-for="(feat, i) in planForm.features" :key="i">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                            <input x-model="planForm.features[i]" type="text"
                                class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <button @click="planForm.features.splice(i,1)" class="p-1 rounded-lg hover:bg-rose-50">
                                <i data-lucide="x" class="w-3.5 h-3.5 text-rose-400"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <button @click="planForm.features.push('')"
                    class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Thêm tính năng
                </button>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="planForm.is_featured" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
                    <span class="text-xs font-semibold text-slate-600">Gói nổi bật</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="planForm.is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
                    <span class="text-xs font-semibold text-slate-600">Đang hoạt động</span>
                </label>
                <div class="flex items-center gap-2 ml-auto">
                    <label class="text-xs font-semibold text-slate-600">Màu sắc</label>
                    <input type="color" x-model="planForm.color"
                        class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                </div>
            </div>
            {{-- Áp dụng ngay lập tức cho học viên đang dùng gói --}}
            <div x-show="editingPlan" x-cloak
                class="flex items-center justify-between gap-4 p-3.5 bg-amber-50 border border-amber-200 rounded-xl">
                <div class="flex items-start gap-2.5">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-500 mt-0.5 shrink-0"></i>
                    <div>
                        <p class="text-xs font-bold text-amber-800">Áp dụng ngay lập tức</p>
                        <p class="text-[10px] text-amber-600 mt-0.5">
                            Đồng bộ hạn mức mới cho
                            <span class="font-bold" x-text="(editingPlan?.subscriber_count ?? 0) + ' học viên'"></span>
                            đang dùng gói này. Nếu tắt, thay đổi chỉ áp dụng cho lượt đăng ký/gia hạn kế tiếp.
                        </p>
                    </div>
                </div>
                <div class="toggle-track shrink-0" :class="planForm.apply_now ? 'on' : ''"
                    @click="planForm.apply_now = !planForm.apply_now">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button @click="planModalOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="savePlan()"
                    :disabled="!planForm.name || !planForm.slug"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all">
                    <span x-text="editingPlan ? 'Cập nhật gói' : 'Tạo gói'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════
         MODAL: Cấp / Gia hạn gói
    ═══════════════════════════════ --}}
    <div x-show="grantModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="grantModalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">Cấp / Gia hạn gói</h2>
                <button @click="grantModalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div x-show="grantTarget">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl mb-4">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black text-white"
                        :style="`background:${grantTarget?.color}`" x-text="grantTarget?.user_name?.[0] || '?'">
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800" x-text="grantTarget?.user_name"></p>
                        <p class="text-xs text-slate-400" x-text="grantTarget?.user_email"></p>
                    </div>
                </div>
            </div>

            <div x-show="!grantTarget">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Email học viên</label>
                <input x-model="grantForm.user_email" type="email" placeholder="email@example.com"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Gói đăng ký</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="p in plans.filter(pl=>pl.price>0)" :key="p.id">
                        <button @click="grantForm.plan_slug=p.slug"
                            class="py-2.5 rounded-xl text-xs font-bold border-2 transition-all"
                            :style="grantForm.plan_slug===p.slug
                                ? `background:${p.color};color:#fff;border-color:${p.color}`
                                : `border-color:#e2e8f0;color:#64748b`"
                            x-text="p.name">
                        </button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ngày bắt đầu</label>
                    <input x-model="grantForm.starts_at" type="date"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ngày kết thúc</label>
                    <input x-model="grantForm.ends_at" type="date"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Phương thức</label>
                <select x-model="grantForm.payment_method"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="admin">Admin cấp thủ công</option>
                    <option value="bank">Chuyển khoản ngân hàng</option>
                    <option value="sepay">SePay</option>
                </select>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="grantModalOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="grantSubscription()"
                    class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-all">
                    <i data-lucide="check" class="w-4 h-4 inline-block mr-1"></i> Xác nhận cấp gói
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="{
            'bg-emerald-600': toast.type==='success',
            'bg-rose-600':    toast.type==='error',
            'bg-amber-500':   toast.type==='warning',
        }">
        <i :data-lucide="toast.type==='success'?'check-circle':toast.type==='error'?'x-circle':'alert-triangle'" class="w-4 h-4"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function subscriptionManager() {
    return {
        // ── State ──────────────────────────────────────────────────────────
        activeTab:     'plans',
        planModalOpen: false,
        grantModalOpen:false,
        editingPlan:   null,
        grantTarget:   null,
        subSearch: '', subFilterPlan: '', subFilterStatus: '', subFilterMethod: '',
        toast: { show: false, msg: '', type: 'success' },

        // Server data
        plans:         [],
        subscriptions: [],
        kpis:          [],
        revenueChart:  [],
        churnStats:    [],

        tabs: [
            { key: 'plans',         label: 'Thiết lập gói',     icon: 'layers' },
            { key: 'stats',         label: 'Thống kê',          icon: 'bar-chart-2' },
            { key: 'subscriptions', label: 'Danh sách đăng ký', icon: 'users' },
        ],

        // Form: plan
        planForm: {
            name: '', slug: '', description: '', price: 0, duration_days: 30,
            token_limit: 0, knowledge_limit: 0, download_limit: 0,
            features: [''], is_featured: false, is_active: true, color: '#6366f1',
            apply_now: false,
        },

        // Form: grant
        grantForm: {
            user_email: '', plan_slug: 'pro',
            starts_at: new Date().toISOString().split('T')[0],
            ends_at: '', payment_method: 'admin',
        },

        // ── Computed ───────────────────────────────────────────────────────
        get totalSubscribers() {
            return this.plans.reduce((s, p) => s + (p.subscriber_count ?? 0), 0);
        },
        get totalRevenue() {
            return this.plans.reduce((s, p) => s + p.price * (p.subscriber_count ?? 0), 0);
        },
        get filteredSubscriptions() {
            const q = this.subSearch.toLowerCase();
            return this.subscriptions.filter(s =>
                (!q || s.user_name.toLowerCase().includes(q) || s.user_email.toLowerCase().includes(q))
                && (!this.subFilterPlan   || s.plan_slug       === this.subFilterPlan)
                && (!this.subFilterStatus || s.status          === this.subFilterStatus)
                && (!this.subFilterMethod || s.payment_method  === this.subFilterMethod)
            );
        },

        // ── Init ───────────────────────────────────────────────────────────
        async init() {
            await this.fetchData();
            this.$watch('activeTab', () => this.$nextTick(() => lucide.createIcons()));
        },

        // ── API helper ─────────────────────────────────────────────────────
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },
        async api(url, method = 'GET', body = null) {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
                body: body ? JSON.stringify(body) : null,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message ?? `Lỗi HTTP ${res.status}`);
            return data;
        },

        // ── Fetch all data ─────────────────────────────────────────────────
        // Controller GET /admin/subscriptions/data
        // Trả: { plans, subscriptions, kpis, revenueChart, churnStats }
        async fetchData() {
            try {
                const res = await this.api('/admin/subscriptions/data');
                this.plans         = res.plans         ?? [];
                this.subscriptions = res.subscriptions ?? [];
                this.kpis          = res.kpis          ?? [];
                this.revenueChart  = res.revenueChart  ?? [];
                this.churnStats    = res.churnStats    ?? [];
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Plan modal ─────────────────────────────────────────────────────
        openPlanModal(plan = null) {
            this.editingPlan = plan;
            if (plan) {
                this.planForm = {
                    ...plan,
                    features: [...(plan.features ?? [''])],
                    apply_now: false, // luôn tắt mặc định khi mở modal sửa, admin phải chủ động bật
                };
            } else {
                this.planForm = {
                    name: '', slug: '', description: '', price: 0, duration_days: 30,
                    token_limit: 0, knowledge_limit: 0, download_limit: 0,
                    features: [''], is_featured: false, is_active: true, color: '#6366f1',
                    apply_now: false,
                };
            }
            this.planModalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // ── Save plan (POST hoặc PUT) ───────────────────────────────────────
        // POST /admin/subscriptions/plans
        // PUT  /admin/subscriptions/plans/{id}
        async savePlan() {
            if (!this.planForm.name || !this.planForm.slug) return;
            const payload = {
                ...this.planForm,
                features: this.planForm.features.filter(f => f.trim()),
            };
            try {
                let res;
                if (this.editingPlan) {
                    res = await this.api(`/admin/subscriptions/plans/${this.editingPlan.id}`, 'PUT', payload);
                    const idx = this.plans.findIndex(p => p.id === this.editingPlan.id);
                    if (idx !== -1) this.plans.splice(idx, 1, res.plan);
                } else {
                    res = await this.api('/admin/subscriptions/plans', 'POST', payload);
                    this.plans.push(res.plan);
                }
                this.planModalOpen = false;
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Toggle plan active ─────────────────────────────────────────────
        // PATCH /admin/subscriptions/plans/{id}/toggle-active
        async toggleActive(plan) {
            try {
                const res = await this.api(`/admin/subscriptions/plans/${plan.id}/toggle-active`, 'PATCH');
                plan.is_active = res.is_active;
                this.showToast(res.message, res.is_active ? 'success' : 'warning');
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Grant modal ────────────────────────────────────────────────────
        openGrantModal(sub = null) {
            this.grantTarget = sub;
            this.grantForm = {
                user_email:     sub?.user_email ?? '',
                plan_slug:      sub?.plan_slug  ?? 'pro',
                starts_at:      new Date().toISOString().split('T')[0],
                ends_at:        '',
                payment_method: 'admin',
            };
            this.grantModalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // ── Grant subscription ─────────────────────────────────────────────
        // POST /admin/subscriptions/grant
        async grantSubscription() {
            if (!this.grantForm.plan_slug || !this.grantForm.user_email) return;
            try {
                const res = await this.api('/admin/subscriptions/grant', 'POST', this.grantForm);
                // Cập nhật subscription list
                const idx = this.subscriptions.findIndex(s => s.id === res.subscription?.id);
                if (idx !== -1) this.subscriptions.splice(idx, 1, res.subscription);
                else if (res.subscription) this.subscriptions.unshift(res.subscription);
                // Cập nhật subscriber_count trên plans
                this.plans = res.plans ?? this.plans;
                this.grantModalOpen = false;
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Cancel subscription ────────────────────────────────────────────
        // PATCH /admin/subscriptions/{id}/cancel
        async cancelSub(sub) {
            if (!confirm(`Hủy subscription của "${sub.user_name}"?`)) return;
            try {
                const res = await this.api(`/admin/subscriptions/${sub.id}/cancel`, 'PATCH');
                sub.status = res.status;
                this.plans = res.plans ?? this.plans;
                this.showToast(res.message, 'warning');
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Helpers ────────────────────────────────────────────────────────
        formatPrice(p) {
            return new Intl.NumberFormat('vi-VN').format(p) + 'đ';
        },
        formatRevenue(n) {
            if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M ₫';
            if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K ₫';
            return n + ' ₫';
        },
        revenuePercent(plan) {
            const total = this.totalRevenue;
            return total ? Math.round(plan.price * (plan.subscriber_count ?? 0) / total * 100) : 0;
        },
        subscriberPercent(plan) {
            const total = this.totalSubscribers;
            return total ? Math.round((plan.subscriber_count ?? 0) / total * 100) : 0;
        },
        planName(slug)  { return this.plans.find(p => p.slug === slug)?.name  ?? slug; },
        planColor(slug) { return this.plans.find(p => p.slug === slug)?.color ?? '#64748b'; },

        // ends_at_raw: "2025-06-03" từ controller → JS tính diff
        isExpiringSoon(sub) {
            if (!sub.ends_at_raw || sub.status !== 'active') return false;
            const diff = (new Date(sub.ends_at_raw) - new Date()) / (1000 * 60 * 60 * 24);
            return diff >= 0 && diff <= 5;
        },

        showToast(msg, type = 'success') {
            this.toast = { show: true, msg, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => this.toast.show = false, 2800);
        },
    };
}
</script>
@endpush
@endsection