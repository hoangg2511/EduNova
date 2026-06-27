@extends('layouts.app')
@section('title', 'Lịch học - EduNova')

@push('styles')
<style>
    .cal-day { min-height: 100px; }
    .cal-day-sm { min-height: 80px; }
    .event-pill {
        display: block; width: 100%; text-align: left;
        padding: 2px 6px; border-radius: 6px; font-size: 11px;
        font-weight: 600; white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; cursor: pointer; transition: opacity .15s;
    }
    .event-pill:hover { opacity: .8; }
    [x-cloak] { display: none !important; }

    /* Smooth month transition */
    .cal-grid { transition: opacity .2s; }
    .cal-grid.fading { opacity: 0; }

    /* Time grid lines */
    .time-row { border-top: 1px solid #f1f5f9; }
    .time-row:nth-child(even) { background: #fafafa; }
</style>
@endpush

@section('content')
<div class="space-y-5" x-data="calendarApp()" x-cloak>

    {{-- ── HEADER ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Lịch học</h1>
            <p class="text-slate-500 text-sm mt-0.5">Quản lý thời gian và lịch trình học tập</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="goToday()"
                class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700
                       hover:bg-slate-50 transition-all">
                Hôm nay
            </button>
            <button @click="openAddEvent = true; resetForm()"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm
                       hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
                <i data-lucide="plus" class="w-4 h-4"></i> Thêm sự kiện
            </button>
        </div>
    </div>

    {{-- ── MINI STATS ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                <i data-lucide="calendar-days" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900" x-text="thisMonthEvents.length"></p>
                <p class="text-xs text-slate-500">Sự kiện tháng này</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900" x-text="todayEvents.length"></p>
                <p class="text-xs text-slate-500">Hôm nay</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center shrink-0">
                <i data-lucide="book-open" class="w-5 h-5 text-violet-600"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900" x-text="events.filter(e=>e.type==='study').length"></p>
                <p class="text-xs text-slate-500">Lịch học</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900" x-text="events.filter(e=>e.type==='deadline').length"></p>
                <p class="text-xs text-slate-500">Deadline</p>
            </div>
        </div>
    </div>

    {{-- ── MAIN LAYOUT: Calendar + Sidebar ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-5">

        {{-- ── LEFT: CALENDAR ── --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

            {{-- Calendar toolbar --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                {{-- Nav --}}
                <div class="flex items-center gap-3">
                    <button @click="prevPeriod()"
                        class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 transition-all">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-slate-600"></i>
                    </button>
                    <h2 class="text-lg font-black text-slate-900 min-w-[180px] text-center"
                        x-text="headerTitle"></h2>
                    <button @click="nextPeriod()"
                        class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 transition-all">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-600"></i>
                    </button>
                </div>

                {{-- View switcher --}}
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <template x-for="v in ['month','week','day']" :key="v">
                        <button @click="setView(v)"
                            :class="viewMode === v ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all capitalize"
                            x-text="v === 'month' ? 'Tháng' : v === 'week' ? 'Tuần' : 'Ngày'">
                        </button>
                    </template>
                </div>
            </div>

            {{-- ══ MONTH VIEW ══ --}}
            <div x-show="viewMode === 'month'" class="p-4">
                {{-- Day headers --}}
                <div class="grid grid-cols-7 mb-2">
                    <template x-for="d in ['CN','T2','T3','T4','T5','T6','T7']" :key="d">
                        <div class="text-center text-xs font-bold text-slate-400 py-2" x-text="d"></div>
                    </template>
                </div>

                {{-- Day cells --}}
                <div class="grid grid-cols-7 gap-1 cal-grid" :class="animating ? 'fading' : ''">
                    <template x-for="cell in monthCells" :key="cell.key">
                        <div @click="selectDay(cell)"
                            class="cal-day rounded-xl p-1.5 cursor-pointer border transition-all"
                            :class="{
                                'opacity-40': !cell.currentMonth,
                                'bg-slate-900 border-slate-900': cell.isToday,
                                'bg-blue-50 border-blue-200': cell.isSelected && !cell.isToday,
                                'border-transparent hover:bg-slate-50 hover:border-slate-200': !cell.isToday && !cell.isSelected,
                                'border-slate-200': cell.isSelected && !cell.isToday,
                            }">
                            {{-- Day number --}}
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full"
                                    :class="{
                                        'text-white': cell.isToday,
                                        'text-slate-400': !cell.currentMonth && !cell.isToday,
                                        'text-slate-800': cell.currentMonth && !cell.isToday,
                                    }"
                                    x-text="cell.day">
                                </span>
                                <span x-show="cell.events.length > 2"
                                    class="text-[9px] font-bold text-slate-400"
                                    x-text="`+${cell.events.length - 2}`">
                                </span>
                            </div>

                            {{-- Events (max 2) --}}
                            <div class="space-y-0.5">
                                <template x-for="ev in cell.events.slice(0,2)" :key="ev.id">
                                    <button @click.stop="openEventDetail(ev)"
                                        class="event-pill"
                                        :style="`background:${typeColor(ev.type)}20;color:${typeColor(ev.type)}`"
                                        x-text="ev.title">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ══ WEEK VIEW ══ --}}
            <div x-show="viewMode === 'week'" class="overflow-x-auto">
                <div class="min-w-[600px]">
                    {{-- Week day headers --}}
                    <div class="grid gap-0" :style="`grid-template-columns: 56px repeat(7, 1fr)`">
                        <div class="p-3 border-b border-slate-100"></div>
                        <template x-for="day in weekDays" :key="day.date">
                            <div class="p-3 border-b border-l border-slate-100 text-center cursor-pointer hover:bg-slate-50"
                                @click="goDayView(day)">
                                <p class="text-xs text-slate-400 font-semibold" x-text="day.label"></p>
                                <div class="mx-auto mt-1 w-8 h-8 rounded-full flex items-center justify-center text-sm font-black"
                                    :class="day.isToday ? 'bg-slate-900 text-white' : 'text-slate-800'"
                                    x-text="day.num">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Time slots --}}
                    <div class="max-h-[520px] overflow-y-auto">
                        <template x-for="hour in hours" :key="hour">
                            <div class="grid time-row" :style="`grid-template-columns: 56px repeat(7, 1fr)`" style="min-height:56px">
                                <div class="px-2 py-1 text-[10px] text-slate-400 font-semibold border-r border-slate-100 shrink-0"
                                    x-text="formatHour(hour)">
                                </div>
                                <template x-for="day in weekDays" :key="day.date">
                                    <div class="border-l border-slate-100 p-0.5 relative"
                                        @click="quickAddOnSlot(day.date, hour)">
                                        <template x-for="ev in getEventsForSlot(day.date, hour)" :key="ev.id">
                                            <button @click.stop="openEventDetail(ev)"
                                                class="event-pill mb-0.5"
                                                :style="`background:${typeColor(ev.type)};color:#fff`"
                                                x-text="ev.title">
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ══ DAY VIEW ══ --}}
            <div x-show="viewMode === 'day'" class="overflow-hidden">
                <div class="grid" style="grid-template-columns: 56px 1fr">
                    <div></div>
                    <div class="p-4 border-b border-slate-100">
                        <p class="text-xs text-slate-400 font-semibold" x-text="['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'][selectedDate.getDay()]"></p>
                        <p class="text-3xl font-black text-slate-900" x-text="selectedDate.getDate()"></p>
                    </div>
                </div>
                <div class="max-h-[520px] overflow-y-auto">
                    <template x-for="hour in hours" :key="hour">
                        <div class="grid time-row" style="grid-template-columns: 56px 1fr; min-height:64px">
                            <div class="px-2 py-1 text-[10px] text-slate-400 font-semibold border-r border-slate-100"
                                x-text="formatHour(hour)">
                            </div>
                            <div class="p-1 space-y-1"
                                @click="quickAddOnSlot(dateStr(selectedDate), hour)">
                                <template x-for="ev in getEventsForSlot(dateStr(selectedDate), hour)" :key="ev.id">
                                    <button @click.stop="openEventDetail(ev)"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition-all hover:opacity-80"
                                        :style="`background:${typeColor(ev.type)}15;border-left:3px solid ${typeColor(ev.type)};color:${typeColor(ev.type)}`">
                                        <p class="font-bold" x-text="ev.title"></p>
                                        <p class="opacity-70 mt-0.5" x-text="`${ev.startTime} – ${ev.endTime}`"></p>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── RIGHT SIDEBAR ── --}}
        <div class="space-y-4">

            {{-- Mini calendar --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <button @click="prevMonth()" class="p-1.5 rounded-lg hover:bg-slate-100">
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5 text-slate-500"></i>
                    </button>
                    <p class="text-sm font-black text-slate-900"
                        x-text="`${['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'][miniMonth.getMonth()]} ${miniMonth.getFullYear()}`">
                    </p>
                    <button @click="nextMonth()" class="p-1.5 rounded-lg hover:bg-slate-100">
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500"></i>
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-0.5">
                    <template x-for="d in ['CN','T2','T3','T4','T5','T6','T7']" :key="d">
                        <div class="text-center text-[9px] font-bold text-slate-400 pb-1" x-text="d"></div>
                    </template>
                    <template x-for="cell in miniCells" :key="cell.key">
                        <button @click="jumpToDay(cell)"
                            class="w-7 h-7 rounded-lg text-xs font-semibold mx-auto flex items-center justify-center transition-all"
                            :class="{
                                'opacity-30 text-slate-400': !cell.currentMonth,
                                'bg-slate-900 text-white': cell.isToday,
                                'bg-blue-100 text-blue-700': cell.isSelected && !cell.isToday,
                                'text-slate-700 hover:bg-slate-100': !cell.isToday && !cell.isSelected && cell.currentMonth,
                                'ring-1 ring-blue-400': cell.hasEvents && !cell.isToday && !cell.isSelected,
                            }"
                            x-text="cell.day">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Today's events --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <h3 class="font-black text-slate-900 text-sm mb-3 flex items-center gap-2">
                    <i data-lucide="sun" class="w-4 h-4 text-yellow-500"></i>
                    Hôm nay
                </h3>
                <div x-show="todayEvents.length === 0" class="text-center py-4">
                    <p class="text-xs text-slate-400">Không có sự kiện nào</p>
                </div>
                <div class="space-y-2">
                    <template x-for="ev in todayEvents" :key="ev.id">
                        <div @click="openEventDetail(ev)"
                            class="flex gap-2.5 items-start cursor-pointer group p-2 rounded-xl hover:bg-slate-50 transition-all -mx-2">
                            <div class="w-1 self-stretch rounded-full shrink-0 mt-0.5"
                                :style="`background:${typeColor(ev.type)}`"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate" x-text="ev.title"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="`${ev.startTime} – ${ev.endTime}`"></p>
                            </div>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                :style="`background:${typeColor(ev.type)}20;color:${typeColor(ev.type)}`"
                                x-text="typeLabel(ev.type)">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Upcoming events --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <h3 class="font-black text-slate-900 text-sm mb-3 flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-4 h-4 text-blue-500"></i>
                    Sắp tới
                </h3>
                <div class="space-y-2">
                    <template x-for="ev in upcomingEvents.slice(0,4)" :key="ev.id">
                        <div @click="openEventDetail(ev)"
                            class="flex gap-2.5 items-start cursor-pointer p-2 rounded-xl hover:bg-slate-50 transition-all -mx-2">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-black"
                                :style="`background:${typeColor(ev.type)}15;color:${typeColor(ev.type)}`"
                                x-text="new Date(ev.date).getDate()">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate" x-text="ev.title"></p>
                                <p class="text-xs text-slate-400" x-text="formatDateLabel(ev.date)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Legend --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <h3 class="font-black text-slate-900 text-sm mb-3">Phân loại</h3>
                <div class="space-y-2">
                    <template x-for="type in eventTypes" :key="type.key">
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" x-model="type.visible" @change="rebuildCells()"
                               class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 transition-all duration-200 cursor-pointer">
                            <div class="w-3 h-3 rounded-full"
                                :style="`background:${type.color}`"></div>
                            <span class="text-xs font-semibold text-slate-600 group-hover:text-slate-900"
                                x-text="type.label"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════
         MODAL: ADD/EDIT EVENT
    ═══════════════════════ --}}
    <div x-show="openAddEvent" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openAddEvent = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900"
                    x-text="editingEvent ? 'Chỉnh sửa sự kiện' : 'Thêm sự kiện mới'"></h2>
                <button @click="openAddEvent = false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            {{-- Color strip by type --}}
            <div class="h-1 rounded-full w-full transition-all"
                :style="`background:${typeColor(eventForm.type)}`"></div>

            <div class="space-y-4">
                {{-- Title --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tiêu đề <span class="text-red-500">*</span></label>
                    <input type="text" x-model="eventForm.title"
                        placeholder="VD: Học Toán cao cấp A1"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                {{-- Type --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Loại sự kiện</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="type in eventTypes" :key="type.key">
                            <button @click="eventForm.type = type.key"
                                class="py-2.5 rounded-xl text-xs font-bold border-2 transition-all"
                                :style="eventForm.type === type.key
                                    ? `background:${type.color};color:#fff;border-color:${type.color}`
                                    : `border-color:#e2e8f0;color:#64748b`"
                                x-text="type.label">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ngày</label>
                    <input type="date" x-model="eventForm.date"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                {{-- Time --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Bắt đầu</label>
                        <input type="time" x-model="eventForm.startTime"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Kết thúc</label>
                        <input type="time" x-model="eventForm.endTime"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ghi chú</label>
                    <textarea x-model="eventForm.description" rows="2"
                        placeholder="Ghi chú thêm..."
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 resize-none">
                    </textarea>
                </div>

                {{-- Repeat --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Lặp lại</label>
                    <select x-model="eventForm.repeat"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                        <option value="none">Không lặp</option>
                        <option value="daily">Hằng ngày</option>
                        <option value="weekly">Hằng tuần</option>
                        <option value="monthly">Hằng tháng</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button x-show="editingEvent" @click="deleteEvent()"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4 inline-block mr-1"></i>Xóa
                </button>
                <button @click="openAddEvent = false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="saveEvent()"
                    :disabled="!eventForm.title || !eventForm.date"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold
                           hover:bg-slate-700 disabled:opacity-40 transition-all">
                    <span x-text="editingEvent ? 'Cập nhật' : 'Thêm sự kiện'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════
         MODAL: EVENT DETAIL
    ═══════════════════════ --}}
    <div x-show="openDetail && detailEvent" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openDetail = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
            {{-- Color header --}}
            <div class="h-2" :style="`background:${typeColor(detailEvent?.type)}`"></div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :style="`background:${typeColor(detailEvent?.type)}15;color:${typeColor(detailEvent?.type)}`"
                            x-text="typeLabel(detailEvent?.type)">
                        </span>
                        <h3 class="text-lg font-black text-slate-900 mt-2" x-text="detailEvent?.title"></h3>
                    </div>
                    <button @click="openDetail = false" class="p-2 rounded-xl hover:bg-slate-100">
                        <i data-lucide="x" class="w-4 h-4 text-slate-500"></i>
                    </button>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                        <span x-text="formatDateLabel(detailEvent?.date)"></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                        <span x-text="`${detailEvent?.startTime} – ${detailEvent?.endTime}`"></span>
                    </div>
                    <div x-show="detailEvent?.description" class="flex items-start gap-2 text-sm text-slate-600">
                        <i data-lucide="file-text" class="w-4 h-4 text-slate-400 mt-0.5 shrink-0"></i>
                        <span x-text="detailEvent?.description"></span>
                    </div>
                    <div x-show="detailEvent?.repeat !== 'none'" class="flex items-center gap-2 text-sm text-slate-600">
                        <i data-lucide="repeat" class="w-4 h-4 text-slate-400"></i>
                        <span x-text="{'daily':'Hằng ngày','weekly':'Hằng tuần','monthly':'Hằng tháng'}[detailEvent?.repeat] || ''"></span>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button @click="editEvent(detailEvent)"
                        class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Sửa
                    </button>
                    <button @click="deleteEventById(detailEvent?.id); openDetail = false"
                        class="flex-1 py-2.5 border border-red-200 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-50 transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" 
     x-transition
     class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
     :class="{
         'bg-emerald-600': toast.type === 'success',
         'bg-amber-500':   toast.type === 'warning',
         'bg-rose-600':    toast.type === 'error'
     }"
     style="display:none;">
     
    <i x-show="toast.type === 'success'" data-lucide="check-circle" class="w-4 h-4"></i>
    <i x-show="toast.type === 'warning'" data-lucide="alert-triangle" class="w-4 h-4"></i>
    <i x-show="toast.type === 'error'"   data-lucide="x-circle" class="w-4 h-4"></i>
    
    <span x-text="toast.message"></span>
</div>

</div>

@push('scripts')
<script>
function calendarApp() {
    const TODAY = new Date();
    TODAY.setHours(0,0,0,0);

    return {
        // State
        viewMode:    'month',
        animating:   false,
        currentDate: new Date(TODAY),
        selectedDate:new Date(TODAY),
        miniMonth:   new Date(TODAY),
        monthCells:  [],
        miniCells:   [],
        weekDays:    [],
        hours:       Array.from({length:16}, (_,i) => i + 6), // 06:00 – 21:00
        openAddEvent:false,
        openDetail:  false,
        detailEvent: null,
        editingEvent:null,
        toast:       { show:false, message:'',type: 'success' },
        eventForm:   { title:'', type:'study', date:'', startTime:'08:00', endTime:'09:30', description:'', repeat:'none' },

        eventTypes: @json($eventTypes).map(type => ({ ...type, visible: true })),

        events: @json($calendarEvents ?? []),
        // ── Computed ──
        get todayEvents() {
            const t = this.dateStr(TODAY);
            return this.visibleEvents.filter(e => e.date === t).sort((a,b) => a.startTime.localeCompare(b.startTime));
        },
        get thisMonthEvents() {
            const m = this.currentDate.getMonth(), y = this.currentDate.getFullYear();
            return this.visibleEvents.filter(e => {
                const d = new Date(e.date);
                return d.getMonth() === m && d.getFullYear() === y;
            });
        },
        get upcomingEvents() {
            const t = this.dateStr(TODAY);
            return this.visibleEvents
                .filter(e => e.date >= t)
                .sort((a,b) => a.date.localeCompare(b.date) || a.startTime.localeCompare(b.startTime));
        },
        get visibleEvents() {
            const vis = new Set(this.eventTypes.filter(t => t.visible).map(t => t.key));
            return this.events.filter(e => vis.has(e.type));
        },
        get headerTitle() {
            const months = ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
            if (this.viewMode === 'month') return `${months[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
            if (this.viewMode === 'week')  return `${months[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
            return `${this.selectedDate.getDate()} ${months[this.selectedDate.getMonth()]} ${this.selectedDate.getFullYear()}`;
        },

        // ── Init ──
        init() {
            this.rebuildCells();
            this.buildMiniCells();
            this.buildWeekDays();
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        // ── Calendar builders ──
        rebuildCells() {
            const y = this.currentDate.getFullYear(), m = this.currentDate.getMonth();
            const first = new Date(y, m, 1);
            const last  = new Date(y, m+1, 0);
            const startDow = first.getDay(); // 0=Sun
            const cells = [];
            // Prev month padding
            for (let i = startDow - 1; i >= 0; i--) {
                const d = new Date(y, m, -i);
                cells.push(this.makeCell(d, false));
            }
            // Current month
            for (let day = 1; day <= last.getDate(); day++) {
                cells.push(this.makeCell(new Date(y, m, day), true));
            }
            // Next month padding
            const remaining = 42 - cells.length;
            for (let i = 1; i <= remaining; i++) {
                cells.push(this.makeCell(new Date(y, m+1, i), false));
            }
            this.monthCells = cells;
            this.$nextTick(() => lucide.createIcons());
        },

        makeCell(date, currentMonth) {
            const ds = this.dateStr(date);
            const evs = this.visibleEvents.filter(e => e.date === ds);
            return {
                key: ds,
                day: date.getDate(),
                date,
                currentMonth,
                isToday:    date.getTime() === TODAY.getTime(),
                isSelected: date.getTime() === this.selectedDate.getTime(),
                hasEvents:  evs.length > 0,
                events:     evs,
            };
        },

        buildMiniCells() {
            const y = this.miniMonth.getFullYear(), m = this.miniMonth.getMonth();
            const first = new Date(y, m, 1);
            const last  = new Date(y, m+1, 0);
            const cells = [];
            for (let i = first.getDay() - 1; i >= 0; i--) cells.push(this.makeCell(new Date(y,m,-i), false));
            for (let d = 1; d <= last.getDate(); d++) cells.push(this.makeCell(new Date(y,m,d), true));
            const rem = 42 - cells.length;
            for (let i = 1; i <= rem; i++) cells.push(this.makeCell(new Date(y,m+1,i), false));
            this.miniCells = cells;
        },

        buildWeekDays() {
            const dow  = this.currentDate.getDay();
            const sun  = new Date(this.currentDate);
            sun.setDate(sun.getDate() - dow);
            const labels = ['CN','T2','T3','T4','T5','T6','T7'];
            this.weekDays = Array.from({length:7}, (_,i) => {
                const d = new Date(sun);
                d.setDate(sun.getDate() + i);
                return {
                    date:    this.dateStr(d),
                    label:   labels[i],
                    num:     d.getDate(),
                    isToday: d.getTime() === TODAY.getTime(),
                };
            });
        },

        // ── Navigation ──
        prevPeriod() {
            this.animating = true;
            setTimeout(() => {
                if (this.viewMode === 'month') this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()-1, 1);
                else if (this.viewMode === 'week') { const d = new Date(this.currentDate); d.setDate(d.getDate()-7); this.currentDate = d; }
                else { const d = new Date(this.selectedDate); d.setDate(d.getDate()-1); this.selectedDate = d; this.currentDate = d; }
                this.rebuild(); this.animating = false;
            }, 150);
        },
        nextPeriod() {
            this.animating = true;
            setTimeout(() => {
                if (this.viewMode === 'month') this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()+1, 1);
                else if (this.viewMode === 'week') { const d = new Date(this.currentDate); d.setDate(d.getDate()+7); this.currentDate = d; }
                else { const d = new Date(this.selectedDate); d.setDate(d.getDate()+1); this.selectedDate = d; this.currentDate = d; }
                this.rebuild(); this.animating = false;
            }, 150);
        },
        goToday() {
            this.currentDate  = new Date(TODAY);
            this.selectedDate = new Date(TODAY);
            this.rebuild();
        },
        prevMonth() { this.miniMonth = new Date(this.miniMonth.getFullYear(), this.miniMonth.getMonth()-1, 1); this.buildMiniCells(); },
        nextMonth() { this.miniMonth = new Date(this.miniMonth.getFullYear(), this.miniMonth.getMonth()+1, 1); this.buildMiniCells(); },
        rebuild() {
            this.rebuildCells();
            this.buildWeekDays();
            this.$nextTick(() => lucide.createIcons());
        },

        // ── Day selection ──
        selectDay(cell) {
            this.selectedDate = new Date(cell.date);
            this.rebuildCells();
            this.buildMiniCells();
            this.$nextTick(() => lucide.createIcons());
        },
        jumpToDay(cell) {
            this.selectedDate = new Date(cell.date);
            this.currentDate  = new Date(cell.date);
            this.viewMode     = 'day';
            this.rebuild();
        },
        goDayView(day) {
            const d = new Date(day.date);
            this.selectedDate = d;
            this.currentDate  = d;
            this.viewMode     = 'day';
            this.rebuild();
        },
        setView(v) {
            this.viewMode = v;
            this.rebuild();
        },

        // ── Event helpers ──
        typeColor(type) {
            const map = { study:'#3B82F6', deadline:'#EF4444', exam:'#8B5CF6', personal:'#10B981' };
            return map[type] || '#64748B';
        },
        typeLabel(type) {
            const map = { study:'Học tập', deadline:'Deadline', exam:'Thi cử', personal:'Cá nhân' };
            return map[type] || type;
        },
        getEventsForSlot(date, hour) {
            return this.visibleEvents.filter(e => {
                if (e.date !== date) return false;
                const h = parseInt(e.startTime.split(':')[0]);
                return h === hour;
            });
        },
        formatHour(h) { return `${String(h).padStart(2,'0')}:00`; },
        dateStr(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; },
        formatDateLabel(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const days = ['CN','T2','T3','T4','T5','T6','T7'];
            return `${days[d.getDay()]}, ${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()}`;
        },

        // ── CRUD ──
        resetForm() {
            this.editingEvent = null;
            this.eventForm = {
                title:'', type:'study',
                date: this.dateStr(this.selectedDate),
                startTime:'08:00', endTime:'09:30',
                description:'', repeat:'none',
            };
        },
        quickAddOnSlot(date, hour) {
            this.openAddEvent = true;
            this.editingEvent = null;
            this.eventForm = {
                title:'', type:'study', date,
                startTime: `${String(hour).padStart(2,'0')}:00`,
                endTime:   `${String(hour+1).padStart(2,'0')}:30`,
                description:'', repeat:'none',
            };
            this.$nextTick(() => lucide.createIcons());
        },
        async saveEvent() {
                if (!this.eventForm.title || !this.eventForm.date) return;

                const isEditing = !!this.editingEvent;
                const url = isEditing
                    ? `/user/calendars/${this.editingEvent.id}`
                    : '{{ route("user.calendars.store") }}';
                const method = isEditing ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.eventForm)
                    });

                    const result = await response.json();

                    if (result.success) {
                        if (isEditing) {
                            // Dùng splice để Alpine detect được thay đổi
                            const idx = this.events.findIndex(e => e.id === this.editingEvent.id);
                             if (idx !== -1) {
                                // Dùng eventForm thay vì result.data để đảm bảo đúng field names
                                const updatedEvent = { 
                                    ...this.events[idx], 
                                    ...this.eventForm,
                                    id: this.editingEvent.id  // giữ nguyên id
                                };
                                this.events.splice(idx, 1, updatedEvent);
                            }
                            this.showToast('Đã cập nhật sự kiện thành công!');
                        } else {
                            this.events.push({ id: result.data.id, ...this.eventForm });
                            this.showToast('Đã thêm sự kiện thành công!');
                        }

                        this.openAddEvent = false;
                        this.editingEvent = null;
                        this.eventForm = { title: '', type: 'study', date: '', startTime: '08:00', endTime: '09:30', description: '', repeat: 'none' };
                        this.rebuild();
                    } else {
                        this.showToast('Lỗi: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('saveEvent error:', error);
                    this.showToast('Có lỗi xảy ra khi kết nối server', 'error');
                }
            },
        openEventDetail(ev) {
            console.log('openEventDetail: Mở chi tiết sự kiện với ID:', ev.id);
            this.detailEvent = ev;
            this.openDetail  = true;
            this.$nextTick(() => lucide.createIcons());
        },
        editEvent(ev) {
            this.openDetail   = false;
            this.editingEvent = ev;
            this.eventForm    = { ...ev };
            this.openAddEvent = true;
            this.$nextTick(() => lucide.createIcons());
        },
        async deleteEvent() {
            if (!this.editingEvent) {
                console.warn('deleteEvent: Không tìm thấy editingEvent');
                return;
            }

            if (!confirm('Bạn có chắc chắn muốn xóa sự kiện này không?')) return;

            // Log ID sự kiện sắp xóa
            console.log('deleteEvent: Đang bắt đầu xóa sự kiện với ID:', this.editingEvent.id);

            
        },
        async deleteEventById(id) {
           
            try {
                 
                const url = "{{ route('user.calendars.destroy', ':id') }}".replace(':id', id);
                console.log('deleteEvent: Gọi API tới URL:', url);

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                // Log trạng thái phản hồi
                console.log('deleteEvent: Phản hồi từ server status:', response.status);

                const result = await response.json();
                console.log('deleteEvent: Kết quả trả về:', result);

                if (result.success) {
                    console.log('deleteEvent: Xóa thành công, đang cập nhật UI...');
                    this.events = this.events.filter(e => e.id !== id);
                    this.showToast('Đã xóa sự kiện thành công!');
                    this.openAddEvent = false;
                    this.openDetail = false;
                    this.editingEvent = null;
                    this.rebuild();
                } else {
                    console.error('deleteEvent: Server từ chối xóa:', result.message);
                    this.showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('deleteEvent: Lỗi ngoại lệ (Exception):', error);
                this.showToast('Lỗi kết nối server!', 'error');
            }
            // this.rebuild();
            // this.showToast('Đã xóa sự kiện');
        },
        showToast(msg, type = 'success') {
            this.toast = { show: true, message: msg, type: type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 2500);
        },
    };
}

document.addEventListener('alpine:initialized', () => lucide.createIcons());
</script>
@endpush
@endsection