{{-- resources/views/user/subscriptions/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Nâng cấp tài khoản')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    {{-- ── Gói đang dùng ── --}}
    @if($currentSub)
    <div class="mb-10 p-6 rounded-2xl border-2 border-indigo-200 bg-indigo-50 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-widest mb-0.5">Gói hiện tại</p>
                <p class="text-xl font-bold text-slate-800">{{ $currentSub->plan->name }}</p>
                <p class="text-sm text-slate-500">
                    @if($currentSub->ends_at)
                        Hết hạn: <span class="font-semibold text-slate-700">{{ $currentSub->ends_at->format('d/m/Y') }}</span>
                        — còn <span class="font-semibold text-indigo-600">{{ $currentSub->ends_at->diffInDays(now()) }} ngày</span>
                    @else
                        <span class="text-emerald-600 font-semibold">Không giới hạn thời gian</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-3 text-sm">
            <div class="text-center px-4 py-2 bg-white rounded-xl border border-indigo-100">
                <p class="font-bold text-slate-800">{{ $currentSub->plan->token_limit > 0 ? number_format($currentSub->plan->token_limit) : '∞' }}</p>
                <p class="text-xs text-slate-500">Token AI</p>
            </div>
            <div class="text-center px-4 py-2 bg-white rounded-xl border border-indigo-100">
                <p class="font-bold text-slate-800">{{ $currentSub->plan->knowledge_limit > 0 ? $currentSub->plan->knowledge_limit : '∞' }}</p>
                <p class="text-xs text-slate-500">Bài kiến thức</p>
            </div>
            <div class="text-center px-4 py-2 bg-white rounded-xl border border-indigo-100">
                <p class="font-bold text-slate-800">{{ $currentSub->plan->download_limit > 0 ? $currentSub->plan->download_limit : '∞' }}</p>
                <p class="text-xs text-slate-500">Tải xuống</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Header ── --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-slate-800 mb-3">Chọn gói phù hợp với bạn</h1>
        <p class="text-slate-500 text-lg">Nâng cấp để mở khoá toàn bộ tính năng học tập</p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-6 px-5 py-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ session('warning') }}
        </div>
    @endif

    {{-- ── Pricing Cards ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
        @foreach($plans as $plan)
        <div class="relative rounded-2xl border-2 bg-white p-8 flex flex-col transition-all duration-300 hover:shadow-xl hover:-translate-y-1
            {{ $plan->is_featured ? 'border-indigo-500 shadow-lg shadow-indigo-100' : 'border-slate-200' }}
            {{ $currentPlan->id === $plan->id ? 'ring-2 ring-offset-2 ring-indigo-400' : '' }}">

            {{-- Badge nổi bật --}}
            @if($plan->is_featured)
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                <span class="bg-indigo-600 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow">
                    ⭐ Phổ biến nhất
                </span>
            </div>
            @endif

            {{-- Badge đang dùng --}}
            @if($currentPlan->id === $plan->id)
            <div class="absolute top-4 right-4">
                <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full">
                    ✓ Đang dùng
                </span>
            </div>
            @endif

            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-800 mb-1">{{ $plan->name }}</h2>
                <p class="text-slate-500 text-sm">{{ $plan->description }}</p>
            </div>

            <div class="mb-6">
                @if($plan->isFree())
                    <span class="text-4xl font-black text-slate-800">Miễn phí</span>
                @else
                    <span class="text-4xl font-black text-slate-800">{{ number_format($plan->price, 0, ',', '.') }}đ</span>
                    <span class="text-slate-400 text-sm">/tháng</span>
                @endif
            </div>

            <ul class="space-y-3 mb-8 flex-1">
                @foreach($plan->features ?? [] as $feature)
                <li class="flex items-center gap-2.5 text-sm text-slate-600">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            <form action="{{ route('user.subscriptions.subscribe') }}" method="POST">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <button type="submit"
                    class="w-full py-3 rounded-xl font-semibold text-sm transition-all
                    {{ $currentPlan->id === $plan->id
                        ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                        : ($plan->is_featured
                            ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200'
                            : 'bg-slate-800 text-white hover:bg-slate-700') }}"
                    {{ $currentPlan->id === $plan->id ? 'disabled' : '' }}>
                    {{ $currentPlan->id === $plan->id ? 'Gói hiện tại' : ($plan->isFree() ? 'Dùng miễn phí' : 'Đăng ký ngay →') }}
                </button>
            </form>
        </div>
        @endforeach
    </div>

    {{-- ── Bảng so sánh ── --}}
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-800 text-center mb-8">So sánh chi tiết các gói</h2>
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-6 py-4 font-semibold text-slate-600 w-1/3">Tính năng</th>
                        @foreach($plans as $plan)
                        <th class="text-center px-6 py-4 font-bold {{ $plan->is_featured ? 'text-indigo-600 bg-indigo-50' : 'text-slate-800' }}">
                            {{ $plan->name }}
                            @if($plan->is_featured)
                                <span class="ml-1 text-xs font-normal text-indigo-400">⭐</span>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                    $rows = [
                        ['label' => 'Giá', 'key' => 'price_display'],
                        ['label' => 'Token AI / tháng', 'key' => 'token_limit'],
                        ['label' => 'Lộ trình kiến thức', 'key' => 'knowledge_limit'],
                        ['label' => 'Tải xuống / tháng', 'key' => 'download_limit'],
                        ['label' => 'FlashCards', 'key' => 'fc'],
                        ['label' => 'Ôn tập AI', 'key' => 'ai_study'],
                        ['label' => 'Export PDF/Excel', 'key' => 'export'],
                        ['label' => 'Hỗ trợ ưu tiên', 'key' => 'support'],
                    ];
                    $matrix = [
                        'free'    => ['price_display'=>'Miễn phí','token_limit'=>'10,000','knowledge_limit'=>'1','download_limit'=>'3','fc'=>'Cơ bản','ai_study'=>false,'export'=>false,'support'=>false],
                        'pro'     => ['price_display'=>'99.000đ/tháng','token_limit'=>'100,000','knowledge_limit'=>'5','download_limit'=>'50','fc'=>'Nâng cao','ai_study'=>true,'export'=>false,'support'=>false],
                        'premium' => ['price_display'=>'199.000đ/tháng','token_limit'=>'Không giới hạn','knowledge_limit'=>'Không giới hạn','download_limit'=>'Không giới hạn','fc'=>'Nâng cao','ai_study'=>true,'export'=>true,'support'=>true],
                    ];
                    @endphp

                    @foreach($rows as $i => $row)
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                        <td class="px-6 py-4 font-medium text-slate-700">{{ $row['label'] }}</td>
                        @foreach($plans as $plan)
                        @php $val = $matrix[$plan->slug][$row['key']] ?? null; @endphp
                        <td class="text-center px-6 py-4 {{ $plan->is_featured ? 'bg-indigo-50/40' : '' }}">
                            @if(is_bool($val))
                                @if($val)
                                    <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-slate-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                @endif
                            @else
                                <span class="text-slate-700 font-medium">{{ $val }}</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── FAQ ngắn ── --}}
    <div class="max-w-2xl mx-auto text-center">
        <p class="text-slate-500 text-sm">
            Thanh toán an toàn qua <strong>Stripe</strong> • Huỷ bất cứ lúc nào • Hỗ trợ: 
            <a href="mailto:support@edunova.vn" class="text-indigo-600 hover:underline">support@edunova.vn</a>
        </p>
    </div>
</div>
@endsection