@extends('layouts.app')
@section('title', 'Dashboard - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* Sparkline mini chart */
    .spark-bar {
        display: inline-flex;
        align-items: flex-end;
        gap: 2px;
        height: 32px;
    }
    .spark-bar span {
        width: 4px;
        border-radius: 2px;
        background: currentColor;
        opacity: 0.5;
        transition: opacity .15s;
    }
    .spark-bar span:last-child { opacity: 1; }

    /* Progress ring */
    .ring-track { stroke: #e2e8f0; }
    .ring-fill   { stroke-linecap: round; transition: stroke-dashoffset .6s ease; }

    /* Heat-map cells */
    .hm-cell {
        width: 12px; height: 12px;
        border-radius: 3px;
        background: #f1f5f9;
        transition: background .2s;
    }
    .hm-1 { background: #bfdbfe; }
    .hm-2 { background: #93c5fd; }
    .hm-3 { background: #3b82f6; }
    .hm-4 { background: #1d4ed8; }

    /* Roadmap connector */
    .rm-line {
        position: absolute;
        left: 19px; top: 40px;
        width: 2px;
        background: linear-gradient(to bottom, #6366f1 0%, #e2e8f0 100%);
    }

    /* Subtle stat card gradient accent */
    .stat-accent::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(99,102,241,.04) 0%, transparent 60%);
        pointer-events: none;
    }

    /* Table row hover */
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }

    /* Badge pulse for "live" indicator */
    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(16,185,129,.4); }
        100% { box-shadow: 0 0 0 8px rgba(16,185,129,0); }
    }
    .pulse-badge { animation: pulse-ring 1.6s ease infinite; }

    /* Smooth bar chart bars */
    .bar-chart-bar {
        transition: height .5s cubic-bezier(.4,0,.2,1);
        border-radius: 4px 4px 0 0;
    }
</style>
@endpush

@section('content')
<div x-data="adminDash()" x-cloak class="space-y-6">

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400" x-text="todayLabel"></span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Tổng quan hệ thống</h1>
            <p class="text-slate-500 text-sm mt-0.5">Theo dõi hiệu quả học tập và vận hành nền tảng</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-badge"></span>
                <span class="text-xs font-bold text-emerald-700" x-text="onlineUsers + ' đang học'"></span>
            </div>
            <button @click="exportReport()"
                class="flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all active:scale-95">
                <i data-lucide="download" class="w-4 h-4"></i> Xuất báo cáo
            </button>
        </div>
    </div>

    {{-- ── TOP KPI CARDS ── --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-for="kpi in kpis" :key="kpi.key">
            <div class="relative bg-white rounded-2xl border border-slate-200 p-5 overflow-hidden stat-accent hover:shadow-md transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                        :style="`background:${kpi.color}15`">
                        <i :data-lucide="kpi.icon" class="w-5 h-5" :style="`color:${kpi.color}`"></i>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                        :class="kpi.delta >= 0
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'bg-rose-50 text-rose-600'"
                        x-text="(kpi.delta >= 0 ? '+' : '') + kpi.delta + '%'">
                    </span>
                </div>
                <p class="text-2xl font-black text-slate-900" x-text="kpi.value"></p>
                <p class="text-xs text-slate-500 mt-0.5" x-text="kpi.label"></p>
                {{-- Sparkline --}}
                <div class="spark-bar mt-3" :style="`color:${kpi.color}`">
                    <template x-for="(v, i) in kpi.spark" :key="i">
                        <span :style="`height:${v}%`"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- ── ROW 2: MAIN CHART + ROADMAP PROGRESS ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-5">

        {{-- Bar chart: weekly enrollments --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-black text-slate-900">Người đăng ký học</h2>
                    <p class="text-xs text-slate-400 mt-0.5">7 ngày qua • tính theo ngày</p>
                </div>
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <template x-for="tab in ['Tuần','Tháng','Năm']" :key="tab">
                        <button @click="chartTab = tab"
                            :class="chartTab === tab ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all"
                            x-text="tab">
                        </button>
                    </template>
                </div>
            </div>
            {{-- Bar chart --}}
            <div class="flex items-end justify-between gap-2" style="height:160px">
                <template x-for="(bar, i) in chartData" :key="i">
                    <div class="flex-1 flex flex-col items-center gap-1.5">
                        <span class="text-[9px] font-bold text-slate-400" x-text="bar.val"></span>
                        <div class="w-full flex items-end justify-center" style="height:130px">
                            <div class="bar-chart-bar w-full"
                                :style="`height:${bar.pct}%;background:${i === chartData.length-1 ? '#6366f1' : '#e0e7ff'}`">
                            </div>
                        </div>
                        <span class="text-[9px] text-slate-400 font-semibold" x-text="bar.label"></span>
                    </div>
                </template>
            </div>
            {{-- Chart footer --}}
            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-slate-100">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-indigo-500"></span>
                    <span class="text-xs text-slate-500">Hôm nay</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-indigo-100"></span>
                    <span class="text-xs text-slate-500">Các ngày trước</span>
                </div>
                <div class="ml-auto text-xs font-bold text-slate-700">
                    Tổng: <span class="text-indigo-600" x-text="chartData.reduce((s,b)=>s+b.val,0)"></span>
                </div>
            </div>
        </div>

        {{-- Roadmap progress tracker --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-black text-slate-900">Lộ trình hệ thống</h2>
                <span class="text-xs font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">Q2 2025</span>
            </div>
            <div class="flex-1 space-y-0 overflow-y-auto pr-1" style="max-height:220px">
                <template x-for="(rm, i) in roadmap" :key="rm.id">
                    <div class="relative flex gap-3">
                        {{-- connector line --}}
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 z-10"
                                :class="{
                                    'bg-indigo-600 text-white': rm.status === 'done',
                                    'bg-indigo-100 text-indigo-600 border-2 border-indigo-400': rm.status === 'active',
                                    'bg-slate-100 text-slate-400': rm.status === 'todo',
                                }">
                                <i :data-lucide="rm.status === 'done' ? 'check' : rm.status === 'active' ? 'zap' : 'circle'"
                                   class="w-3.5 h-3.5"></i>
                            </div>
                            <div x-show="i < roadmap.length - 1"
                                class="w-0.5 flex-1 my-1 min-h-[24px]"
                                :class="rm.status === 'done' ? 'bg-indigo-300' : 'bg-slate-200'">
                            </div>
                        </div>
                        <div class="pb-4 flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-slate-800 truncate" x-text="rm.title"></p>
                                <span x-show="rm.status === 'active'"
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-600 shrink-0">
                                    Đang làm
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="rm.date"></p>
                            <template x-if="rm.status === 'active'">
                                <div class="mt-2 h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full transition-all"
                                        :style="`width:${rm.progress}%`"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500"
                    x-text="`${roadmap.filter(r=>r.status==='done').length}/${roadmap.length} mốc hoàn thành`">
                </span>
                <div class="flex gap-1">
                    <template x-for="rm in roadmap" :key="rm.id">
                        <div class="w-2 h-2 rounded-full"
                            :class="{
                                'bg-indigo-600': rm.status==='done',
                                'bg-indigo-300': rm.status==='active',
                                'bg-slate-200': rm.status==='todo',
                            }">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ROW 3: COURSE PERFORMANCE + ACTIVITY HEATMAP ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-5">

        {{-- Course table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-black text-slate-900">Hiệu quả khóa học</h2>
                <div class="flex items-center gap-2">
                    <input x-model="courseSearch" type="text" placeholder="Tìm khóa học..."
                        class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300 w-36">
                    <button class="p-1.5 rounded-xl hover:bg-slate-100 transition-all">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-slate-500"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50">
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Khóa học</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">HV</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Hoàn thành</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Đánh giá</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Doanh thu</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Xu hướng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="course in filteredCourses" :key="course.id">
                            <tr class="tbl-row border-b border-slate-50 cursor-pointer" @click="openCourse(course)">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0"
                                            :style="`background:${course.color}15;color:${course.color}`">
                                            <i :data-lucide="course.icon" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 text-xs" x-text="course.name"></p>
                                            <p class="text-[10px] text-slate-400" x-text="course.category"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-700" x-text="course.students.toLocaleString()"></td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <div class="w-14 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full"
                                                :style="`width:${course.completion}%;background:${course.color}`"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-600" x-text="course.completion + '%'"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="text-xs font-bold text-amber-500 flex items-center justify-end gap-0.5">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400"></i>
                                        <span x-text="course.rating"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-700"
                                    x-text="course.revenue + ' ₫'"></td>
                                <td class="px-4 py-3.5">
                                    <div class="spark-bar justify-center" :style="`color:${course.color}`">
                                        <template x-for="(v, si) in course.spark" :key="si">
                                            <span :style="`height:${v}%`"></span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400" x-text="`Hiển thị ${filteredCourses.length}/${courses.length} khóa học`"></span>
                <button class="text-xs font-semibold text-indigo-600 hover:underline">Xem tất cả →</button>
            </div>
        </div>

        {{-- Activity heatmap + rings --}}
        <div class="space-y-4">
            {{-- Completion ring cards --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h2 class="text-sm font-black text-slate-900 mb-4">Tỉ lệ hoàn thành</h2>
                <div class="space-y-3">
                    <template x-for="ring in completionRings" :key="ring.label">
                        <div class="flex items-center gap-3">
                            <svg width="36" height="36" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="14" fill="none" stroke-width="4" class="ring-track"></circle>
                                <circle cx="18" cy="18" r="14" fill="none" stroke-width="4"
                                    :stroke="ring.color"
                                    :stroke-dasharray="`${ring.pct * 0.879} 87.9`"
                                    stroke-dashoffset="21.97"
                                    class="ring-fill">
                                </circle>
                            </svg>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <span class="text-xs font-semibold text-slate-600" x-text="ring.label"></span>
                                    <span class="text-xs font-black" :style="`color:${ring.color}`" x-text="ring.pct + '%'"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="ring.sub"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Activity heatmap --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-black text-slate-900">Hoạt động 4 tuần</h2>
                    <span class="text-[10px] text-slate-400">mỗi ô = 1 ngày</span>
                </div>
                <div class="flex gap-1 overflow-hidden" x-ref="heatmap">
                    <template x-for="(week, wi) in heatmap" :key="wi">
                        <div class="flex flex-col gap-1">
                            <template x-for="(day, di) in week" :key="di">
                                <div class="hm-cell" :class="`hm-${day}`"
                                    :title="`Hoạt động mức ${day}`">
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="flex items-center gap-1.5 mt-3">
                    <span class="text-[10px] text-slate-400">Ít</span>
                    <div class="hm-cell w-3 h-3 rounded"></div>
                    <div class="hm-cell hm-1 w-3 h-3 rounded"></div>
                    <div class="hm-cell hm-2 w-3 h-3 rounded"></div>
                    <div class="hm-cell hm-3 w-3 h-3 rounded"></div>
                    <div class="hm-cell hm-4 w-3 h-3 rounded"></div>
                    <span class="text-[10px] text-slate-400">Nhiều</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ROW 4: RECENT USERS + QUICK SCHEDULE + ALERTS ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        {{-- Recent registrations --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-black text-slate-900">Đăng ký mới</h2>
                <span class="text-[10px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full"
                    x-text="recentUsers.length + ' hôm nay'"></span>
            </div>
            <div class="space-y-3">
                <template x-for="u in recentUsers.slice(0,5)" :key="u.id">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black text-white shrink-0"
                            :style="`background:${u.color}`"
                            x-text="u.name[0]">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate" x-text="u.name"></p>
                            <p class="text-[10px] text-slate-400" x-text="u.course"></p>
                        </div>
                        <span class="text-[10px] text-slate-400 shrink-0" x-text="u.time"></span>
                    </div>
                </template>
            </div>
            <button class="mt-4 w-full py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                Quản lý người dùng →
            </button>
        </div>

        {{-- Quick schedule --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-black text-slate-900">Lịch hôm nay</h2>
                <button class="p-1.5 rounded-lg hover:bg-slate-100">
                    <i data-lucide="calendar-plus" class="w-4 h-4 text-slate-500"></i>
                </button>
            </div>
            <div class="space-y-2">
                <template x-for="ev in todaySchedule" :key="ev.id">
                    <div class="flex gap-3 p-2.5 rounded-xl hover:bg-slate-50 cursor-pointer transition-all group">
                        <div class="w-1 rounded-full shrink-0 self-stretch"
                            :style="`background:${ev.color}`"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800" x-text="ev.title"></p>
                            <p class="text-[10px] text-slate-400 mt-0.5" x-text="ev.time"></p>
                        </div>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-lg self-start shrink-0"
                            :style="`background:${ev.color}15;color:${ev.color}`"
                            x-text="ev.type"></span>
                    </div>
                </template>
                <div x-show="todaySchedule.length === 0" class="text-center py-6">
                    <i data-lucide="calendar-check" class="w-8 h-8 text-slate-200 mx-auto mb-2"></i>
                    <p class="text-xs text-slate-400">Không có lịch hôm nay</p>
                </div>
            </div>
            <button class="mt-3 w-full py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                Xem lịch đầy đủ →
            </button>
        </div>

        {{-- System alerts --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-black text-slate-900">Cảnh báo hệ thống</h2>
                <span x-show="alerts.filter(a=>!a.read).length > 0"
                    class="text-[10px] font-black bg-rose-500 text-white px-2 py-0.5 rounded-full"
                    x-text="alerts.filter(a=>!a.read).length">
                </span>
            </div>
            <div class="space-y-2">
                <template x-for="alert in alerts" :key="alert.id">
                    <div @click="markRead(alert)"
                        class="flex gap-3 p-2.5 rounded-xl cursor-pointer transition-all group"
                        :class="alert.read ? 'opacity-50 hover:opacity-70' : 'hover:bg-slate-50'">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                            :style="`background:${alert.color}15`">
                            <i :data-lucide="alert.icon" class="w-4 h-4" :style="`color:${alert.color}`"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800" x-text="alert.title"></p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-relaxed" x-text="alert.desc"></p>
                        </div>
                        <div x-show="!alert.read" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-1"></div>
                    </div>
                </template>
            </div>
            <button @click="alerts.forEach(a => a.read = true)"
                class="mt-3 w-full py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                Đánh dấu tất cả đã đọc
            </button>
        </div>
    </div>

    {{-- ── ROW 5: SUBSCRIPTION BREAKDOWN ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-5">

        {{-- Plan distribution --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-sm font-black text-slate-900 mb-4">Phân bố gói đăng ký</h2>
            <div class="space-y-3">
                <template x-for="plan in plans" :key="plan.name">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full" :style="`background:${plan.color}`"></div>
                                <span class="text-xs font-semibold text-slate-700" x-text="plan.name"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-slate-800" x-text="plan.count.toLocaleString() + ' HV'"></span>
                                <span class="text-[10px] text-slate-400" x-text="plan.pct + '%'"></span>
                            </div>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                :style="`width:${plan.pct}%;background:${plan.color}`"></div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
                <div class="text-center">
                    <p class="text-xl font-black text-slate-900" x-text="plans.reduce((s,p)=>s+p.count,0).toLocaleString()"></p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tổng học viên</p>
                </div>
                <div class="text-center">
                    <p class="text-xl font-black text-indigo-600">89.2M ₫</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Doanh thu tháng</p>
                </div>
            </div>
        </div>

        {{-- Recent transactions --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-black text-slate-900">Giao dịch gần đây</h2>
                <button class="text-xs font-semibold text-indigo-600 hover:underline">Xem tất cả</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Học viên</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Gói</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Số tiền</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="tx in transactions" :key="tx.id">
                            <tr class="tbl-row border-b border-slate-50">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black text-white shrink-0"
                                            :style="`background:${tx.color}`"
                                            x-text="tx.name[0]">
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800" x-text="tx.name"></p>
                                            <p class="text-[10px] text-slate-400" x-text="tx.email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                        :style="`background:${tx.planColor}15;color:${tx.planColor}`"
                                        x-text="tx.plan">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right text-xs font-black text-slate-800" x-text="tx.amount + ' ₫'"></td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                        :class="{
                                            'bg-emerald-50 text-emerald-600': tx.status === 'success',
                                            'bg-amber-50 text-amber-600':    tx.status === 'pending',
                                            'bg-rose-50 text-rose-600':      tx.status === 'failed',
                                        }"
                                        x-text="{'success':'Thành công','pending':'Chờ xử lý','failed':'Thất bại'}[tx.status]">
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right text-[10px] text-slate-400" x-text="tx.time"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Toast --}}
<div x-show="toast.show" x-transition
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2 bg-slate-900"
    style="display:none">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
    <span x-text="toast.msg"></span>
</div>

@push('scripts')
<script>
function adminDash() {
    return {
        toast: { show: false, msg: '' },
        chartTab: 'Tuần',
        courseSearch: '',
        onlineUsers: 142,
        todayLabel: new Date().toLocaleDateString('vi-VN', { weekday:'long', day:'numeric', month:'long', year:'numeric' }),

        kpis: [
            { key:'users',    icon:'users',         color:'#6366f1', label:'Tổng học viên',    value:'12,847', delta:+8.4,  spark:[30,45,40,60,55,70,85] },
            { key:'courses',  icon:'book-open',     color:'#10b981', label:'Khóa học hoạt động', value:'48',   delta:+2.1,  spark:[60,55,65,70,65,80,75] },
            { key:'revenue',  icon:'trending-up',   color:'#f59e0b', label:'Doanh thu tháng',  value:'89.2M ₫', delta:+15.3, spark:[40,50,45,65,70,60,90] },
            { key:'complete', icon:'award',         color:'#ef4444', label:'Tỉ lệ bỏ học',    value:'7.3%',   delta:-2.1,  spark:[80,70,75,65,60,55,50] },
        ],

        chartData: [
            { label:'T2', val:124, pct:62 },
            { label:'T3', val:98,  pct:49 },
            { label:'T4', val:156, pct:78 },
            { label:'T5', val:112, pct:56 },
            { label:'T6', val:187, pct:93 },
            { label:'T7', val:143, pct:71 },
            { label:'CN', val:200, pct:100 },
        ],

        roadmap: [
            { id:1, title:'Hệ thống Flashcard', date:'Hoàn thành – 12/3', status:'done', progress:100 },
            { id:2, title:'Module Thi & Đề thi', date:'Hoàn thành – 28/3', status:'done', progress:100 },
            { id:3, title:'Thư viện tài liệu (PDF.js)', date:'Hoàn thành – 10/4', status:'done', progress:100 },
            { id:4, title:'Hệ thống Subscription & SePay', date:'Đang thực hiện – 68%', status:'active', progress:68 },
            { id:5, title:'Lịch học thông minh (AI)', date:'Bắt đầu – Q3/2025', status:'todo', progress:0 },
            { id:6, title:'Ứng dụng di động (Flutter)', date:'Kế hoạch – Q4/2025', status:'todo', progress:0 },
        ],

        courses: [
            { id:1, name:'Toán cao cấp A1',       category:'Toán học',     students:2341, completion:73, rating:'4.8', revenue:'24.5M', color:'#6366f1', icon:'calculator',    spark:[40,55,50,70,65,80] },
            { id:2, name:'Lập trình Python căn bản',category:'CNTT',       students:1876, completion:61, rating:'4.9', revenue:'18.2M', color:'#10b981', icon:'code',          spark:[30,45,60,55,70,75] },
            { id:3, name:'Tiếng Anh giao tiếp',   category:'Ngoại ngữ',   students:3210, completion:45, rating:'4.7', revenue:'31.1M', color:'#f59e0b', icon:'message-circle',spark:[60,65,55,70,68,80] },
            { id:4, name:'Vật lý đại cương',       category:'Khoa học',    students:987,  completion:82, rating:'4.6', revenue:'9.8M',  color:'#8b5cf6', icon:'atom',          spark:[70,75,80,78,85,90] },
            { id:5, name:'Kỹ năng thuyết trình',  category:'Kỹ năng mềm', students:1543, completion:55, rating:'4.9', revenue:'15.3M', color:'#ef4444', icon:'mic',           spark:[45,50,55,60,58,70] },
        ],

        completionRings: [
            { label:'Hoàn thành khóa học', pct:73, sub:'8,756 / 12,847 HV', color:'#6366f1' },
            { label:'Đạt điểm đề thi',     pct:61, sub:'Trung bình 7.4/10', color:'#10b981' },
            { label:'Flashcard thành thạo', pct:48, sub:'5,821 từ vựng đạt', color:'#f59e0b' },
        ],

        heatmap: (() => {
            const levels = [0,1,2,3,4];
            return Array.from({length:16}, () =>
                Array.from({length:7}, () => levels[Math.floor(Math.random()*5)])
            );
        })(),

        recentUsers: [
            { id:1, name:'Nguyễn Minh Khoa', course:'Python căn bản',    time:'2 phút',  color:'#6366f1' },
            { id:2, name:'Trần Thị Lan',      course:'Tiếng Anh giao tiếp', time:'5 phút', color:'#10b981' },
            { id:3, name:'Lê Văn Hùng',       course:'Toán cao cấp A1',  time:'12 phút', color:'#f59e0b' },
            { id:4, name:'Phạm Thúy Ngân',    course:'Vật lý đại cương', time:'18 phút', color:'#ef4444' },
            { id:5, name:'Đỗ Quốc Bảo',       course:'Kỹ năng thuyết trình', time:'25 phút', color:'#8b5cf6' },
        ],

        todaySchedule: [
            { id:1, title:'Live class: Python OOP', time:'09:00 – 10:30', type:'Live',    color:'#6366f1' },
            { id:2, title:'Phiên hỏi đáp Toán A1', time:'14:00 – 15:00', type:'Q&A',     color:'#10b981' },
            { id:3, title:'Kiểm tra định kỳ T4',   time:'16:00 – 17:30', type:'Thi',     color:'#ef4444' },
            { id:4, title:'Họp nhóm giảng viên',   time:'19:00 – 20:00', type:'Họp',     color:'#f59e0b' },
        ],

        alerts: [
            { id:1, title:'Server tải cao', desc:'CPU đạt 87% trong 10 phút', icon:'server', color:'#ef4444', read:false },
            { id:2, title:'Gói hết hạn', desc:'23 học viên sắp hết gói Premium', icon:'credit-card', color:'#f59e0b', read:false },
            { id:3, title:'Nội dung mới chờ duyệt', desc:'5 bài học cần xem xét', icon:'file-check', color:'#6366f1', read:false },
            { id:4, title:'Thanh toán thành công', desc:'89 giao dịch hôm nay', icon:'check-circle', color:'#10b981', read:true },
        ],

        plans: [
            { name:'Free',    count:7234, pct:56, color:'#e2e8f0' },
            { name:'Basic',   count:2891, pct:22, color:'#93c5fd' },
            { name:'Premium', count:1956, pct:15, color:'#6366f1' },
            { name:'Team',    count:766,  pct:6,  color:'#1d4ed8' },
            { name:'Enterprise', count:0, pct:1,  color:'#0f172a' },
        ],

        transactions: [
            { id:1, name:'Nguyễn T. Hà',   email:'ha@mail.vn',    plan:'Premium', planColor:'#6366f1', amount:'299,000', status:'success', time:'08:42',  color:'#6366f1' },
            { id:2, name:'Lê Minh Tú',     email:'tu@mail.vn',    plan:'Basic',   planColor:'#3b82f6', amount:'99,000',  status:'success', time:'09:15',  color:'#10b981' },
            { id:3, name:'Phạm Lan Chi',   email:'chi@mail.vn',   plan:'Team',    planColor:'#1d4ed8', amount:'799,000', status:'pending', time:'10:02',  color:'#f59e0b' },
            { id:4, name:'Vũ Quang Huy',   email:'huy@mail.vn',   plan:'Premium', planColor:'#6366f1', amount:'299,000', status:'failed',  time:'10:37',  color:'#ef4444' },
            { id:5, name:'Đặng Thùy Dung', email:'dung@mail.vn',  plan:'Basic',   planColor:'#3b82f6', amount:'99,000',  status:'success', time:'11:20',  color:'#8b5cf6' },
        ],

        get filteredCourses() {
            if (!this.courseSearch) return this.courses;
            return this.courses.filter(c =>
                c.name.toLowerCase().includes(this.courseSearch.toLowerCase()) ||
                c.category.toLowerCase().includes(this.courseSearch.toLowerCase())
            );
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        openCourse(course) {
            this.showToast(`Đang mở: ${course.name}`);
        },
        exportReport() {
            this.showToast('Đang xuất báo cáo PDF...');
        },
        markRead(alert) {
            alert.read = true;
        },
        showToast(msg) {
            this.toast = { show: true, msg };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => this.toast.show = false, 2500);
        },
    };
}
</script>
@endpush
@endsection