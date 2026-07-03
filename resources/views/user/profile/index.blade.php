@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân - EduNova')

@section('content')
<div class="space-y-8" x-data="profileApp({
        name: @js(auth()->user()->name),
        email: @js(auth()->user()->email),
        avatarUrl: @js(auth()->user()->avatar_url),
        role: @js(auth()->user()->role),
        joinedAt: @js(auth()->user()->created_at->format('d/m/Y')),
    })" x-init="init()" x-cloak>

    <!-- Header -->
    <div class="space-y-2">
        <span class="text-[10px] font-black uppercase text-primary-600 tracking-widest">Tài khoản</span>
        <h2 class="text-4xl font-black text-slate-900 leading-tight uppercase tracking-tight">Hồ sơ cá nhân</h2>
        <p class="text-slate-500 text-sm font-medium">Quản lý thông tin tài khoản của bạn</p>
    </div>

    <!-- Profile Card -->
    <div class="frosted-card p-8 space-y-8">
        <!-- Profile Header -->
        <div class="flex items-center gap-6 pb-8 border-b border-slate-200">
            <div class="relative group cursor-pointer" @click="$refs.avatarInput.click()">
                <template x-if="avatarUrl">
                    <img :src="avatarUrl" alt="avatar"
                        class="w-24 h-24 rounded-2xl object-cover shadow-lg border border-slate-200">
                </template>
                <template x-if="!avatarUrl">
                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-black text-4xl shadow-lg">
                        <span x-text="name.charAt(0).toUpperCase()"></span>
                    </div>
                </template>

                {{-- Overlay khi hover --}}
                <div class="absolute inset-0 rounded-2xl bg-black/50 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center">
                    <i data-lucide="camera" class="w-6 h-6 text-white"></i>
                </div>

                {{-- Loading overlay khi đang upload --}}
                <div x-show="avatarUploading" class="absolute inset-0 rounded-2xl bg-black/60 flex items-center justify-center">
                    <i data-lucide="loader-2" class="w-6 h-6 text-white animate-spin"></i>
                </div>

                <input type="file" x-ref="avatarInput" accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadAvatar($event)">
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-black text-slate-900" x-text="name"></h3>
                <p class="text-slate-500 text-sm mt-1" x-text="email"></p>
                <div class="flex items-center gap-2 mt-3">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    <span class="text-xs font-bold text-emerald-700">Hoạt động</span>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest block mb-2">Họ và tên</label>
                    <input type="text" :value="name" disabled class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold disabled:opacity-60">
                </div>
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest block mb-2">Email</label>
                    <input type="email" :value="email" disabled class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold disabled:opacity-60">
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest block mb-2">Vai trò</label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold">
                        <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold inline-block">
                            {{ auth()->user()->role === 'admin' ? 'Quản trị viên' : 'Học viên' }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest block mb-2">Ngày tham gia</label>
                    <input type="text" value="{{ auth()->user()->created_at->format('d/m/Y') }}" disabled class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold disabled:opacity-60">
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="frosted-card p-6 text-center">
            <div class="w-12 h-12 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="book-open" class="w-6 h-6 text-primary-600"></i>
            </div>
            <div class="text-3xl font-black text-slate-900">5</div>
            <p class="text-xs text-slate-500 mt-2 font-semibold">Khóa học đang học</p>
        </div>

        <div class="frosted-card p-6 text-center">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <div class="text-3xl font-black text-slate-900">2</div>
            <p class="text-xs text-slate-500 mt-2 font-semibold">Khóa học hoàn thành</p>
        </div>

        <div class="frosted-card p-6 text-center">
            <div class="w-12 h-12 bg-violet-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="flame" class="w-6 h-6 text-violet-600"></i>
            </div>
            <div class="text-3xl font-black text-slate-900">15</div>
            <p class="text-xs text-slate-500 mt-2 font-semibold">Ngày liên tiếp học</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-4">
        <button @click="openEditModal()" class="flex-1 px-6 py-3 bg-primary-600 text-white rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-primary-700 transition-all flex items-center justify-center gap-2">
            <i data-lucide="edit" class="w-4 h-4"></i>
            Chỉnh sửa hồ sơ
        </button>
        <button @click="openPasswordModal()" class="flex-1 px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
            <i data-lucide="lock" class="w-4 h-4"></i>
            Đổi mật khẩu
        </button>
    </div>

    {{-- ═══════════════════════
         MODAL: CHỈNH SỬA HỒ SƠ
    ═══════════════════════ --}}
    <div x-show="showEditModal" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">Chỉnh sửa hồ sơ</h2>
                <button @click="showEditModal = false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Họ và tên</label>
                    <input type="text" x-model="editForm.name"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="editErrors.name" class="mt-1 text-xs text-red-500" x-text="editErrors.name?.[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Email</label>
                    <input type="email" x-model="editForm.email"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="editErrors.email" class="mt-1 text-xs text-red-500" x-text="editErrors.email?.[0]"></p>
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="showEditModal = false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="saveProfile()" :disabled="editSaving"
                    class="flex-1 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-all">
                    <span x-show="!editSaving">Lưu thay đổi</span>
                    <span x-show="editSaving">Đang lưu...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════
         MODAL: ĐỔI MẬT KHẨU
    ═══════════════════════ --}}
    <div x-show="showPasswordModal" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showPasswordModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">Đổi mật khẩu</h2>
                <button @click="showPasswordModal = false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mật khẩu hiện tại</label>
                    <input type="password" x-model="passwordForm.current_password"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="passwordErrors.current_password" class="mt-1 text-xs text-red-500" x-text="passwordErrors.current_password?.[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mật khẩu mới</label>
                    <input type="password" x-model="passwordForm.password"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="passwordErrors.password" class="mt-1 text-xs text-red-500" x-text="passwordErrors.password?.[0]"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Xác nhận mật khẩu mới</label>
                    <input type="password" x-model="passwordForm.password_confirmation"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="showPasswordModal = false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="savePassword()" :disabled="passwordSaving"
                    class="flex-1 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-all">
                    <span x-show="!passwordSaving">Đổi mật khẩu</span>
                    <span x-show="passwordSaving">Đang xử lý...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[70] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="toast.type==='error' ? 'bg-red-500' : 'bg-emerald-600'"
        style="display:none;">
        <i :data-lucide="toast.type==='error' ? 'alert-circle' : 'check-circle'" class="w-4 h-4"></i>
        <span x-text="toast.message"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileApp(config) {
    return {
        name: config.name,
        email: config.email,
        avatarUrl: config.avatarUrl,
        avatarUploading: false,

        showEditModal: false,
        editForm: { name: '', email: '' },
        editErrors: {},
        editSaving: false,

        showPasswordModal: false,
        passwordForm: { current_password: '', password: '', password_confirmation: '' },
        passwordErrors: {},
        passwordSaving: false,

        toast: { show: false, message: '', type: 'success' },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        openEditModal() {
            this.editForm = { name: this.name, email: this.email };
            this.editErrors = {};
            this.showEditModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async saveProfile() {
            this.editErrors = {};
            if (!this.editForm.name.trim()) {
                this.editErrors.name = ['Vui lòng nhập họ và tên.'];
                return;
            }

            this.editSaving = true;
            try {
                const response = await fetch('{{ route("user.profile.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.editForm),
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    this.name  = result.user.name;
                    this.email = result.user.email;
                    this.showEditModal = false;
                    this.showToast('Cập nhật hồ sơ thành công!');
                } else {
                    this.editErrors = result.errors || {};
                    this.showToast(result.message || 'Có lỗi xảy ra.', 'error');
                }
            } catch (e) {
                console.error(e);
                this.showToast('Lỗi kết nối máy chủ!', 'error');
            } finally {
                this.editSaving = false;
            }
        },

        async uploadAvatar(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.showToast('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.', 'error');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.showToast('Ảnh phải nhỏ hơn 2MB.', 'error');
                return;
            }

            this.avatarUploading = true;
            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetch('{{ route("user.profile.avatar") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    this.avatarUrl = result.avatar_url;
                    this.showToast('Cập nhật ảnh đại diện thành công!');
                } else {
                    const errMsg = result.errors ? Object.values(result.errors).flat().join(', ') : result.message;
                    this.showToast(errMsg || 'Upload ảnh thất bại.', 'error');
                }
            } catch (e) {
                console.error(e);
                this.showToast('Lỗi kết nối máy chủ!', 'error');
            } finally {
                this.avatarUploading = false;
                event.target.value = ''; // reset input để chọn lại cùng file vẫn trigger được
            }
        },

        openPasswordModal() {
            this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
            this.passwordErrors = {};
            this.showPasswordModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async savePassword() {
            this.passwordErrors = {};

            if (!this.passwordForm.current_password) {
                this.passwordErrors.current_password = ['Vui lòng nhập mật khẩu hiện tại.'];
                return;
            }
            if (this.passwordForm.password.length < 8) {
                this.passwordErrors.password = ['Mật khẩu mới phải có ít nhất 8 ký tự.'];
                return;
            }
            if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
                this.passwordErrors.password = ['Xác nhận mật khẩu không khớp.'];
                return;
            }

            this.passwordSaving = true;
            try {
                const response = await fetch('{{ route("user.profile.password") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.passwordForm),
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    this.showPasswordModal = false;
                    this.showToast('Đổi mật khẩu thành công!');
                } else {
                    this.passwordErrors = result.errors || {};
                    this.showToast(result.message || 'Đổi mật khẩu thất bại.', 'error');
                }
            } catch (e) {
                console.error(e);
                this.showToast('Lỗi kết nối máy chủ!', 'error');
            } finally {
                this.passwordSaving = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endpush