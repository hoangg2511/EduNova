@extends('layouts.app')
@section('title', 'Quản lý người dùng - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }
</style>
@endpush

@section('content')
<div x-data="userManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Người dùng</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Quản lý người dùng</h1>
            <p class="text-slate-500 text-sm mt-0.5">Xem, phân quyền và quản lý toàn bộ tài khoản</p>
        </div>
        <button @click="openModal('add')"
            class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Thêm người dùng
        </button>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
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

    {{-- FILTERS + SEARCH --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="search" type="text" placeholder="Tìm theo tên, email..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <select x-model="filterRole" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả vai trò</option>
                <option value="admin">Admin</option>
                <option value="user">Học viên</option>
            </select>
            <select x-model="filterPlan" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả gói</option>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
                <option value="premium">Premium</option>
            </select>
            <select x-model="filterStatus" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Hoạt động</option>
                <option value="banned">Bị khóa</option>
            </select>
            <button @click="search=''; filterRole=''; filterPlan=''; filterStatus=''"
                class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-50 transition-all">
                <i data-lucide="x" class="w-4 h-4 inline-block mr-1"></i>Xóa lọc
            </button>
        </div>

        {{-- Bulk actions --}}
        <div x-show="selected.length > 0" x-transition class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100">
            <span class="text-xs font-bold text-slate-600" x-text="`Đã chọn ${selected.length} người dùng`"></span>
            <button @click="bulkBan()" class="px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-all">
                <i data-lucide="lock" class="w-3 h-3 inline-block mr-1"></i>Khóa tài khoản
            </button>
            <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-all">
                <i data-lucide="trash-2" class="w-3 h-3 inline-block mr-1"></i>Xóa
            </button>
            <button @click="selected=[]" class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-all">Bỏ chọn</button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-5 py-3.5 w-10">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
                        </th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Người dùng</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Vai trò</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Gói</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Đăng ký</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="u in paginatedUsers" :key="u.id">
                        <tr class="tbl-row border-b border-slate-50">
                            <td class="px-5 py-3.5">
                                <input type="checkbox" :value="u.id" x-model="selected" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black text-white shrink-0 bg-indigo-500" x-text="u.name[0]"></div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800" x-text="u.name"></p>
                                        <p class="text-xs text-slate-400" x-text="u.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-indigo-50 text-indigo-600': u.role === 'admin',
                                        'bg-emerald-50 text-emerald-600': u.role === 'user',
                                    }"
                                    x-text="{'admin':'Admin','user':'Học viên'}[u.role] || 'Học viên'">
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-slate-100 text-slate-500': u.plan === 'free',
                                        'bg-blue-50 text-blue-600': u.plan === 'pro',
                                        'bg-violet-50 text-violet-600': u.plan === 'premium',
                                    }"
                                    x-text="u.plan.charAt(0).toUpperCase()+u.plan.slice(1)">
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button @click="toggleStatus(u)"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full transition-all"
                                    :class="u.status === 'active' ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-rose-50 text-rose-600 hover:bg-rose-100'"
                                    x-text="u.status === 'active' ? 'Hoạt động' : 'Đã khóa'">
                                </button>
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs text-slate-500" x-text="u.joinDate"></td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openModal('view', u)" class="p-1.5 rounded-lg hover:bg-slate-100 transition-all" title="Xem chi tiết">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                    </button>
                                    <button @click="openModal('edit', u)" class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all" title="Chỉnh sửa">
                                        <i data-lucide="edit-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    </button>
                                    <button @click="toggleStatus(u)" class="p-1.5 rounded-lg hover:bg-amber-50 transition-all" :title="u.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản'">
                                        <i data-lucide="lock" class="w-3.5 h-3.5" :class="u.status === 'active' ? 'text-amber-500' : 'text-emerald-500'"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredUsers.length === 0">
                        <td colspan="7" class="py-16 text-center">
                            <i data-lucide="users" class="w-10 h-10 text-slate-200 mx-auto mb-3"></i>
                            <p class="text-sm text-slate-400">Không tìm thấy người dùng nào</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400"
                x-text="`Hiển thị ${(page-1)*perPage+1}–${Math.min(page*perPage, filteredUsers.length)} / ${filteredUsers.length} người dùng`">
            </span>
            <div class="flex items-center gap-1">
                <button @click="page--" :disabled="page===1"
                    class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 disabled:opacity-30 transition-all">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-slate-500"></i>
                </button>
                <template x-for="p in totalPages" :key="p">
                    <button @click="page=p"
                        class="w-8 h-8 rounded-xl text-xs font-bold transition-all"
                        :class="page===p ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-600'"
                        x-text="p">
                    </button>
                </template>
                <button @click="page++" :disabled="page===totalPages"
                    class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-slate-100 disabled:opacity-30 transition-all">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black text-slate-900"
                    x-text="modalMode==='add' ? 'Thêm người dùng' : modalMode==='edit' ? 'Chỉnh sửa' : 'Chi tiết người dùng'">
                </h2>
                <button @click="modalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            {{-- View mode ── CHỈ đọc từ modalUser.plan / modalUser.subscription
                 (cùng một nguồn dữ liệu duy nhất trả về từ show()) --}}
            <div x-show="modalMode==='view' && modalUser" class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-black text-white bg-indigo-500" x-text="modalUser?.name[0]"></div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900" x-text="modalUser?.name"></h3>
                        <p class="text-sm text-slate-500" x-text="modalUser?.email"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Vai trò</p>
                        <p class="text-sm font-bold text-slate-800 mt-1" x-text="{'admin':'Admin','user':'Học viên'}[modalUser?.role]"></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Gói đăng ký</p>
                        <p class="text-sm font-bold text-slate-800 mt-1" x-text="modalUser?.plan?.name"></p>
                        <p class="text-[11px] text-slate-400" x-text="modalUser?.plan?.formattedPrice"></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Trạng thái thuê bao</p>
                        <template x-if="modalUser?.subscription">
                            <p class="text-sm font-bold mt-1"
                               :class="modalUser.subscription.isExpiringSoon ? 'text-amber-600' : 'text-emerald-600'"
                               x-text="modalUser.subscription.isExpiringSoon ? 'Sắp hết hạn' : 'Đang hoạt động'">
                            </p>
                        </template>
                        <template x-if="!modalUser?.subscription">
                            <p class="text-sm font-bold text-slate-500 mt-1">Gói miễn phí</p>
                        </template>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase" x-text="modalUser?.subscription?.endsAt ? 'Hết hạn' : 'Ngày đăng ký'"></p>
                        <p class="text-sm font-bold text-slate-800 mt-1"
                           x-text="modalUser?.subscription?.endsAt ?? modalUser?.joinDate">
                        </p>
                    </div>
                </div>
                <button @click="openModal('edit', modalUser)" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                    Chỉnh sửa tài khoản
                </button>
            </div>

            {{-- Add/Edit form --}}
            <div x-show="modalMode !== 'view'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Họ tên <span class="text-rose-500">*</span></label>
                        <input x-model="form.name" type="text" placeholder="Nguyễn Văn A"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email <span class="text-rose-500">*</span></label>
                        <input x-model="form.email" type="email" placeholder="email@example.com" disabled
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>
                <div x-show="modalMode === 'add'">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mật khẩu <span class="text-rose-500">*</span></label>
                    <input x-model="form.password" type="password" placeholder="••••••••"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Vai trò</label>
                        <select x-model="form.role" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="user">Học viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div x-show="modalMode === 'add'">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Gói đăng ký ban đầu</label>
                        <select x-model="form.plan" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                </div>
                <p x-show="modalMode === 'edit'" class="text-xs text-slate-400">
                    Để đổi gói đăng ký, vào trang <a href="{{ route('admin.subscriptions.index') }}" class="text-indigo-600 font-semibold hover:underline">Quản lý thuê bao</a>.
                </p>
                <div class="flex gap-3 pt-2">
                    <button @click="modalOpen=false" class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Hủy</button>
                    <button @click="saveUser()" :disabled="!form.name || !form.email"
                        class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all"
                        x-text="modalMode==='edit' ? 'Cập nhật' : 'Thêm người dùng'">
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="toast.type==='success' ? 'bg-emerald-600' : 'bg-rose-600'">
        <i :data-lucide="toast.type==='success' ? 'check-circle' : 'alert-circle'" class="w-4 h-4"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>


@push('scripts')
<script>
function userManager() {
    return {
        // ── State ──────────────────────────────────────────────────────────
        search: '', filterRole: '', filterPlan: '', filterStatus: '',
        page: 1, perPage: 8,
        selected: [],
        users: [],
        kpis: [],
        totalItems: 0,
        totalPages: 1,
        loading: false,

        modalOpen: false, modalMode: 'add', modalUser: null,
        form: { name:'', email:'', password:'', role:'user', plan:'free' },
        toast: { show:false, msg:'', type:'success' },

        // ── Computed ───────────────────────────────────────────────────────
        get paginatedUsers() { return this.users; },   // đã phân trang server-side
        get filteredUsers() { return this.users; },

        // ── Init ───────────────────────────────────────────────────────────
        async init() {
            await this.fetchUsers();
            this.$watch('search',       () => { this.page = 1; this.fetchUsers(); });
            this.$watch('filterRole',   () => { this.page = 1; this.fetchUsers(); });
            this.$watch('filterPlan',   () => { this.page = 1; this.fetchUsers(); });
            this.$watch('filterStatus', () => { this.page = 1; this.fetchUsers(); });
            this.$watch('page',         () => this.fetchUsers());
        },

        // ── API helpers ────────────────────────────────────────────────────
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async api(url, method = 'GET', body = null) {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
            };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch(url, opts);
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message ?? `HTTP ${res.status}`);
            }
            return res.json();
        },

        // ── Fetch list ─────────────────────────────────────────────────────
        async fetchUsers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    search:   this.search,
                    role:     this.filterRole,
                    plan:     this.filterPlan,
                    status:   this.filterStatus,
                    page:     this.page,
                    per_page: this.perPage,
                });
                const res = await this.api(`/admin/users/data?${params}`);
                this.users      = res.data;
                this.kpis       = res.kpis;
                this.totalItems = res.total;
                this.totalPages = res.last_page;
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // ── Modal ──────────────────────────────────────────────────────────
        async openModal(mode, user = null) {
            this.modalMode = mode;
            this.modalUser = null;
            if (mode === 'view' && user) {
                try {
                    // show() trả về { user: { ..., plan: {...}, subscription: {...} } }
                    const res = await this.api(`/admin/users/${user.id}`);
                    this.modalUser = res.user;
                } catch (e) {
                    this.showToast(e.message, 'error');
                    return;
                }
            } else {
                this.modalUser = user;
                this.form = user
                    ? { name: user.name, email: user.email, role: user.role, plan: user.plan ?? 'free', password: '' }
                    : { name:'', email:'', password:'', role:'user', plan:'free' };
            }
            this.modalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // ── Save (add / edit) ──────────────────────────────────────────────
        async saveUser() {
            try {
                if (this.modalMode === 'edit') {
                    const payload = { name: this.form.name, email: this.form.email, role: this.form.role };
                    const res = await this.api(`/admin/users/${this.modalUser.id}`, 'PUT', payload);
                    const idx = this.users.findIndex(u => u.id === this.modalUser.id);
                    if (idx !== -1) this.users.splice(idx, 1, res.user);
                    this.showToast(res.message);
                } else {
                    const res = await this.api('/admin/users', 'POST', this.form);
                    this.showToast(res.message);
                    this.page = 1;
                    await this.fetchUsers();
                }
                this.modalOpen = false;
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Toggle status ──────────────────────────────────────────────────
        async toggleStatus(u) {
            try {
                const res = await this.api(`/admin/users/${u.id}/toggle-status`, 'PATCH');
                u.status = res.status;
                this.showToast(res.message);
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Bulk actions ───────────────────────────────────────────────────
        toggleAll(e) {
            this.selected = e.target.checked ? this.users.map(u => u.id) : [];
        },

        async bulkBan() {
            try {
                const res = await this.api('/admin/users/bulk-ban', 'POST', { ids: this.selected });
                this.selected.forEach(id => {
                    const u = this.users.find(x => x.id === id);
                    if (u) u.status = 'banned';
                });
                this.selected = [];
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        async bulkDelete() {
            if (!confirm('Xóa các tài khoản đã chọn?')) return;
            try {
                const res = await this.api('/admin/users/bulk-delete', 'POST', { ids: this.selected });
                this.users = this.users.filter(u => !this.selected.includes(u.id));
                this.totalItems -= this.selected.length;
                this.selected = [];
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Filters reset ──────────────────────────────────────────────────
        resetFilters() {
            this.search = '';
            this.filterRole = '';
            this.filterPlan = '';
            this.filterStatus = '';
        },

        // ── Toast ──────────────────────────────────────────────────────────
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