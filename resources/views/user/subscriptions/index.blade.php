{{-- resources/views/user/subscriptions/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Nâng cấp tài khoản')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10"
     x-data="{
        confirmOpen: false,
        selectedPlan: null,
        openConfirm(plan) {
            this.selectedPlan = plan;
            this.confirmOpen = true;
        },
        closeConfirm() {
            this.confirmOpen = false;
            this.selectedPlan = null;
        },
        submitConfirm() {
            this.$refs['form_' + this.selectedPlan.id].submit();
        }
     }">

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
                @if($currentSub->ends_at)
    Hết hạn: <span class="font-semibold text-slate-700">{{ $currentSub->ends_at->format('d/m/Y') }}</span>
    — 
    @if($currentSub->ends_at->isPast())
        <span class="font-semibold text-red-600">Đã hết hạn</span>
    @else
       còn <span class="font-semibold text-indigo-600">{{ intval(now()->diffInDays($currentSub->ends_at)) }} ngày</span>
    @endif
@else
    <span class="text-emerald-600 font-semibold">Không giới hạn thời gian</span>
@endif
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
    @if(session('info'))
        <div class="mb-6 px-5 py-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-700 font-medium flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            {{ session('info') }}
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

            {{-- Form thật, được submit bằng JS từ modal xác nhận, không submit trực tiếp --}}
            <form action="{{ route('user.subscriptions.subscribe') }}" method="POST" x-ref="form_{{ $plan->id }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            </form>

            <button type="button"
                @if($currentPlan->id !== $plan->id)
                @click="openConfirm({
                    id: {{ $plan->id }},
                    name: @js($plan->name),
                    price: {{ $plan->price }},
                    isFree: @js($plan->isFree()),
                    token_limit: {{ $plan->token_limit }},
                    knowledge_limit: {{ $plan->knowledge_limit }},
                    download_limit: {{ $plan->download_limit }}
                })"
                @endif
                class="w-full py-3 rounded-xl font-semibold text-sm transition-all
                {{ $currentPlan->id === $plan->id
                    ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                    : ($plan->is_featured
                        ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200'
                        : 'bg-slate-800 text-white hover:bg-slate-700') }}"
                {{ $currentPlan->id === $plan->id ? 'disabled' : '' }}>
                {{ $currentPlan->id === $plan->id ? 'Gói hiện tại' : ($plan->isFree() ? 'Dùng miễn phí' : 'Đăng ký ngay →') }}
            </button>
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

    {{-- ══════════ MODAL XÁC NHẬN CHUYỂN GÓI ══════════ --}}
    <div x-show="confirmOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
             x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeConfirm()"></div>

        {{-- Modal box --}}
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6"
             x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.outside="closeConfirm()"
             x-ref="confirmModalBox">

            <template x-if="selectedPlan">
                <div>
                    {{-- Icon cảnh báo --}}
                    <!-- <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div> -->

                    <h3 class="text-lg font-bold text-slate-800 mb-2">
                        
                        Xác nhận chuyển sang gói <span x-text="selectedPlan.name" class="text-indigo-600"></span>?
                    </h3>

                    <p class="text-sm text-slate-500 mb-4">
                        Khi chuyển gói, toàn bộ hạn mức sử dụng của bạn sẽ được
                        <span class="font-semibold text-slate-700">đặt lại (reset) theo gói mới</span>,
                        thay thế hoàn toàn hạn mức của gói hiện tại — kể cả phần bạn chưa dùng hết.
                    </p>

                    {{-- Bảng hạn mức mới --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-5 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Token AI</span>
                            <span class="font-bold text-slate-800"
                                  x-text="selectedPlan.token_limit > 0 ? new Intl.NumberFormat('vi-VN').format(selectedPlan.token_limit) : '∞ Không giới hạn'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Bài kiến thức</span>
                            <span class="font-bold text-slate-800"
                                  x-text="selectedPlan.knowledge_limit > 0 ? selectedPlan.knowledge_limit : '∞ Không giới hạn'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Tải xuống</span>
                            <span class="font-bold text-slate-800"
                                  x-text="selectedPlan.download_limit > 0 ? selectedPlan.download_limit : '∞ Không giới hạn'"></span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400 mb-6" x-show="!selectedPlan.isFree">
                        Bạn sẽ được chuyển đến cổng thanh toán SePay để hoàn tất giao dịch
                        <span class="font-semibold" x-text="new Intl.NumberFormat('vi-VN').format(selectedPlan.price) + 'đ'"></span>.
                    </p>

                    <div class="flex gap-3">
                        <button type="button"
                            @click="closeConfirm()"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-sm bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">
                            Huỷ
                        </button>
                        <button type="button"
                            @click="submitConfirm()"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-sm bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                            Xác nhận chuyển gói
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection