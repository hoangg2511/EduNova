@extends('layouts.app')

@section('title', 'Dashboard - EduNova')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- ══════════════════════════════════════
         HERO GREETING
    ══════════════════════════════════════ --}}
    <div class="bg-white border-b border-slate-100 px-6 md:px-10 py-8">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-1">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-teal-600">EduNova · Trang tổng quan</p>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight leading-none">
                    Chào, {{ auth()->user()->name }}
                    <span class="inline-block ml-2 text-2xl">👋</span>
                </h1>
                <p class="text-sm text-slate-500 font-medium pt-1">
                    Hôm nay là <span class="text-slate-700 font-semibold">{{ now()->translatedFormat('l, d/m/Y') }}</span>
                    · Tiếp tục học thôi!
                </p>
            </div>

            {{-- Quick action --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('user.exams.store') }}"
                   class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-sm font-bold rounded-2xl hover:bg-slate-700 transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Tạo bài thi
                </a>
                <a href="{{ route('user.documents.upload') }}"
                   class="flex items-center gap-2 px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-bold rounded-2xl hover:bg-slate-50 transition-all">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Tải tài liệu
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 md:px-10 py-8 space-y-8">

        {{-- ══════════════════════════════════════
             STAT STRIP
        ══════════════════════════════════════ --}}
        @php
        $stats = [
            [
                'label'    => 'Bài thi đã tạo',
                'value'    => $totalExams ?? 0,
                'sub'      => '+2 tuần này',
                'icon'     => 'file-text',
                'accent'   => 'slate',
                'progress' => null,
            ],
            [
                'label'    => 'Lượt làm bài',
                'value'    => $totalAttempts ?? 0,
                'sub'      => 'Tổng lượt nộp',
                'icon'     => 'users',
                'accent'   => 'teal',
                'progress' => null,
            ],
            [
                'label'    => 'Tỉ lệ đậu',
                'value'    => ($passRate ?? 0) . '%',
                'sub'      => 'Trung bình',
                'icon'     => 'target',
                'accent'   => 'emerald',
                'progress' => $passRate ?? 0,
            ],
            [
                'label'    => 'Tài liệu',
                'value'    => $totalDocs ?? 0,
                'sub'      => 'Đã tải lên',
                'icon'     => 'book-open',
                'accent'   => 'violet',
                'progress' => null,
            ],
        ];
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($stats as $stat)
            <div class="bg-white border border-slate-100 rounded-3xl p-5 flex flex-col gap-4 hover:border-slate-200 hover:shadow-sm transition-all">
                <div class="flex items-center justify-between">
                    <div class="w-9 h-9 rounded-xl
                        @if($stat['accent'] === 'teal')   bg-teal-50
                        @elseif($stat['accent'] === 'emerald') bg-emerald-50
                        @elseif($stat['accent'] === 'violet')  bg-violet-50
                        @else bg-slate-100 @endif
                        flex items-center justify-center">
                        <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4
                            @if($stat['accent'] === 'teal')    text-teal-600
                            @elseif($stat['accent'] === 'emerald') text-emerald-600
                            @elseif($stat['accent'] === 'violet')  text-violet-600
                            @else text-slate-600 @endif"></i>
                    </div>

                    {{-- Mini progress ring nếu có --}}
                    @if($stat['progress'] !== null)
                    <div class="relative w-9 h-9">
                        <svg class="w-9 h-9 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#f1f5f9" stroke-width="3"/>
                            <circle cx="18" cy="18" r="14" fill="none"
                                stroke="#10b981" stroke-width="3"
                                stroke-linecap="round"
                                stroke-dasharray="{{ 2 * pi() * 14 }}"
                                stroke-dashoffset="{{ 2 * pi() * 14 * (1 - $stat['progress'] / 100) }}"
                                style="transform-origin:18px 18px"/>
                        </svg>
                    </div>
                    @endif
                </div>

                <div>
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ $stat['value'] }}</div>
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ $stat['label'] }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $stat['sub'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════
             MAIN 2-COL
        ══════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Cột trái: Hoạt động gần đây (2/3) ── --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">Hoạt động gần đây</h2>
                    <a href="#" class="text-xs font-bold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                        Xem tất cả <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="bg-white border border-slate-100 rounded-3xl overflow-hidden">
                    @if($activities->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">Chưa có hoạt động gần đây.</div>
                    @endif

                    @foreach($activities as $act)
                    <div class="flex items-center gap-4 px-6 py-4
                        @if(!$loop->last) border-b border-slate-50 @endif
                        hover:bg-slate-50 transition-colors cursor-pointer group">

                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                            @if($act['color'] === 'teal')   bg-teal-50
                            @elseif($act['color'] === 'violet') bg-violet-50
                            @else bg-slate-100 @endif">
                            <i data-lucide="{{ $act['icon'] }}" class="w-4 h-4
                                @if($act['color'] === 'teal')   text-teal-600
                                @elseif($act['color'] === 'violet') text-violet-600
                                @else text-slate-600 @endif"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $act['title'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $act['meta'] }}</p>
                        </div>

                        <i data-lucide="chevron-right"
                           class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition-colors"></i>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Cột phải: Quick links + Tiến độ (1/3) ── --}}
            <div class="space-y-4">

                {{-- Quick links --}}
                <div>
                    <h2 class="text-base font-black text-slate-900 uppercase tracking-tight mb-4">Truy cập nhanh</h2>
                    <div class="space-y-2">
                        @php
                        $links = [
                            ['label' => 'Quản lý bài thi',   'icon' => 'file-text',   'route' => 'user.exams',     'color' => 'slate'],
                            ['label' => 'Thư viện tài liệu', 'icon' => 'book-open',   'route' => 'user.documents', 'color' => 'violet'],
                            ['label' => 'Lịch học',          'icon' => 'calendar',    'route' => 'user.calendars',        'color' => 'teal'],
                            //{{--['label' => 'Kết quả thi',       'icon' => 'bar-chart-2', 'route' => 'user.exams.attempts.store',  'color' => 'emerald'],--}}
                        ];
                        @endphp

                        @foreach($links as $link)
                        <a href="{{ route($link['route']) }}"
                           class="flex items-center gap-3 px-4 py-3 bg-white border border-slate-100 rounded-2xl hover:border-slate-200 hover:shadow-sm transition-all group">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center
                                @if($link['color'] === 'violet')  bg-violet-50
                                @elseif($link['color'] === 'teal') bg-teal-50
                                @elseif($link['color'] === 'emerald') bg-emerald-50
                                @else bg-slate-100 @endif">
                                <i data-lucide="{{ $link['icon'] }}" class="w-3.5 h-3.5
                                    @if($link['color'] === 'violet')  text-violet-600
                                    @elseif($link['color'] === 'teal') text-teal-600
                                    @elseif($link['color'] === 'emerald') text-emerald-600
                                    @else text-slate-600 @endif"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700 flex-1">{{ $link['label'] }}</span>
                            <i data-lucide="arrow-right"
                               class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-500 group-hover:translate-x-0.5 transition-all"></i>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Tiến độ bài thi --}}
                <div class="bg-white border border-slate-100 rounded-3xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Bài thi gần nhất</p>
                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">LIVE</span>
                    </div>

                    @if($recentExams->isEmpty())
                        <div class="p-6 text-sm text-slate-500">Chưa có bài thi gần đây.</div>
                    @else
                    <div class="space-y-4">
                        @foreach($recentExams as $ex)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold text-slate-800 truncate max-w-[160px]">{{ $ex['name'] }}</p>
                                <span class="text-[10px] font-black
                                    @if($ex['pass'] >= 80) text-emerald-600
                                    @elseif($ex['pass'] >= 60) text-amber-600
                                    @else text-rose-500 @endif">
                                    {{ $ex['pass'] }}% đậu
                                </span>
                            </div>
                            {{-- Progress bar: lượt làm --}}
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full
                                    @if($ex['pass'] >= 80) bg-emerald-400
                                    @elseif($ex['pass'] >= 60) bg-amber-400
                                    @else bg-rose-400 @endif"
                                    style="width: {{ min(100, round($ex['attempts'] / $ex['max'] * 100)) }}%">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400">{{ $ex['attempts'] }}/{{ $ex['max'] }} lượt</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════
             BẢNG KẾT QUẢ GẦN ĐÂY
        ══════════════════════════════════════ --}}
        <div class="space-y-4">
            {{--<div class="flex items-center justify-between">
                <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">Kết quả nộp bài gần đây</h2>
                <a href="{{ route('user.exams.attempts') }}"
                   class="text-xs font-bold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                    Xem tất cả <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>--}}

            <div class="bg-white border border-slate-100 rounded-3xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-left px-6 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Học viên</th>
                                <th class="text-left px-4 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Bài thi</th>
                                <th class="text-center px-4 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Điểm</th>
                                <th class="text-center px-4 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Kết quả</th>
                                <th class="text-right px-6 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attempts as $att)
                            <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center
                                                    text-[10px] font-black text-slate-600 shrink-0">
                                            {{ strtoupper(mb_substr($att['name'], 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-slate-800 text-sm">{{ $att['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-500 text-sm">{{ $att['exam'] }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-sm font-black
                                        @if($att['score'] >= 80) text-emerald-600
                                        @elseif($att['score'] >= 60) text-amber-600
                                        @else text-rose-500 @endif">
                                        {{ $att['score'] }}%
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($att['passed'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                        <i data-lucide="check" class="w-3 h-3"></i> Đậu
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black px-2.5 py-1 rounded-full bg-rose-50 text-rose-600">
                                        <i data-lucide="x" class="w-3 h-3"></i> Rớt
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-slate-400 font-medium">{{ $att['time'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">Chưa có kết quả nộp bài nào.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /max-w --}}
</div>{{-- /min-h-screen --}}
@endsection