@extends('layouts.admin')
@section('title', 'Dashboard - Admin EduNova')

@section('content')
<div class="space-y-8" x-data="{}">

    {{-- ── Header ── --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Tổng quan hệ thống</h1>
            <p class="text-slate-500 text-sm mt-1">Số liệu toàn nền tảng EduNova, cập nhật theo thời gian thực</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-full text-sm font-semibold text-emerald-700">
                <i data-lucide="users" class="w-4 h-4 text-emerald-500"></i>
                {{ number_format($newUsersToday) }} người dùng mới hôm nay
            </div>
        </div>
    </div>

    {{-- ── Cảnh báo cần xử lý ── --}}
    @if($pendingDocumentsCount > 0 || $expiringSoonCount > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($pendingDocumentsCount > 0)
        <a href="{{ route('admin.documents.index', ['status' => 'pending']) }}"
           class="flex items-center gap-4 p-4 rounded-2xl bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-all">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-black text-amber-800">{{ $pendingDocumentsCount }} tài liệu đang chờ duyệt</p>
                <p class="text-xs text-amber-600">Bấm để xem và xử lý ngay</p>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-amber-500"></i>
        </a>
        @endif
        @if($expiringSoonCount > 0)
        <a href="{{ route('admin.subscriptions.index', ['filter' => 'expiring']) }}"
           class="flex items-center gap-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-all">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                <i data-lucide="alarm-clock" class="w-5 h-5 text-rose-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-black text-rose-800">{{ $expiringSoonCount }} gói sắp hết hạn (3 ngày tới)</p>
                <p class="text-xs text-rose-600">Xem danh sách thuê bao</p>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-rose-500"></i>
        </a>
        @endif
    </div>
    @endif

    {{-- ── Stat cards ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="frosted-card p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-blue-100 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($totalUsers) }}</p>
                <p class="text-xs text-slate-500">Tổng người dùng (+{{ $newUsersThisWeek }} tuần này)</p>
            </div>
        </div>
        <div class="frosted-card p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="wallet" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($revenueThisMonth) }}₫</p>
                <p class="text-xs text-slate-500">Doanh thu tháng này</p>
            </div>
        </div>
        <div class="frosted-card p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-violet-100 flex items-center justify-center shrink-0">
                <i data-lucide="crown" class="w-5 h-5 text-violet-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($activeSubscriptionsCount) }}</p>
                <p class="text-xs text-slate-500">Thuê bao đang hoạt động</p>
            </div>
        </div>
        <div class="frosted-card p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-pink-100 flex items-center justify-center shrink-0">
                <i data-lucide="clipboard-check" class="w-5 h-5 text-pink-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">{{ number_format($totalExams) }}</p>
                <p class="text-xs text-slate-500">{{ number_format($examAttemptsToday) }} lượt thi hôm nay</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── LEFT: 2 cột chính ── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Biểu đồ doanh thu 7 ngày ── --}}
            <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-primary-600"></i>
                        Doanh thu 7 ngày gần nhất
                    </h3>
                    <span class="text-xs text-slate-400">Tổng: {{ number_format($revenueAllTime) }}₫</span>
                </div>
                <div class="flex items-end justify-between gap-3 h-40">
                    @foreach($dailyRevenue as $day)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-500">{{ number_format($day['amount'] / 1000) }}k</span>
                        <div class="w-full bg-primary-100 rounded-lg relative" style="height: 100px;">
                            <div class="absolute bottom-0 left-0 w-full bg-primary-500 rounded-lg transition-all"
                                 style="height: {{ $maxDailyRevenue > 0 ? max(4, round(($day['amount'] / $maxDailyRevenue) * 100)) : 4 }}%;"></div>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $day['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tài liệu chờ duyệt ── --}}
            <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                        Tài liệu chờ duyệt
                    </h3>
                    <a href="{{ route('admin.documents.index') }}" class="text-xs font-semibold text-primary-600 hover:underline">Xem tất cả →</a>
                </div>

                @if($pendingDocuments->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-6">Không có tài liệu nào đang chờ duyệt 🎉</p>
                @else
                    <div class="space-y-2">
                        @foreach($pendingDocuments as $document)
                        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-all">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                style="background:{{ $document->color ?? '#64748b' }}15">
                                <i data-lucide="{{ $document->icon ?? 'file' }}" class="w-4 h-4" style="color:{{ $document->color ?? '#64748b' }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $document->name }}</p>
                                <p class="text-xs text-slate-400">{{ $document->uploader->name ?? 'Ẩn danh' }} · {{ $document->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <a href="{{ route('admin.documents.index', $document) }}"
                               class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-primary-50 text-primary-700 hover:bg-primary-100">
                                Duyệt →
                            </a>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Lượt làm bài thi gần đây ── --}}
            <!-- <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="w-4 h-4 text-emerald-600"></i>
                        Lượt làm bài thi gần đây (toàn hệ thống)
                    </h3>
                </div>

                @if($recentExamAttempts->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-6">Chưa có lượt thi nào.</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentExamAttempts as $attempt)
                        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-all">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
                                {{ $attempt->passed ? 'bg-emerald-50' : 'bg-red-50' }}">
                                <i data-lucide="{{ $attempt->passed ? 'check' : 'x' }}" class="w-4 h-4 {{ $attempt->passed ? 'text-emerald-600' : 'text-red-500' }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $attempt->exam->title ?? 'Bài thi' }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $attempt->exam->user->name ?? 'N/A' }} · {{ $attempt->candidate_name ?? 'Ẩn danh' }} · {{ $attempt->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <span class="text-sm font-black {{ $attempt->passed ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $attempt->score }}%
                            </span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div> -->
        </div>

        {{-- ── RIGHT: sidebar phụ ── --}}
        <div class="space-y-6">

            {{-- Người dùng mới ── --}}
            <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-4 h-4 text-blue-600"></i>
                        Người dùng mới
                    </h3>
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-primary-600 hover:underline">Xem tất cả →</a>
                </div>

                @if($recentUsers->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-6">Chưa có người dùng nào.</p>
                @else
                    <div class="space-y-3">
                        @foreach($recentUsers as $u)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center shrink-0 text-xs font-black text-primary-700">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-400 truncate">{{ $u->email }}</p>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ $u->created_at->diffForHumans() }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Thuê bao trả phí gần đây ── --}}
            <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i>
                        Thuê bao trả phí gần đây
                    </h3>
                </div>

                @if($recentSubscriptions->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-6">Chưa có giao dịch nào.</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentSubscriptions as $subscription)
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-700 truncate">{{ $subscription->user->name ?? 'N/A' }}</p>
                                <p class="text-[11px] text-slate-400">{{ $subscription->plan->name ?? '' }} · {{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="text-sm font-black text-emerald-600">
                                +{{ number_format($subscription->plan->price ?? 0) }}₫
                            </span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Coin trong ngày ── --}}
            <div class="frosted-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="coins" class="w-4 h-4 text-yellow-500"></i>
                        Coin hôm nay
                    </h3>
                </div>
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="font-black text-emerald-600">+{{ number_format($coinsIssuedToday) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Đã phát hành</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="font-black text-red-500">-{{ number_format($coinsSpentToday) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Đã sử dụng</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection