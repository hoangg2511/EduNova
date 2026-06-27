@extends('layouts.app')
@section('title', 'Thông báo hệ thống - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .notif-row { transition: background .15s, border-color .2s; }
    .notif-row:hover { background: #f8fafc; }
    .notif-row.unread { border-left: 3px solid #6366f1; }
    .notif-row.read   { border-left: 3px solid transparent; }
    .skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
                background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:8px; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
    .badge-pulse { animation: badge-pulse 2s infinite; }
    @keyframes badge-pulse { 0%,100%{ opacity:1; } 50%{ opacity:.6; } }
</style>
@endpush

@section('content')
<div x-data="notificationManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Thông báo hệ thống</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black text-slate-900">Thông báo hệ thống</h1>
                <span x-show="unreadCount > 0"
                    class="badge-pulse flex items-center gap-1 px-2.5 py-1 bg-indigo-600 text-white text-xs font-black rounded-full">
                    <span x-text="unreadCount"></span> chưa đọc
                </span>
            </div>
            <p class="text-slate-500 text-sm mt-0.5">Gửi và quản lý thông báo hệ thống đến người dùng EduNova</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="markAllRead()" x-show="unreadCount > 0"
                class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-50 transition-all">
                <i data-lucide="check-check" class="w-4 h-4"></i> Đọc tất cả
            </button>
            <button @click="openBroadcast()"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
                <i data-lucide="send" class="w-4 h-4"></i> Gửi thông báo
            </button>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-if="kpis.length === 0">
            <template x-for="i in [1,2,3,4]" :key="i">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="skeleton h-9 w-9 rounded-xl mb-3"></div>
                    <div class="skeleton h-7 w-16 mb-2"></div>
                    <div class="skeleton h-3 w-24"></div>
                </div>
            </template>
        </template>
        <template x-for="kpi in kpis" :key="kpi.key">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="`background:${kpi.color}15`">
                        <i :data-lucide="kpi.icon" class="w-4 h-4" :style="`color:${kpi.color}`"></i>
                    </div>
                    <span class="text-xs font-semibold text-slate-500" x-text="kpi.label"></span>
                </div>
                <p class="text-2xl font-black text-slate-900" x-text="kpi.value"></p>
                <p class="text-[10px] text-slate-400 mt-1" x-text="kpi.sub"></p>
            </div>
        </template>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="search" @input.debounce.400ms="fetchData()"
                    type="text" placeholder="Tìm tiêu đề, nội dung..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            {{-- read/unread/all toggle --}}
            <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                <template x-for="f in filterTabs" :key="f.key">
                    <button @click="filter=f.key; fetchData()"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                        :class="filter===f.key ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                        x-text="f.label">
                    </button>
                </template>
            </div>

            {{-- bulk delete --}}
            <div x-show="selected.length > 0" x-transition class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-600 whitespace-nowrap"
                    x-text="`${selected.length} đã chọn`">
                </span>
                <button @click="bulkDelete()"
                    class="px-3 py-2 text-xs font-bold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-all">
                    <i data-lucide="trash-2" class="w-3 h-3 inline-block mr-1"></i>Xóa
                </button>
                <button @click="selected=[]"
                    class="px-3 py-2 text-xs font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">
                    Bỏ chọn
                </button>
            </div>
        </div>
    </div>

    {{-- NOTIFICATION LIST --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        {{-- Loading --}}
        <div x-show="loading" class="divide-y divide-slate-50">
            <template x-for="i in [1,2,3,4,5]" :key="i">
                <div class="px-5 py-4 flex items-start gap-4">
                    <div class="skeleton w-4 h-4 rounded mt-0.5 shrink-0"></div>
                    <div class="skeleton w-9 h-9 rounded-xl shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="skeleton h-4 w-1/2"></div>
                        <div class="skeleton h-3 w-3/4"></div>
                        <div class="skeleton h-3 w-1/4"></div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty --}}
        <div x-show="!loading && notifications.length === 0" class="py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="bell-off" class="w-8 h-8 text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-semibold">Không có thông báo nào</p>
            <p class="text-slate-400 text-sm mt-1"
                x-text="filter !== 'all' || search ? 'Thử thay đổi bộ lọc' : 'Nhấn Gửi thông báo để tạo mới'">
            </p>
        </div>

        {{-- List --}}
        <div x-show="!loading && notifications.length > 0" class="divide-y divide-slate-50">
            <template x-for="n in notifications" :key="n.id">
                <div class="notif-row px-5 py-4 flex items-start gap-4"
                    :class="n.is_read ? 'read' : 'unread'">

                    <input type="checkbox" :value="n.id" x-model="selected" @click.stop
                        class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-400 shrink-0 cursor-pointer">

                    {{-- Icon hệ thống --}}
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-indigo-50">
                        <i data-lucide="settings" class="w-4 h-4 text-indigo-500"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- unread dot --}}
                                <span x-show="!n.is_read"
                                    class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 badge-pulse"></span>
                                <p class="text-sm font-bold"
                                    :class="n.is_read ? 'text-slate-600' : 'text-slate-900'"
                                    x-text="n.title">
                                </p>
                            </div>
                            {{-- Actions --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button x-show="!n.is_read" @click="markRead(n)"
                                    class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all" title="Đánh dấu đã đọc">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-500"></i>
                                </button>
                                <button @click="deleteOne(n)"
                                    class="p-1.5 rounded-lg hover:bg-rose-50 transition-all" title="Xóa">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-400"></i>
                                </button>
                            </div>
                        </div>

                        <p class="text-sm mt-0.5 line-clamp-2"
                            :class="n.is_read ? 'text-slate-400' : 'text-slate-500'"
                            x-text="n.body">
                        </p>

                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <div x-show="n.user" class="flex items-center gap-1.5">
                                <div class="w-5 h-5 rounded-lg flex items-center justify-center text-[10px] font-black text-white"
                                    :style="`background:${n.user?.color}`"
                                    x-text="n.user?.name?.[0]">
                                </div>
                                <span class="text-[10px] font-semibold text-slate-500" x-text="n.user?.name"></span>
                                <span class="text-[10px] text-slate-300">·</span>
                                <span class="text-[10px] text-slate-400" x-text="n.user?.email"></span>
                            </div>
                            <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                <span x-text="n.time_ago"></span>
                            </span>
                            <span x-show="n.is_read" class="text-[10px] text-emerald-500 flex items-center gap-1">
                                <i data-lucide="check-circle" class="w-3 h-3"></i>
                                <span x-text="'Đã đọc ' + n.read_at"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Pagination --}}
        <div x-show="!loading && totalPages > 1"
            class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400"
                x-text="`Trang ${currentPage}/${totalPages} · ${totalItems} thông báo`">
            </span>
            <div class="flex items-center gap-1">
                <button @click="changePage(currentPage-1)" :disabled="currentPage<=1"
                    class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 disabled:opacity-30 transition-all">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-slate-500"></i>
                </button>
                <template x-for="p in pageRange" :key="p">
                    <button @click="changePage(p)"
                        class="w-8 h-8 rounded-xl text-xs font-bold transition-all"
                        :class="currentPage===p ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-600'"
                        x-text="p">
                    </button>
                </template>
                <button @click="changePage(currentPage+1)" :disabled="currentPage>=totalPages"
                    class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 disabled:opacity-30 transition-all">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════
         MODAL: Gửi thông báo hệ thống
    ════════════════════════════════ --}}
    <div x-show="broadcastOpen" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="broadcastOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 space-y-5 overflow-y-auto max-h-[90vh]">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <i data-lucide="settings" class="w-5 h-5 text-indigo-500"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900">Gửi thông báo hệ thống</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Broadcast đến người dùng EduNova</p>
                    </div>
                </div>
                <button @click="broadcastOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            {{-- Target --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Gửi đến</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="tg in targets" :key="tg.key">
                        <button @click="broadcastForm.target=tg.key"
                            class="py-3 rounded-xl border-2 text-xs font-bold transition-all flex flex-col items-center gap-1.5"
                            :class="broadcastForm.target===tg.key
                                ? 'bg-slate-900 text-white border-slate-900'
                                : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                            <i :data-lucide="tg.icon" class="w-4 h-4"></i>
                            <span x-text="tg.label"></span>
                        </button>
                    </template>
                </div>

                <div x-show="broadcastForm.target==='role'" class="mt-3">
                    <select x-model="broadcastForm.target_role"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="student">Học viên</option>
                        <option value="instructor">Giảng viên</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div x-show="broadcastForm.target==='user'" class="mt-3">
                    <input x-model="broadcastForm.target_user" type="email"
                        placeholder="email@example.com"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>

            {{-- Title --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Tiêu đề <span class="text-rose-500">*</span>
                </label>
                <input x-model="broadcastForm.title" type="text"
                    placeholder="VD: Cập nhật hệ thống v2.5"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nội dung <span class="text-rose-500">*</span>
                </label>
                <textarea x-model="broadcastForm.body" rows="4"
                    placeholder="Nội dung chi tiết của thông báo hệ thống..."
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                </textarea>
                <p class="text-[10px] text-slate-400 mt-1 text-right"
                    x-text="`${broadcastForm.body.length}/1000 ký tự`">
                </p>
            </div>

            {{-- Preview --}}
            <div x-show="broadcastForm.title || broadcastForm.body"
                class="bg-indigo-50 rounded-2xl p-4 border border-indigo-100">
                <p class="text-[10px] font-bold text-indigo-400 uppercase mb-2">Preview</p>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-white flex items-center justify-center shrink-0">
                        <i data-lucide="settings" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800"
                            x-text="broadcastForm.title || 'Tiêu đề thông báo'">
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-2"
                            x-text="broadcastForm.body || 'Nội dung thông báo...'">
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> Vừa xong
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="broadcastOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="sendBroadcast()"
                    :disabled="!broadcastForm.title || !broadcastForm.body || sending"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all flex items-center justify-center gap-2">
                    <svg x-show="sending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <i x-show="!sending" data-lucide="send" class="w-4 h-4"></i>
                    <span x-text="sending ? 'Đang gửi...' : 'Gửi thông báo'"></span>
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
function notificationManager() {
    return {
        notifications: [],
        kpis:    [],
        loading: true,
        selected: [],
        broadcastOpen: false,
        sending: false,
        toast: { show: false, msg: '', type: 'success' },

        search: '',
        filter: 'all',

        currentPage: 1,
        totalPages:  1,
        totalItems:  0,
        perPage:     15,

        filterTabs: [
            { key: 'all',    label: 'Tất cả' },
            { key: 'unread', label: 'Chưa đọc' },
            { key: 'read',   label: 'Đã đọc' },
        ],
        targets: [
            { key: 'all',  label: 'Tất cả',      icon: 'users' },
            { key: 'role', label: 'Theo vai trò', icon: 'shield' },
            { key: 'user', label: 'Cụ thể',       icon: 'user' },
        ],
        broadcastForm: {
            target: 'all', target_role: 'student', target_user: '',
            title: '', body: '',
        },

        // ── Computed ───────────────────────────────────────────────────────
        get unreadCount() {
            const kpi = this.kpis.find(k => k.key === 'unread');
            return kpi ? parseInt(kpi.value) : 0;
        },
        get pageRange() {
            const range = [], delta = 2;
            for (let p = Math.max(1, this.currentPage - delta);
                     p <= Math.min(this.totalPages, this.currentPage + delta); p++) {
                range.push(p);
            }
            return range;
        },

        // ── Init ───────────────────────────────────────────────────────────
        async init() {
            await this.fetchData();
            this.$watch('filter', () => { this.currentPage = 1; this.fetchData(); });
        },

        // ── API ────────────────────────────────────────────────────────────
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

        // ── Fetch ──────────────────────────────────────────────────────────
        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    filter:   this.filter,
                    search:   this.search,
                    page:     this.currentPage,
                    per_page: this.perPage,
                });
                const res = await this.api(`/admin/notifications/data?${params}`);
                this.notifications = res.data         ?? [];
                this.kpis          = res.kpis         ?? [];
                this.totalItems    = res.total        ?? 0;
                this.totalPages    = res.last_page    ?? 1;
                this.currentPage   = res.current_page ?? 1;
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        changePage(p) {
            if (p < 1 || p > this.totalPages) return;
            this.currentPage = p;
            this.fetchData();
        },

        // ── Mark read ──────────────────────────────────────────────────────
        async markRead(n) {
            if (n.is_read) return;
            try {
                const res = await this.api(`/admin/notifications/${n.id}/read`, 'PATCH');
                n.is_read = true;
                n.read_at = res.read_at;
                const kpi = this.kpis.find(k => k.key === 'unread');
                if (kpi) kpi.value = String(Math.max(0, parseInt(kpi.value) - 1));
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        async markAllRead() {
            try {
                const res = await this.api('/admin/notifications/mark-all-read', 'POST');
                this.notifications.forEach(n => { n.is_read = true; });
                const kpi = this.kpis.find(k => k.key === 'unread');
                if (kpi) kpi.value = '0';
                this.showToast(res.message);
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Delete ─────────────────────────────────────────────────────────
        async deleteOne(n) {
            try {
                await this.api(`/admin/notifications/${n.id}`, 'DELETE');
                this.notifications = this.notifications.filter(x => x.id !== n.id);
                this.totalItems = Math.max(0, this.totalItems - 1);
                const kpi = this.kpis.find(k => k.key === 'total');
                if (kpi) kpi.value = String(Math.max(0, parseInt(kpi.value) - 1));
                this.showToast('Đã xóa thông báo', 'warning');
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        async bulkDelete() {
            if (!confirm(`Xóa ${this.selected.length} thông báo đã chọn?`)) return;
            try {
                await this.api('/admin/notifications/bulk-delete', 'POST', { ids: this.selected });
                this.selected = [];
                await this.fetchData();
                this.showToast('Đã xóa thông báo đã chọn', 'warning');
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Broadcast ──────────────────────────────────────────────────────
        openBroadcast() {
            this.broadcastForm = {
                target: 'all', target_role: 'student', target_user: '',
                title: '', body: '',
            };
            this.broadcastOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async sendBroadcast() {
            if (!this.broadcastForm.title || !this.broadcastForm.body) return;
            this.sending = true;
            try {
                const res = await this.api('/admin/notifications/broadcast', 'POST', this.broadcastForm);
                this.broadcastOpen = false;
                await this.fetchData();
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.sending = false;
            }
        },

        showToast(msg, type = 'success') {
            this.toast = { show: true, msg, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => this.toast.show = false, 2500);
        },
    };
}
</script>
@endpush
@endsection