@extends('layouts.app')
@section('title', 'Cấu hình hệ thống Coin - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .cfg-card { transition: all .2s; }
    .cfg-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,.1); }
    .skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
                background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius:8px; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
    .toggle-dot { transition: transform .2s ease; }
</style>
@endpush

@section('content')
<div x-data="walletConfigManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Hệ thống Coin</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Cấu hình hệ thống Coin</h1>
            <p class="text-slate-500 text-sm mt-0.5">Thiết lập tỷ lệ thưởng, trừ coin và quy đổi Coin ↔ Token / Lượt tải</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openCreateModal()"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i> Thêm config
            </button>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-if="kpis.length === 0">
            <template x-for="i in [1,2,3,4]" :key="i">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="skeleton h-9 w-9 rounded-xl mb-3"></div>
                    <div class="skeleton h-7 w-20 mb-2"></div>
                    <div class="skeleton h-3 w-28"></div>
                </div>
            </template>
        </template>
        <template x-for="kpi in kpis" :key="kpi.key">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 relative overflow-hidden">
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

    {{-- PREVIEW QUY TẮC NGHIỆP VỤ --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="sparkles" class="w-4 h-4 text-indigo-500"></i>
            <h2 class="text-sm font-black text-slate-900">Quy tắc đang áp dụng</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Rule 1: Upload -> coin --}}
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="upload-cloud" class="w-4 h-4 text-emerald-600"></i>
                    <span class="text-xs font-bold text-emerald-700">Upload tài liệu</span>
                </div>
                <p class="text-sm text-slate-700">
                    Tài liệu được duyệt → <span class="font-black text-emerald-700" x-text="ruleValue('earning_rate.document_upload')"></span> coin
                </p>
            </div>
            {{-- Rule 2: Coin -> Token --}}
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="zap" class="w-4 h-4 text-indigo-600"></i>
                    <span class="text-xs font-bold text-indigo-700">Mua Token chat (hết token ở UserLog)</span>
                </div>
                <p class="text-sm text-slate-700">
                    <span class="font-black text-indigo-700" x-text="ruleValue('conversion.token_pack_coin_cost')"></span> coin
                    → <span class="font-black text-indigo-700" x-text="ruleValue('conversion.token_pack_amount')"></span> token
                </p>
            </div>
            {{-- Rule 3: Coin -> Download --}}
            <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="download" class="w-4 h-4 text-amber-600"></i>
                    <span class="text-xs font-bold text-amber-700">Mua lượt tải (hết download_limit ở UserLog)</span>
                </div>
                <p class="text-sm text-slate-700">
                    <span class="font-black text-amber-700" x-text="ruleValue('conversion.download_pack_coin_cost')"></span> coin
                    → <span class="font-black text-amber-700" x-text="ruleValue('conversion.download_pack_amount')"></span> lượt tải
                </p>
            </div>
        </div>
    </div>

    {{-- TABS --}}
    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-2xl p-1.5 w-fit flex-wrap">
        <template x-for="g in groupTabs" :key="g.key">
            <button @click="activeGroup = g.key"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
                :class="activeGroup===g.key ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-700'">
                <i :data-lucide="g.icon" class="w-4 h-4"></i>
                <span x-text="g.label"></span>
                <span x-show="groupCount(g.key) > 0"
                    class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                    :class="activeGroup===g.key ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-600'"
                    x-text="groupCount(g.key)">
                </span>
            </button>
        </template>
    </div>

    {{-- SEARCH --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-48">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input x-model="search" type="text" placeholder="Tìm theo key hoặc mô tả..."
                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
        </div>
        <select x-model="filterActive" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
            <option value="">Tất cả trạng thái</option>
            <option value="1">Đang bật</option>
            <option value="0">Đang tắt</option>
        </select>
        <button @click="search='';filterActive=''"
            class="px-3 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-500 hover:bg-slate-50 transition-all">
            <i data-lucide="x" class="w-4 h-4 inline-block"></i>
        </button>
    </div>

    {{-- LOADING SKELETON --}}
    <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="i in [1,2,3,4,5,6]" :key="i">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3">
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton h-3 w-full"></div>
                <div class="skeleton h-9 w-full rounded-xl"></div>
            </div>
        </template>
    </div>

    {{-- CONFIG GRID --}}
    <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="cfg in filteredConfigs" :key="cfg.id">
            <div class="cfg-card bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-3">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                            :style="`background:${groupColor(cfg.key)}15;color:${groupColor(cfg.key)}`"
                            x-text="groupLabel(cfg.key)"></span>
                        <p class="font-mono text-xs font-bold text-slate-800 mt-2 break-all" x-text="cfg.key"></p>
                    </div>
                    {{-- Toggle active --}}
                    <button @click="toggleActive(cfg)"
                        :disabled="processingIds.includes(cfg.id)"
                        class="shrink-0 w-10 h-6 rounded-full p-0.5 transition-colors"
                        :class="cfg.is_active ? 'bg-emerald-500' : 'bg-slate-200'">
                        <span class="toggle-dot block w-5 h-5 rounded-full bg-white shadow"
                            :class="cfg.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                    </button>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed line-clamp-2" x-text="cfg.description || 'Chưa có mô tả'"></p>

                {{-- Inline value editor --}}
                <div class="flex items-center gap-2 mt-auto pt-2 border-t border-slate-100">
                    <input type="number" x-model.number="cfg._editValue"
                        class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm font-bold text-slate-800
                               focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <button @click="saveValue(cfg)"
                        :disabled="processingIds.includes(cfg.id) || cfg._editValue === cfg.value"
                        class="px-3 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-1"
                        :class="cfg._editValue !== cfg.value
                            ? 'bg-slate-900 text-white hover:bg-slate-700'
                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                        <svg x-show="processingIds.includes(cfg.id)" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <i x-show="!processingIds.includes(cfg.id)" data-lucide="check" class="w-3.5 h-3.5"></i>
                        Lưu
                    </button>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                    <button @click="openEditModal(cfg)" class="flex items-center gap-1 hover:text-indigo-600 transition-colors">
                        <i data-lucide="pencil" class="w-3 h-3"></i> Sửa chi tiết
                    </button>
                    <button @click="deleteConfig(cfg)" class="flex items-center gap-1 hover:text-rose-600 transition-colors">
                        <i data-lucide="trash-2" class="w-3 h-3"></i> Xóa
                    </button>
                </div>
            </div>
        </template>

        <div x-show="filteredConfigs.length===0" class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200">
            <i data-lucide="settings-2" class="w-10 h-10 text-slate-200 mx-auto mb-3"></i>
            <p class="text-sm text-slate-400">Không tìm thấy config nào</p>
        </div>
    </div>

    {{-- MODAL: Sửa chi tiết --}}
    <div x-show="editModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="editModalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Sửa config</h2>
                    <p class="text-xs font-mono text-slate-400 mt-0.5" x-text="editTarget?.key"></p>
                </div>
                <button @click="editModalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Giá trị</label>
                <input type="number" x-model.number="editForm.value"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả</label>
                <textarea x-model="editForm.description" rows="3"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" x-model="editForm.is_active" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-300">
                Bật config này
            </label>

            <div class="flex gap-3 pt-1">
                <button @click="editModalOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Hủy</button>
                <button @click="confirmEdit()"
                    :disabled="editTarget && processingIds.includes(editTarget.id)"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-50 transition-all">
                    Lưu thay đổi
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL: Thêm config mới --}}
    <div x-show="createModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="createModalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-900">Thêm config mới</h2>
                <button @click="createModalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nhóm</label>
                <select x-model="createForm.group"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <template x-for="g in groupTabs.filter(g => g.key !== 'all')" :key="g.key">
                        <option :value="g.key" x-text="g.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Key (vd: document_summary)</label>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-mono text-slate-400 shrink-0" x-text="createForm.group + '.'"></span>
                    <input type="text" x-model="createForm.keySuffix" placeholder="ten_config"
                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Giá trị</label>
                <input type="number" x-model.number="createForm.value"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả</label>
                <textarea x-model="createForm.description" rows="2" placeholder="Mô tả ý nghĩa config..."
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="createModalOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Hủy</button>
                <button @click="confirmCreate()"
                    :disabled="!createForm.keySuffix || creating"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-50 transition-all">
                    Tạo config
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="{'bg-emerald-600':toast.type==='success','bg-rose-600':toast.type==='error','bg-amber-500':toast.type==='warning'}">
        <i :data-lucide="toast.type==='success'?'check-circle':toast.type==='error'?'x-circle':'alert-triangle'" class="w-4 h-4"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function walletConfigManager() {
    return {
        loading: true,
        configs: [],
        kpis: [],
        search: '',
        filterActive: '',
        activeGroup: 'all',
        processingIds: [],
        creating: false,

        editModalOpen: false,
        editTarget: null,
        editForm: { value: 0, description: '', is_active: true },

        createModalOpen: false,
        createForm: { group: 'earning_rate', keySuffix: '', value: 0, description: '' },

        toast: { show: false, msg: '', type: 'success' },

        groupTabs: [
            { key: 'all',           label: 'Tất cả',          icon: 'layout-grid' },
            { key: 'earning_rate',  label: 'Kiếm coin',       icon: 'coins' },
            { key: 'spending_rate', label: 'Trừ coin',        icon: 'minus-circle' },
            { key: 'conversion',    label: 'Quy đổi',         icon: 'arrow-left-right' },
            { key: 'daily_limit',   label: 'Giới hạn ngày',   icon: 'gauge' },
        ],

        groupMeta: {
            earning_rate:  { label: 'Kiếm coin',     color: '#10b981' },
            spending_rate: { label: 'Trừ coin',      color: '#ef4444' },
            conversion:    { label: 'Quy đổi',       color: '#6366f1' },
            daily_limit:   { label: 'Giới hạn ngày', color: '#f59e0b' },
        },

        get filteredConfigs() {
            const q = this.search.toLowerCase();
            return this.configs.filter(c =>
                (this.activeGroup === 'all' || c.key.startsWith(this.activeGroup + '.'))
                && (!q || c.key.toLowerCase().includes(q) || (c.description || '').toLowerCase().includes(q))
                && (this.filterActive === '' || String(Number(c.is_active)) === this.filterActive)
            );
        },

        groupKeyOf(key) { return key.includes('.') ? key.split('.')[0] : 'other'; },
        groupLabel(key) { return this.groupMeta[this.groupKeyOf(key)]?.label ?? 'Khác'; },
        groupColor(key) { return this.groupMeta[this.groupKeyOf(key)]?.color ?? '#64748b'; },
        groupCount(groupKey) {
            if (groupKey === 'all') return this.configs.length;
            return this.configs.filter(c => c.key.startsWith(groupKey + '.')).length;
        },

        ruleValue(key) {
            const cfg = this.configs.find(c => c.key === key);
            return cfg ? cfg.value : '—';
        },

        init() {
            this.fetchConfigs();
        },

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

        async fetchConfigs() {
            this.loading = true;
            try {
                const res = await this.api('/admin/wallet-configs/data');
                this.configs = (res.data ?? []).map(c => ({ ...c, _editValue: c.value }));
                this.kpis = res.kpis ?? [];
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async saveValue(cfg) {
            if (cfg._editValue === cfg.value || this.processingIds.includes(cfg.id)) return;
            this.processingIds.push(cfg.id);
            try {
                const res = await this.api(`/admin/wallet-configs/${cfg.id}`, 'PATCH', { value: cfg._editValue });
                Object.assign(cfg, res.data);
                cfg._editValue = cfg.value;
                this.showToast('Đã cập nhật giá trị');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== cfg.id);
            }
        },

        async toggleActive(cfg) {
            if (this.processingIds.includes(cfg.id)) return;
            this.processingIds.push(cfg.id);
            try {
                const res = await this.api(`/admin/wallet-configs/${cfg.id}`, 'PATCH', { is_active: !cfg.is_active });
                Object.assign(cfg, res.data);
                this.showToast(cfg.is_active ? 'Đã bật config' : 'Đã tắt config');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== cfg.id);
            }
        },

        openEditModal(cfg) {
            this.editTarget = cfg;
            this.editForm = { value: cfg.value, description: cfg.description, is_active: !!cfg.is_active };
            this.editModalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async confirmEdit() {
            if (!this.editTarget) return;
            const cfg = this.editTarget;
            this.processingIds.push(cfg.id);
            try {
                const res = await this.api(`/admin/wallet-configs/${cfg.id}`, 'PATCH', this.editForm);
                Object.assign(cfg, res.data);
                cfg._editValue = cfg.value;
                this.editModalOpen = false;
                this.showToast('Đã lưu thay đổi');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== cfg.id);
            }
        },

        openCreateModal() {
            this.createForm = { group: 'earning_rate', keySuffix: '', value: 0, description: '' };
            this.createModalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async confirmCreate() {
            if (!this.createForm.keySuffix) return;
            this.creating = true;
            const key = `${this.createForm.group}.${this.createForm.keySuffix.trim()}`;
            try {
                const res = await this.api('/admin/wallet-configs', 'POST', {
                    key,
                    value: this.createForm.value,
                    description: this.createForm.description,
                });
                this.configs.push({ ...res.data, _editValue: res.data.value });
                this.createModalOpen = false;
                this.showToast('Đã tạo config mới');
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.creating = false;
            }
        },

        async deleteConfig(cfg) {
            if (!confirm(`Xóa config "${cfg.key}"?`)) return;
            try {
                await this.api(`/admin/wallet-configs/${cfg.id}`, 'DELETE');
                this.configs = this.configs.filter(c => c.id !== cfg.id);
                this.showToast('Đã xóa config', 'warning');
            } catch (e) {
                this.showToast(e.message, 'error');
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