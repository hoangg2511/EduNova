@extends('layouts.app')

@section('title', 'FlashCards - EduNova')

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* ── Modal ── */
    .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(15,23,42,0.5);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 60;
        display: flex; align-items: center; justify-content: center;
        padding: 1rem;
    }
    .modal-box {
        background: #fff;
        border-radius: 24px;
        width: 100%; max-width: 560px;
        box-shadow: 0 32px 64px -12px rgba(0,0,0,0.18);
        max-height: 92vh;
        overflow-y: auto;
        animation: modalIn 0.28s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes modalIn {
        from { opacity:0; transform: scale(0.9) translateY(20px); }
        to   { opacity:1; transform: scale(1) translateY(0); }
    }

    /* ── Deck card ── */
    .deck-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        overflow: hidden;
        cursor: pointer;
    }
    .deck-card:hover {
        box-shadow: 0 16px 32px -6px rgba(99,102,241,0.12);
        transform: translateY(-4px);
        border-color: #c7d2fe;
    }
    .deck-color-bar { height: 5px; width: 100%; }

    /* ── FlashCard flip ── */
    .card-flip { perspective: 1000px; }
    .card-flip-inner {
        transition: transform 0.55s cubic-bezier(0.4,0,0.2,1);
        transform-style: preserve-3d;
        position: relative;
        height: 100%;
    }
    .card-flip.flipped .card-flip-inner { transform: rotateY(180deg); }
    .card-front, .card-back {
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        width: 100%; height: 100%;
    }
    .card-back { transform: rotateY(180deg); position: absolute; inset: 0; }

    /* ── FC card item ── */
    .fc-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        transition: all 0.25s;
        overflow: hidden;
    }
    .fc-item:hover {
        box-shadow: 0 8px 20px -4px rgba(99,102,241,0.1);
        transform: translateY(-2px);
        border-color: #c7d2fe;
    }

    /* ── Badge ── */
    .fc-badge {
        display: inline-flex; align-items: center;
        padding: 2px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 600; letter-spacing: 0.03em;
    }
    .badge-blue   { background:#eff6ff; color:#1d4ed8; }
    .badge-green  { background:#f0fdf4; color:#15803d; }
    .badge-amber  { background:#fffbeb; color:#b45309; }
    .badge-red    { background:#fef2f2; color:#b91c1c; }
    .badge-purple { background:#f5f3ff; color:#6d28d9; }
    .badge-pink   { background:#fdf2f8; color:#9d174d; }
    .badge-teal   { background:#f0fdfa; color:#0f766e; }
    .badge-slate  { background:#f1f5f9; color:#475569; }

    /* ── Difficulty dot ── */
    .dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
    .dot-easy   { background:#22c55e; }
    .dot-medium { background:#f59e0b; }
    .dot-hard   { background:#ef4444; }

    /* ── Buttons ── */
    .btn-primary { background:#6366f1; color:#fff; transition: all 0.2s; }
    .btn-primary:hover { background:#4f46e5; transform:translateY(-1px); box-shadow:0 4px 14px rgba(99,102,241,0.4); }
    .btn-primary:active { transform:translateY(0); }

    /* ── Tab ── */
    .tab-btn { transition: all 0.2s; border-radius:8px; }
    .tab-btn.active { background:#6366f1; color:#fff; }
    .tab-btn:not(.active):hover { background:#f1f5f9; }

    /* ── Search ── */
    .search-input:focus { outline:none; box-shadow:0 0 0 3px rgba(99,102,241,0.15); border-color:#a5b4fc; }

    /* ── Progress ── */
    .prog-track { height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
    .prog-bar   { height:100%; border-radius:999px; transition: width 0.5s ease; }

    /* ── Study overlay ── */
    .study-overlay {
        position: fixed; inset: 0;
        background: #0f172a;
        z-index: 70;
        display: flex; flex-direction: column;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

    /* ── Color picker ── */
    .color-dot {
        width: 28px; height: 28px; border-radius: 50%;
        cursor: pointer; transition: all 0.2s;
        border: 3px solid transparent;
    }
    .color-dot:hover { transform: scale(1.15); }
    .color-dot.selected { border-color: #1e293b; transform: scale(1.15); }

    /* Deck grid */
    .deck-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

    /* FC grid */
    .fc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 16px; }
</style>
@endpush

@section('content')
<div 
    x-data="fcApp({ 
        decks: {{ Js::from($flashcards ?? []) }},
        activeDeckId: {{ $activeDeckId ?? 'null' }},
        streakDays: {{ auth()->user()->streak_days ?? 0 }}
    })" 
    x-init="init()" 
    x-cloak
> 

    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  VIEW 1 — DANH SÁCH BỘ THẺ (DECKS)       ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="view === 'decks'">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-1">Bộ FlashCards</h1>
                <p class="text-slate-500 text-sm">Tổ chức kiến thức theo từng bộ thẻ</p>
            </div>
            <button @click="openCreateDeck()" class="btn-primary flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm self-start md:self-auto">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Tạo bộ thẻ mới
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="frosted-card p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
                    <i data-lucide="library" class="w-5 h-5 text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800" x-text="decks.length"></p>
                    <p class="text-xs text-slate-500">Bộ thẻ</p>
                </div>
            </div>
            <div class="frosted-card p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-purple-100 flex items-center justify-center shrink-0">
                    <i data-lucide="layers" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800" x-text="totalCards"></p>
                    <p class="text-xs text-slate-500">Tổng thẻ</p>
                </div>
            </div>
            <div class="frosted-card p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800" x-text="totalLearned"></p>
                    <p class="text-xs text-slate-500">Đã thuộc</p>
                </div>
            </div>
            <div class="frosted-card p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
                    <i data-lucide="flame" class="w-5 h-5 text-amber-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800" x-text="streakDays"></p>
                    <p class="text-xs text-slate-500">Ngày liên tiếp</p>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="relative mb-6 max-w-sm">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
            <input type="text" x-model="deckSearch" placeholder="Tìm bộ thẻ..."
                class="search-input w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 transition-all">
        </div>

        {{-- Empty --}}
        <div x-show="filteredDecks.length === 0" class="frosted-card p-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="library" class="w-8 h-8 text-indigo-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-2">Chưa có bộ thẻ nào</h3>
            <p class="text-slate-400 text-sm mb-6">Tạo bộ thẻ đầu tiên để bắt đầu!</p>
            <button @click="openCreateDeck()" class="btn-primary px-5 py-2.5 rounded-xl font-medium text-sm">Tạo ngay</button>
        </div>

        {{-- Deck Grid --}}
        <div class="deck-grid">
            <template x-for="deck in filteredDecks" :key="deck.id">
                <div class="deck-card" @click="openDeck(deck)">
                    {{-- Color bar --}}
                    <div class="deck-color-bar" :style="'background:' + deck.color"></div>

                    <div class="p-5">
                        {{-- Title row --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-3">
                                <!-- <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
                                     :style="'background:' + deck.color + '22'">
                                    <span x-text="deck.emoji || '📚'"></span>
                                </div> -->
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base leading-tight" x-text="deck.name"></h3>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="deck.subject || 'Chưa phân loại'"></p>
                                </div>
                            </div>
                            {{-- Actions --}}
                            <div class="flex items-center gap-1 shrink-0" @click.stop>
                                <button @click="openEditDeck(deck)" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="confirmDeleteDeck(deck)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Description --}}
                        <p x-show="deck.desc" class="text-xs text-slate-500 mb-3 line-clamp-2" x-text="deck.desc"></p>

                        {{-- Stats --}}
                        <div class="flex items-center gap-3 mb-3 text-xs text-slate-500">
                            <span class="flex items-center gap-1">
                                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                <span x-text="(deck.cards || []).length + ' thẻ'"></span>
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span x-text="(deck.cards || []).filter(c=>c.status==='learned').length + ' thuộc'"></span>
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span x-text="(deck.cards || []).filter(c=>c.starred).length"></span>
                            </span>
                        </div>

                        {{-- Progress --}}
                        <div class="prog-track">
                            <div class="prog-bar" :style="'width:' + deckProgress(deck) + '%; background:' + deck.color"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5 text-right" x-text="deckProgress(deck) + '% hoàn thành'"></p>
                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400" x-text="'Tạo ' + formatDate(deck.createdAt)"></span>
                        <span class="text-xs font-semibold text-indigo-600 flex items-center gap-1">
                            Mở bộ thẻ <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>{{-- end view decks --}}


    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  VIEW 2 — CÁC THẺ TRONG BỘ              ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="view === 'cards'">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 mb-6 text-sm">
            <button @click="view='decks'" class="flex items-center gap-1.5 text-slate-500 hover:text-indigo-600 transition-all font-medium">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                Bộ FlashCards
            </button>
            <span class="text-slate-300">/</span>
            <span class="font-semibold text-slate-800" x-text="activeDeck?.name"></span>
        </div>

        {{-- Deck header --}}
        <div class="frosted-card p-6 mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between"
             :style="'border-top: 4px solid ' + (activeDeck?.color || '#6366f1')">
            <div class="flex items-center gap-4">
                <!-- <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl"
                     :style="'background:' + (activeDeck?.color || '#6366f1') + '20'">
                    <span x-text="activeDeck?.emoji || '📚'"></span>
                </div> -->
                <div>
                    <h2 class="text-2xl font-bold text-slate-800" x-text="activeDeck?.name"></h2>
                    <p class="text-sm text-slate-500 mt-0.5" x-text="activeDeck?.desc || 'Không có mô tả'"></p>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="fc-badge badge-purple" x-text="(activeDeck?.cards||[]).length + ' thẻ'"></span>
                        <span class="fc-badge badge-green" x-text="(activeDeck?.cards||[]).filter(c=>c.status==='learned').length + ' đã thuộc'"></span>
                        <span class="fc-badge badge-amber" x-text="(activeDeck?.cards||[]).filter(c=>c.status==='learning').length + ' đang học'"></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="openStudyMode()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-200 text-indigo-600 font-semibold text-sm hover:bg-indigo-50 transition-all">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                    Ôn tập
                </button>
                <button @click="openCreateCard()" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Thêm thẻ
                </button>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="frosted-card p-4 mb-5 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" x-model="cardSearch" placeholder="Tìm kiếm thẻ..."
                    class="search-input w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 transition-all">
            </div>
            <select x-model="filterDifficulty" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Mọi độ khó</option>
                <option value="easy">Dễ</option>
                <option value="medium">Trung bình</option>
                <option value="hard">Khó</option>
            </select>
            <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1 shrink-0">
                <button @click="filterStatus=''" :class="filterStatus==='' ? 'active':''" class="tab-btn px-3 py-1.5 text-xs font-medium">Tất cả</button>
                <button @click="filterStatus='new'" :class="filterStatus==='new' ? 'active':''" class="tab-btn px-3 py-1.5 text-xs font-medium">Mới</button>
                <button @click="filterStatus='learning'" :class="filterStatus==='learning' ? 'active':''" class="tab-btn px-3 py-1.5 text-xs font-medium">Đang học</button>
                <button @click="filterStatus='learned'" :class="filterStatus==='learned' ? 'active':''" class="tab-btn px-3 py-1.5 text-xs font-medium">Đã thuộc</button>
            </div>
            <div class="flex gap-1 shrink-0">
                <button @click="cardView='grid'" :class="cardView==='grid' ? 'bg-indigo-100 text-indigo-600':'text-slate-400'" class="p-2.5 rounded-xl transition-all">
                    <i data-lucide="grid-2x2" class="w-4 h-4"></i>
                </button>
                <button @click="cardView='list'" :class="cardView==='list' ? 'bg-indigo-100 text-indigo-600':'text-slate-400'" class="p-2.5 rounded-xl transition-all">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Count --}}
        <p class="text-sm text-slate-500 mb-4">
            Hiển thị <span class="font-semibold text-slate-700" x-text="filteredCards.length"></span> thẻ
        </p>

        {{-- Empty cards --}}
        <div x-show="filteredCards.length === 0" class="frosted-card p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="layers" class="w-7 h-7 text-indigo-400"></i>
            </div>
            <h3 class="font-semibold text-slate-700 mb-2">Chưa có thẻ nào</h3>
            <p class="text-slate-400 text-sm mb-5">Thêm thẻ đầu tiên vào bộ này!</p>
            <button @click="openCreateCard()" class="btn-primary px-5 py-2.5 rounded-xl font-medium text-sm">Thêm thẻ</button>
        </div>

        {{-- GRID --}}
        <div x-show="filteredCards.length > 0 && cardView === 'grid'" class="fc-grid">
            <template x-for="card in filteredCards" :key="card.id">
                <div class="fc-item">
                    {{-- Card top --}}
                    <div class="p-3 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="dot" :class="'dot-' + card.difficulty"></span>
                            <span class="fc-badge" :class="{
                                'badge-slate': card.status==='new',
                                'badge-amber': card.status==='learning',
                                'badge-green': card.status==='learned'
                            }" x-text="card.status==='new'?'Mới':card.status==='learning'?'Đang học':'Đã thuộc'"></span>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <button @click="toggleStar(card)" :class="card.starred?'text-amber-400':'text-slate-300 hover:text-amber-400'" class="p-1.5 rounded-lg transition-all">
                                <i data-lucide="star" class="w-3.5 h-3.5" :class="card.starred?'fill-amber-400':''"></i>
                            </button>
                            <button @click="openEditCard(card)" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <button @click="confirmDeleteCard(card)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Flip --}}
                    <div class="p-3">
                        <div class="card-flip rounded-xl" style="height:200px; cursor:pointer;"
                             :class="card.flipped?'flipped':''" @click="card.flipped=!card.flipped">
                            <div class="card-flip-inner">
                                <div class="card-front rounded-xl flex flex-col items-center justify-center p-4 text-center"
                                     :style="'background:' + (activeDeck?.color||'#6366f1') + '12; border:1px solid ' + (activeDeck?.color||'#6366f1') + '30'">
                                    <p class="text-xs font-semibold mb-2 uppercase tracking-widest opacity-60" :style="'color:' + (activeDeck?.color||'#6366f1')">Câu hỏi</p>
                                    <p class="text-slate-700 font-semibold text-sm leading-relaxed" x-text="card.front"></p>
                                    <p class="text-xs text-slate-400 mt-3 flex items-center gap-1">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i> lật thẻ
                                    </p>
                                </div>
                                <div class="card-back rounded-xl flex flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
                                    <p class="text-xs font-semibold text-emerald-500 mb-2 uppercase tracking-widest">Đáp án</p>
                                    <p class="text-slate-700 font-semibold text-sm leading-relaxed" x-text="card.back"></p>
                                    <p x-show="card.hint" class="text-xs text-slate-400 mt-2 italic" x-text="card.hint"></p>
                                    <div class="mt-3 flex gap-1.5">
                                        <button @click.stop="markStatus(card,'learning')" class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-medium hover:bg-amber-200 transition-all">Đang học</button>
                                        <button @click.stop="markStatus(card,'learned')" class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-medium hover:bg-emerald-200 transition-all">Đã thuộc ✓</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-3 pb-3 text-right">
                        <p class="text-xs text-slate-400" x-text="'Ôn ' + (card.reviewCount||0) + ' lần'"></p>
                    </div>
                </div>
            </template>
        </div>

        {{-- LIST --}}
        <div x-show="filteredCards.length > 0 && cardView === 'list'" class="space-y-2">
            <template x-for="card in filteredCards" :key="card.id">
                <div class="fc-item flex items-center gap-4 p-4">
                    <div class="w-2 h-10 rounded-full shrink-0" :class="{
                        'bg-slate-300':  card.status==='new',
                        'bg-amber-400':  card.status==='learning',
                        'bg-emerald-400':card.status==='learned'
                    }"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-700 text-sm truncate" x-text="card.front"></p>
                        <p class="text-slate-400 text-xs truncate mt-0.5" x-text="card.back"></p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="dot hidden md:inline-block" :class="'dot-' + card.difficulty"></span>
                        <button @click="toggleStar(card)" :class="card.starred?'text-amber-400':'text-slate-300 hover:text-amber-400'" class="p-1.5 rounded-lg transition-all">
                            <i data-lucide="star" class="w-4 h-4" :class="card.starred?'fill-amber-400':''"></i>
                        </button>
                        <button @click="openEditCard(card)" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button @click="confirmDeleteCard(card)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>{{-- end view cards --}}


    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  MODAL: TẠO / SỬA BỘ THẺ               ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="showDeckModal" class="modal-overlay" @click.self="showDeckModal=false">
        <div class="modal-box">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800" x-text="deckEditMode ? 'Chỉnh sửa bộ thẻ' : 'Tạo bộ thẻ mới'"></h2>
                <button @click="showDeckModal=false" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-5">
                {{-- Emoji + Name --}}
                <div class="flex gap-3">
                    <!-- <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Icon</label>
                        <input type="text" x-model="deckForm.emoji" maxlength="2"
                            class="w-16 h-12 text-center text-2xl rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div> -->
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Tên bộ thẻ <span class="text-red-400">*</span></label>
                        <input type="text" x-model="deckForm.name" placeholder="Vd: Từ vựng IELTS Band 7+"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all">
                        <p x-show="deckErrors.name" class="mt-1 text-xs text-red-500" x-text="deckErrors.name"></p>
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Môn học / Chủ đề</label>
                    <input type="text" x-model="deckForm.subject" placeholder="Vd: Tiếng Anh, Toán, Lịch sử..."
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all">
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Mô tả ngắn</label>
                    <textarea x-model="deckForm.desc" rows="2" placeholder="Mô tả nội dung của bộ thẻ..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all"></textarea>
                </div>

                {{-- Color --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-3">Màu sắc</label>
                    <div class="flex gap-2 flex-wrap">
                        <template x-for="color in deckColors" :key="color">
                            <button type="button" @click="deckForm.color = color"
                                :class="deckForm.color === color ? 'selected' : ''"
                                class="color-dot"
                                :style="'background:' + color">
                            </button>
                        </template>
                    </div>
                    {{-- Preview --}}
                    <div class="mt-3 h-1.5 rounded-full transition-all" :style="'background:' + deckForm.color"></div>
                </div>
            </div>
            <div class="p-6 pt-0 flex gap-3 justify-end">
                <button @click="showDeckModal=false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-all">Huỷ</button>
                <button @click="saveDeck()" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span x-text="deckEditMode ? 'Cập nhật' : 'Tạo bộ thẻ'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  MODAL: TẠO / SỬA FLASHCARD             ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="showCardModal" class="modal-overlay" @click.self="showCardModal=false">
        <div class="modal-box">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800" x-text="cardEditMode ? 'Chỉnh sửa thẻ' : 'Thêm thẻ mới'"></h2>
                <button @click="showCardModal=false" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Mặt trước <span class="text-red-400">*</span></label>
                    <textarea x-model="cardForm.front" rows="3" placeholder="Câu hỏi / từ khoá / khái niệm..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all"></textarea>
                    <p x-show="cardErrors.front" class="mt-1 text-xs text-red-500" x-text="cardErrors.front"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Mặt sau <span class="text-red-400">*</span></label>
                    <textarea x-model="cardForm.back" rows="3" placeholder="Đáp án / định nghĩa / giải thích..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all"></textarea>
                    <p x-show="cardErrors.back" class="mt-1 text-xs text-red-500" x-text="cardErrors.back"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Độ khó</label>
                    <div class="flex gap-2">
                        <button type="button" @click="cardForm.difficulty='easy'"
                            :class="cardForm.difficulty==='easy'?'ring-2 ring-emerald-400 bg-emerald-50':'bg-slate-50 border border-slate-200'"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-emerald-700 transition-all">Dễ</button>
                        <button type="button" @click="cardForm.difficulty='medium'"
                            :class="cardForm.difficulty==='medium'?'ring-2 ring-amber-400 bg-amber-50':'bg-slate-50 border border-slate-200'"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-amber-700 transition-all">Trung bình</button>
                        <button type="button" @click="cardForm.difficulty='hard'"
                            :class="cardForm.difficulty==='hard'?'ring-2 ring-red-400 bg-red-50':'bg-slate-50 border border-slate-200'"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-red-700 transition-all">Khó</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gợi ý (tuỳ chọn)</label>
                    <input type="text" x-model="cardForm.hint" placeholder="Thêm gợi ý cho thẻ này..."
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 transition-all">
                </div>
                <div>
                    <button type="button" @click="cardForm.starred=!cardForm.starred"
                        :class="cardForm.starred?'bg-amber-50 border-amber-300 text-amber-700':'bg-slate-50 border-slate-200 text-slate-500'"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-all">
                        <i data-lucide="star" class="w-4 h-4" :class="cardForm.starred?'fill-amber-500 text-amber-500':''"></i>
                        <span x-text="cardForm.starred?'Đã đánh dấu sao':'Đánh dấu sao'"></span>
                    </button>
                </div>
            </div>
            <div class="p-6 pt-0 flex gap-3 justify-end">
                <button @click="showCardModal=false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-all">Huỷ</button>
                <button @click="saveCard()" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span x-text="cardEditMode ? 'Cập nhật' : 'Thêm thẻ'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  MODAL: XÁC NHẬN XOÁ                    ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal=false">
        <div class="modal-box max-w-sm">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2" x-text="deleteType==='deck'?'Xoá bộ thẻ?':'Xoá thẻ này?'"></h3>
                <p class="text-slate-500 text-sm mb-1" x-text="deleteType==='deck'?'Tất cả thẻ trong bộ sẽ bị xoá vĩnh viễn.':'Thẻ sẽ bị xoá vĩnh viễn.'"></p>
                <p class="text-indigo-600 text-sm font-semibold mb-6 truncate px-4" x-text="'&ldquo;' + (deleteTarget?.name || deleteTarget?.front || '') + '&rdquo;'"></p>
                <div class="flex gap-3">
                    <button @click="showDeleteModal=false" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-all">Huỷ bỏ</button>
                    <button @click="execDelete()" class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 text-white text-sm font-semibold hover:bg-red-600 transition-all">Xoá</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════╗ --}}
    {{-- ║  STUDY MODE FULLSCREEN                   ║ --}}
    {{-- ╚══════════════════════════════════════════╝ --}}
    <div x-show="showStudy" class="study-overlay">
        {{-- Top bar --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
            <div class="flex items-center gap-3">
                <span class="text-white font-bold text-lg" x-text="activeDeck?.name"></span>
                <span class="text-white/50 text-sm" x-text="(studyIndex+1) + ' / ' + studyQueue.length"></span>
            </div>
            <button @click="showStudy=false" class="p-2 rounded-xl text-white/60 hover:text-white hover:bg-white/10 transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Progress --}}
        <div class="px-6 pt-4">
            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     :style="'width:' + (studyQueue.length ? (studyIndex/studyQueue.length*100) : 0) + '%; background:' + (activeDeck?.color||'#6366f1')"></div>
            </div>
        </div>

        {{-- Card area --}}
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-8" x-show="studyQueue.length > 0">
            <div class="card-flip w-full max-w-xl" style="height:300px; cursor:pointer;"
                 :class="studyFlipped?'flipped':''" @click="studyFlipped=!studyFlipped">
                <div class="card-flip-inner">
                    <div class="card-front rounded-2xl flex flex-col items-center justify-center p-8 text-center"
                         :style="'background:' + (activeDeck?.color||'#6366f1') + '18; border:1px solid ' + (activeDeck?.color||'#6366f1') + '40'">
                        <p class="text-xs font-semibold uppercase tracking-widest mb-4 text-white/50">Câu hỏi</p>
                        <p class="text-white text-xl font-bold leading-relaxed" x-text="studyQueue[studyIndex]?.front"></p>
                        <p class="text-white/30 text-xs mt-6 flex items-center gap-1">
                            <i data-lucide="rotate-ccw" class="w-3 h-3"></i> nhấp để xem đáp án
                        </p>
                    </div>
                    <div class="card-back rounded-2xl flex flex-col items-center justify-center p-8 text-center bg-emerald-950/80 border border-emerald-700/40">
                        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400 mb-4">Đáp án</p>
                        <p class="text-white text-xl font-bold leading-relaxed" x-text="studyQueue[studyIndex]?.back"></p>
                        <p x-show="studyQueue[studyIndex]?.hint" class="text-emerald-300/70 text-xs mt-3 italic" x-text="'💡 ' + studyQueue[studyIndex]?.hint"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Finished --}}
        <div x-show="studyQueue.length === 0" class="flex-1 flex flex-col items-center justify-center text-center">
            <div class="text-6xl mb-4">🎉</div>
            <p class="text-white text-2xl font-bold mb-2">Hoàn thành!</p>
            <p class="text-white/50">Bạn đã ôn xong tất cả thẻ trong bộ này.</p>
            <button @click="showStudy=false" class="mt-6 px-6 py-2.5 rounded-xl bg-white text-slate-800 font-semibold text-sm hover:bg-slate-100 transition-all">Quay lại</button>
        </div>

        {{-- Controls --}}
        <div class="px-6 py-6 flex flex-col gap-3 items-center" x-show="studyQueue.length > 0">
            <div class="flex gap-3 w-full max-w-xl">
                <button @click="studyMark('learning')" class="flex-1 py-3 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 font-semibold text-sm hover:bg-amber-500/30 transition-all">
                    😅 Chưa thuộc
                </button>
                <button @click="studyMark('learned')" class="flex-1 py-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-semibold text-sm hover:bg-emerald-500/30 transition-all">
                    ✅ Đã thuộc
                </button>
            </div>
            <div class="flex gap-3">
                <button @click="studyPrev()" :disabled="studyIndex===0" class="px-5 py-2 rounded-xl border border-white/20 text-white/60 text-sm hover:bg-white/10 disabled:opacity-30 transition-all flex items-center gap-1.5">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i> Trước
                </button>
                <button @click="studyNext()" :disabled="studyIndex>=studyQueue.length-1" class="px-5 py-2 rounded-xl border border-white/20 text-white/60 text-sm hover:bg-white/10 disabled:opacity-30 transition-all flex items-center gap-1.5">
                    Tiếp <i data-lucide="chevron-right" class="w-4 h-4"></i>
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
function fcApp(config) {
    return {
        /* ── navigation ── */
        view: 'decks',      
        activeDeck: null,
        toast: { show: false, message: '', type: 'success' },
        activeDeckId: config.activeDeckId,

        streakDays: config.streakDays,
        /* ── data ── */
        decks: config.decks,

        /* ── search / filter ── */
        deckSearch: '',
        cardSearch: '',
        filterDifficulty: '',
        filterStatus: '',
        cardView: 'grid',

        /* ── deck modal ── */
        showDeckModal: false,
        deckEditMode: false,
        deckEditId: null,
        deckForm: { name:'', emoji:'📚', subject:'', desc:'', color:'#6366f1' },
        deckErrors: {},
        deckColors: [
            '#6366f1','#8b5cf6','#ec4899','#f43f5e',
            '#f97316','#f59e0b','#10b981','#14b8a6',
            '#0ea5e9','#3b82f6','#64748b','#1e293b',
        ],

        /* ── card modal ── */
        showCardModal: false,
        cardEditMode: false,
        cardEditId: null,
        cardForm: { front:'', back:'', difficulty:'medium', hint:'', starred:false },
        cardErrors: {},

        /* ── delete ── */
        showDeleteModal: false,
        deleteTarget: null,
        deleteType: 'card',  // 'deck' | 'card'

        /* ── study ── */
        showStudy: false,
        studyQueue: [],
        studyIndex: 0,
        studyFlipped: false,

        /* ── computed ── */
        // get streakDays() { return 7; }, // placeholder

        get totalCards() {
            return this.decks.reduce((s,d) => s + (d.cards||[]).length, 0);
        },
        get totalLearned() {
            return this.decks.reduce((s,d) => s + (d.cards||[]).filter(c=>c.status==='learned').length, 0);
        },
        get filteredDecks() {
            const q = this.deckSearch.toLowerCase();
            return this.decks.filter(d => !q || d.name.toLowerCase().includes(q) || (d.subject||'').toLowerCase().includes(q));
        },
        get filteredCards() {
            if (!this.activeDeck) return [];
            return (this.activeDeck.cards || []).filter(card => {
                const q = this.cardSearch.toLowerCase();
                const ms = !q || card.front.toLowerCase().includes(q) || card.back.toLowerCase().includes(q);
                const md = !this.filterDifficulty || card.difficulty === this.filterDifficulty;
                const mst= !this.filterStatus     || card.status     === this.filterStatus;
                return ms && md && mst;
            });
        },

        /* ── helpers ── */
        deckProgress(deck) {
            const cards = deck.cards || [];
            if (!cards.length) return 0;
            return Math.round(cards.filter(c=>c.status==='learned').length / cards.length * 100);
        },
        formatDate(ts) {
            if (!ts) return '';
            return new Date(ts).toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric' });
        },
        persist() {
            localStorage.setItem('edunova_decks', JSON.stringify(this.decks));
        },

        /* ── init ── */
        init() {
            // 1. Lấy dữ liệu từ localStorage
            const saved = localStorage.getItem('edunova_decks');
            let loadedDecks = null;
            console.log('Flashcards data:', config.decks);
            console.log('Active Deck ID:', this.activeDeckId);
            console.log('Streak Days:', this.streakDays);
            if (saved && saved !== 'undefined' && saved !== '[]') {
                try {
                    loadedDecks = JSON.parse(saved);
                } catch (e) {
                    // Vẫn giữ lại error log để debug khi có lỗi cú pháp JSON nghiêm trọng
                    console.error("Lỗi parse JSON:", e);
                }
            }

            // 2. Quyết định chọn dữ liệu: Ưu tiên dữ liệu từ LocalStorage
            if (loadedDecks && loadedDecks.length > 0) {
                this.decks = loadedDecks;
            } else {
                this.decks = config.decks || [];
                this.persist(); // Đồng bộ lại vào LocalStorage
            }

            window.addEventListener('chatbot:flashcard-created', async () => {
                this.showToast('Flashcard mới được tạo. Cập nhật dữ liệu...', 'success');
                await this.reloadDecks();
            });
        },

        async reloadDecks() {
            try {
                const response = await fetch('/user/flashcards?ajax=1', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Không thể tải lại flashcards: ${response.status}`);
                }

                const data = await response.json();
                this.decks = data.flashcards || [];
                this.persist();
                this.showToast('Dữ liệu Flashcards đã được cập nhật.', 'success');
            } catch (error) {
                console.error('Lỗi khi tải lại flashcards:', error);
                window.location.reload();
            }
        },

        /* ── Deck CRUD ── */
        openCreateDeck() {
            this.deckEditMode = false;
            this.deckEditId = null;
            this.deckForm = { name:'', emoji:'📚', subject:'', desc:'', color:'#6366f1' };
            this.deckErrors = {};
            this.showDeckModal = true;
            this.$nextTick(() => lucide.createIcons());
        },
        openEditDeck(deck) {
            this.deckEditMode = true;
            this.deckEditId = deck.id;
            this.deckForm = { name:deck.name, emoji:deck.emoji||'📚', subject:deck.subject||'', desc:deck.desc||'', color:deck.color||'#6366f1' };
            this.deckErrors = {};
            this.showDeckModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async saveDeck() {
            this.deckErrors = {};
            
            // 1. Log trạng thái bắt đầu
            console.log("--- Bắt đầu saveDeck ---");
            console.log("Chế độ Edit:", this.deckEditMode);
            console.log("ID hiện tại:", this.deckEditId);
            console.log("Dữ liệu gửi đi (deckForm):", this.deckForm);

            if (!this.deckForm.name.trim()) { 
                this.deckErrors.name = 'Vui lòng nhập tên bộ thẻ.';
                this.showToast('Vui lòng nhập tên bộ thẻ!', 'error');
                return; 
            }

            try {
                const url = this.deckEditMode ? `/user/flashcards/${this.deckEditId}` : '/user/flashcards';
                const method = this.deckEditMode ? 'PUT' : 'POST';
                
                console.log("URL gọi tới:", url);
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.deckForm)
                });

                const result = await response.json();
                
                // 2. Log phản hồi từ server
                console.log("Kết quả từ server:", { status: response.status, data: result });

                if (response.ok) {
                    if (this.deckEditMode) {
                        const idx = this.decks.findIndex(d => d.id === this.deckEditId);
                        console.log("Index tìm thấy trong mảng local:", idx);
                        
                        if (idx !== -1) {
                            this.decks[idx] = { ...this.decks[idx], ...result.deck };
                        }
                        this.showToast('Cập nhật bộ thẻ thành công!');
                    } else {
                        console.log("Thêm mới deck:", result.deck);
                        this.decks.unshift(result.deck);
                        this.showToast('Đã tạo bộ thẻ mới!');
                    }

                    this.showDeckModal = false;
                    this.persist();
                } else {
                    // 3. Log lỗi từ Server (Validation)
                    console.warn("Server trả về lỗi:", result);
                    this.deckErrors = result.errors || { name: 'Có lỗi xảy ra.' };
                    this.showToast(result.message || 'Lỗi khi lưu dữ liệu!', 'error');
                }
            } catch (error) {
                // 4. Log lỗi Network hoặc JS
                console.error("Lỗi Exception hệ thống:", error);
                this.showToast('Lỗi kết nối máy chủ!', 'error');
            }
            
            this.$nextTick(() => lucide.createIcons());
        },

        confirmDeleteDeck(deck) {
            this.deleteTarget = deck;
            this.deleteType = 'deck';
            this.showDeleteModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        /* ── Card CRUD ── */
        openDeck(deck) {
            this.activeDeck = deck;
            this.cardSearch = '';
            this.filterDifficulty = '';
            this.filterStatus = '';
            this.view = 'cards';
            this.$nextTick(() => lucide.createIcons());
        },
        openCreateCard() {
            this.cardEditMode = false;
            this.cardEditId = null;
            this.cardForm = { front:'', back:'', difficulty:'medium', hint:'', starred:false };
            this.cardErrors = {};
            this.showCardModal = true;
            this.$nextTick(() => lucide.createIcons());
        },
        openEditCard(card) {
            this.cardEditMode = true;
            this.cardEditId = card.id;
            this.cardForm = { front:card.front, back:card.back, difficulty:card.difficulty, hint:card.hint||'', starred:card.starred };
            this.cardErrors = {};
            this.showCardModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async saveCard() {
            this.cardErrors = {};
            
            if (!this.cardForm.front.trim()) { this.cardErrors.front = 'Vui lòng nhập mặt trước.'; return; }
            if (!this.cardForm.back.trim())  { this.cardErrors.back  = 'Vui lòng nhập mặt sau.';  return; }

            try {
                if (this.cardEditMode) {
                    const response = await axios.put(`/user/decks/${this.activeDeck.id}/cards/${this.cardEditId}`, this.cardForm);
                    
                    if (response.data.success) {
                        const idx = this.activeDeck.cards.findIndex(c => c.id === this.cardEditId);
                        if (idx !== -1) {
                            Object.assign(this.activeDeck.cards[idx], response.data.card);
                        }
                        this.showToast('Cập nhật thẻ thành công!');
                    }
                } else {
                    const response = await axios.post(`/user/decks/${this.activeDeck.id}/cards`, this.cardForm);
                    
                    if (response.data.success) {
                        if (!this.activeDeck.cards) this.activeDeck.cards = [];
                        this.activeDeck.cards.unshift(response.data.card);
                        this.showToast('Đã thêm thẻ mới!');
                    }
                }

                this.persist();
                this.showCardModal = false;
                this.$nextTick(() => lucide.createIcons());

            } catch (error) {
                if (error.response?.data?.errors) {
                    this.cardErrors = error.response.data.errors; 
                    this.showToast('Vui lòng sửa lỗi trên form!', 'error');
                } else {
                    this.showToast(error.response?.data?.message || 'Có lỗi xảy ra khi lưu thẻ!', 'error');
                }
            }
        },

        confirmDeleteCard(card) {
            this.deleteTarget = card;
            this.deleteType = 'card';
            this.showDeleteModal = true;
            this.$nextTick(() => lucide.createIcons());
        },
        // execDelete() {
        //     if (this.deleteType === 'deck') {
        //         this.decks = this.decks.filter(d => d.id !== this.deleteTarget.id);
        //     } else {
        //         this.activeDeck.cards = this.activeDeck.cards.filter(c => c.id !== this.deleteTarget.id);
        //     }
        //     this.persist();
        //     this.showDeleteModal = false;
        //     this.deleteTarget = null;
        // },
        async execDelete() {
            const target = this.deleteTarget;
            const type = this.deleteType;
            const id = target.id;
            const url = (type === 'deck') ? `/user/flashcards/${id}` : `/user/cards/${id}`;

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });

                // Đọc nội dung lỗi từ server (dù status code là bao nhiêu)
                const result = await response.json().catch(() => ({})); 

                if (response.ok) {
                    // Xử lý thành công
                    if (type === 'deck') this.decks = this.decks.filter(d => d.id !== id);
                    else this.activeDeck.cards = this.activeDeck.cards.filter(c => c.id !== id);
                    
                    this.showToast(`Đã xóa ${type} thành công.`);
                    this.persist();
                } else {
                    // Ném lỗi với nội dung từ server
                    throw new Error(result.message || `Lỗi server: ${response.status}`);
                }
            } catch (error) {
                console.error('Lỗi khi xóa:', error);
                this.showToast(error.message || 'Có lỗi xảy ra khi xóa!', 'error');
            } finally {
                this.showDeleteModal = false;
                this.deleteTarget = null;
            }
        },

        // toggleStar(card) {
        //     card.starred = !card.starred;
        //     this.persist();
        // },
        // markStatus(card, status) {
        //     card.status = status;
        //     card.reviewCount = (card.reviewCount||0) + 1;
        //     this.persist();
        // },
        async toggleStar(card) {
            // 1. Lưu giá trị cũ để rollback
            const originalStarred = card.starred;
            
            // 2. Cập nhật giá trị
            card.starred = !originalStarred;
            this.persist();
            
            try {
                await axios.patch(`/user/cards/${card.id}/status`, { starred: card.starred });
                // Thành công: không cần làm gì thêm
            } catch (e) {
                // 3. Hoàn tác giá trị cũ
                card.starred = originalStarred;
                this.persist(); // Cập nhật lại localStorage cho khớp
                this.showToast('Không thể cập nhật trạng thái yêu thích!', 'error');
            }
        },

      
        async markStatus(card, status) {
    console.log("--- Bắt đầu markStatus ---");
    console.log("Card ID:", card.id, "| Trạng thái mới:", status);

    const oldStatus = card.status;
    const oldReviewCount = card.reviewCount;

    // Cập nhật UI ngay
    card.status = status;
    card.reviewCount = (card.reviewCount || 0) + 1;
    this.persist();

    try {
        console.log("Đang gọi API...");
        const response = await axios.patch(`/user/cards/${card.id}/status`, { status: status });
        
        console.log("Phản hồi từ Server:", response.data);

        if (response.data.success) {
            if (response.data.streak_days !== undefined) {
                console.log("Cập nhật streak từ server:", response.data.streak_days);
                this.streakDays = response.data.streak_days;
            } else {
                console.warn("Server không trả về streak_days");
            }
        } else {
            console.error("Server trả về success: false", response.data.message);
        }
    } catch (e) {
        console.error("Lỗi khi gọi API:", e);
        
        // Hoàn tác nếu lỗi
        card.status = oldStatus;
        card.reviewCount = oldReviewCount;
        this.persist();
        this.showToast('Không thể cập nhật trạng thái thẻ!', 'error');
    }
},

        /* ── Study ── */
        openStudyMode() {
            const cards = (this.activeDeck?.cards||[]).filter(c=>c.status!=='learned');
            this.studyQueue = cards.length ? [...cards] : [...(this.activeDeck?.cards||[])];
            this.studyIndex = 0;
            this.studyFlipped = false;
            this.showStudy = true;
            this.$nextTick(() => lucide.createIcons());
        },
        studyNext() { if (this.studyIndex < this.studyQueue.length-1) { this.studyIndex++; this.studyFlipped=false; } },
        studyPrev() { if (this.studyIndex > 0) { this.studyIndex--; this.studyFlipped=false; } },
        studyMark(status) {
            const card = (this.activeDeck?.cards||[]).find(c=>c.id===this.studyQueue[this.studyIndex]?.id);
            if (card) { card.status=status; card.reviewCount=(card.reviewCount||0)+1; this.persist(); }
            if (status==='learned') {
                this.studyQueue.splice(this.studyIndex,1);
                if (this.studyIndex>=this.studyQueue.length && this.studyIndex>0) this.studyIndex--;
            } else { this.studyNext(); }
            this.studyFlipped=false;
        },

        showToast(message, type='success') {
            this.toast = { show:true, message, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endpush