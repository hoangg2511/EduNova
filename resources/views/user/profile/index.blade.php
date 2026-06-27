@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân - EduNova')

@section('content')
<div class="space-y-8">
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
            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-black text-4xl shadow-lg">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-black text-slate-900">{{ auth()->user()->name }}</h3>
                <p class="text-slate-500 text-sm mt-1">{{ auth()->user()->email }}</p>
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
                    <input type="text" value="{{ auth()->user()->name }}" disabled class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold disabled:opacity-60">
                </div>
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest block mb-2">Email</label>
                    <input type="email" value="{{ auth()->user()->email }}" disabled class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold disabled:opacity-60">
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
        <button class="flex-1 px-6 py-3 bg-primary-600 text-white rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-primary-700 transition-all flex items-center justify-center gap-2">
            <i data-lucide="edit" class="w-4 h-4"></i>
            Chỉnh sửa hồ sơ
        </button>
        <button class="flex-1 px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
            <i data-lucide="lock" class="w-4 h-4"></i>
            Đổi mật khẩu
        </button>
    </div>
</div>
@endsection
