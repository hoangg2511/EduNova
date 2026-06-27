<header class="h-14 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center px-5 justify-between z-30 shrink-0">
    
    {{-- LEFT: Menu + Breadcrumb --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen"
            class="w-[34px] h-[34px] rounded-xl border border-slate-200 flex items-center justify-center
                   text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>

        <!-- <div class="flex items-center gap-1.5 text-sm text-slate-400">
            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
            <span>EduNova</span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-40"></i>
            <span class="text-slate-700 font-medium">{{ $pageTitle ?? 'Dashboard' }}</span>
        </div> -->
    </div>

    {{-- RIGHT: Search + Streak + Plan + Notif + User --}}
    @auth
    @php $plan = auth()->user()->currentPlan(); @endphp
    <div class="flex items-center gap-2">


        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        {{-- Streak --}}
        <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-amber-50 border border-amber-200
                    rounded-full text-xs font-semibold text-amber-700"
             title="Chuỗi học liên tiếp">
            <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-500"></i>
            <span x-text="(typeof streakDays !== 'undefined' ? streakDays : {{ auth()->user()->streak_days }}) + ' ngày'"></span>
        </div>

        {{-- Plan + Tokens --}}
        <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200
                    rounded-full text-xs text-slate-500">
            <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
            <span class="font-semibold text-slate-700">{{ $plan->name ?? 'Free' }}</span>
            <span class="text-slate-300">·</span>
            <span>{{ number_format($plan->token_limit ?? 0) }} tokens</span>
        </div>

        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        {{-- Notification --}}
        @include('layouts.notification') 

        {{-- Avatar + Name --}}
        <div class="flex items-center gap-2 cursor-pointer group">
            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center
                        font-semibold text-sm group-hover:ring-2 ring-indigo-200 transition-all">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="hidden sm:block">
                <p class="text-xs font-semibold text-slate-700 leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-slate-400 leading-tight">Học viên</p>
            </div>
        </div>

    </div>
    @endauth
</header>