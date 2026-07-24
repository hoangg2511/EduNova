@extends('layouts.app')

@section('title', 'Lộ trình học - EduNova')

@section('content')
<div class="space-y-8">
    <!-- Header -->
   <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="text-[10px] font-black uppercase text-primary-600 tracking-widest">Học tập</span>
            <h2 class="text-4xl font-black text-slate-900 leading-tight uppercase tracking-tight">Lộ trình học</h2>
            <p class="text-slate-500 text-sm font-medium">Danh sách các lộ trình học của bạn</p>
        </div>
        <a href="{{ route('user.knowledge.roadmap') }}" class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-wide hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Tạo lộ trình mới
        </a>
    </div>
    <!-- Filters -->
    <!-- <div class="flex flex-wrap gap-3">
        <button class="px-4 py-2 bg-primary-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-primary-700 transition-all">
            Tất cả
        </button>
        <button class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all">
            Đang học
        </button>
        <button class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all">
            Hoàn thành
        </button>
    </div> -->

    <!-- Courses Grid -->
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($knowledges as $item)
        <div class="frosted-card overflow-hidden hover:shadow-lg transition-all cursor-pointer group">
            <div class="h-32 bg-gradient-to-br from-primary-400 to-primary-600 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 group-hover:opacity-30 transition-opacity">
                    <i data-lucide="book-open" class="w-16 h-16 text-white absolute bottom-2 right-2"></i>
                </div>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <h3 class="font-black text-slate-900 text-lg mb-1 truncate">
                        {{ $item->getMainTopic() }}
                    </h3>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">{{ $item->format }}</p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-500">
                        {{ $item->getSectionsCount() }} chủ đề lớn · {{ $item->getChildTopicsCount() }} chủ đề con
                    </span>
                    <a href="{{ route('user.knowledge.show', $item->id) }}" class="text-primary-600 hover:text-primary-700">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full py-10 text-center">
            <p class="text-slate-500">Bạn chưa có lộ trình nào. Hãy tạo lộ trình đầu tiên!</p>
            <a href="{{ route('user.knowledge.roadmap') }}" class="text-primary-600 font-bold underline">Tạo ngay</a>
        </div>
    @endforelse
</div>
</div>
@endsection
