@extends('layouts.app')
@section('title', 'Quản lý tin tức - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }

    .toolbar-btn { padding:4px 8px; border-radius:6px; font-size:12px; font-weight:600; color:#475569; transition:all .15s; cursor:pointer; }
    .toolbar-btn:hover { background:#f1f5f9; color:#1e293b; }
    .toolbar-btn.active { background:#1e293b; color:#fff; }

    [contenteditable]:focus { outline: none; }
    [contenteditable]:empty:before { content: attr(data-placeholder); color: #94a3b8; }

    .prose-preview h1 { font-size:1.5rem; font-weight:900; color:#0f172a; margin-bottom:.5rem; }
    .prose-preview h2 { font-size:1.2rem; font-weight:800; color:#1e293b; margin:1rem 0 .4rem; }
    .prose-preview p  { font-size:.9rem; color:#475569; line-height:1.7; margin-bottom:.75rem; }
    .prose-preview strong { font-weight:700; color:#1e293b; }
    .prose-preview ul { padding-left:1.2rem; margin-bottom:.75rem; }
    .prose-preview ul li { font-size:.9rem; color:#475569; margin-bottom:.3rem; }
    .prose-preview blockquote { border-left:3px solid #6366f1; padding-left:1rem; color:#64748b; font-style:italic; margin:.75rem 0; }

    .tag-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 10px; border-radius:99px;
                background:#e0e7ff; color:#4338ca; font-size:11px; font-weight:700; }

    .article-card { transition: all .2s; }
    .article-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,.08); }

    /* Upload thumbnail */
    .upload-zone { border: 2px dashed #e2e8f0; border-radius: 12px; transition: all .2s; cursor: pointer; }
    .upload-zone:hover, .upload-zone.dragover { border-color: #6366f1; background: #eef2ff; }
    .upload-zone.uploading { opacity: .7; pointer-events: none; }
    .thumb-preview { position: relative; }
    .thumb-preview .thumb-remove {
        position: absolute; top: 6px; right: 6px;
        background: rgba(0,0,0,.55); color:#fff; border-radius: 50%;
        width: 22px; height: 22px; display:flex; align-items:center; justify-content:center;
        font-size: 12px; cursor:pointer; transition: background .15s;
    }
    .thumb-preview .thumb-remove:hover { background: #ef4444; }
</style>
@endpush

@section('content')
<div x-data="newsManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Tin tức</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Quản lý tin tức</h1>
            <p class="text-slate-500 text-sm mt-0.5">Đăng, chỉnh sửa và quản lý bài viết trên nền tảng</p>
        </div>
        <button @click="openEditor()"
            class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm
                   hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i> Viết bài mới
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

    {{-- TABS --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-2xl p-1.5 w-fit">
            <template x-for="t in tabs" :key="t.key">
                <button @click="setTab(t.key)"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
                    :class="activeTab===t.key ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-700'">
                    <span x-text="t.label"></span>
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                        :class="activeTab===t.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'"
                        x-text="t.count">
                    </span>
                </button>
            </template>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="search" @input="reinitIcons()" type="text" placeholder="Tìm bài viết..."
                    class="pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 w-56">
            </div>
            <select x-model="filterCat" @change="reinitIcons()" class="px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả chủ đề</option>
                <template x-for="c in categories" :key="c">
                    <option :value="c" x-text="c"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- ARTICLE LIST --}}
    {{--
        KEY FIX: Dùng x-key kết hợp activeTab+id để Alpine buộc re-render
        toàn bộ DOM node khi chuyển tab, không tái sử dụng node cũ.
        Sau mỗi lần render xong, reinitIcons() được gọi qua Alpine.effect.
    --}}
    <div class="space-y-3" x-ref="articleList">
        <template x-for="art in filteredArticles" :key="activeTab + '_' + art.id">
            <div class="article-card bg-white rounded-2xl border border-slate-200 p-5 flex flex-col sm:flex-row gap-4">
                {{-- Thumbnail --}}
                <div class="w-full sm:w-28 h-20 rounded-xl shrink-0 overflow-hidden flex items-center justify-center"
                    :style="art.thumbnail_url ? '' : `background:${art.color}15`">
                    <template x-if="art.thumbnail_url">
                        <img :src="art.thumbnail_url" alt="thumbnail"
                            class="w-full h-full object-cover rounded-xl">
                    </template>
                    <template x-if="!art.thumbnail_url">
                        <i :data-lucide="art.icon" class="w-8 h-8" :style="`color:${art.color}`"></i>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="`background:${art.color}15;color:${art.color}`"
                                x-text="art.category">
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :class="{
                                    'bg-emerald-50 text-emerald-600': art.status==='published',
                                    'bg-amber-50 text-amber-600':    art.status==='draft',
                                    'bg-slate-100 text-slate-500':   art.status==='scheduled',
                                }"
                                x-text="{'published':'Đã đăng','draft':'Nháp','scheduled':'Đã lên lịch'}[art.status]">
                            </span>
                            <span x-show="art.pinned" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-500">
                                📌 Ghim
                            </span>
                        </div>

                        {{-- ACTION BUTTONS — dùng SVG inline thay Lucide để tránh lệ thuộc createIcons() --}}
                        <div class="flex items-center gap-1 shrink-0">
                            {{-- Edit --}}
                            <button @click.stop="openEditor(art)"
                                class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all"
                                title="Chỉnh sửa">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            {{-- Pin --}}
                            <button @click.stop="togglePin(art)"
                                class="p-1.5 rounded-lg hover:bg-amber-50 transition-all"
                                :title="art.pinned ? 'Bỏ ghim' : 'Ghim bài'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </button>
                            {{-- Toggle status --}}
                            <button @click.stop="toggleStatus(art)"
                                class="p-1.5 rounded-lg hover:bg-emerald-50 transition-all"
                                :title="art.status==='published' ? 'Chuyển về nháp' : 'Xuất bản'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                </svg>
                            </button>
                            {{-- Delete --}}
                            <button @click.stop="deleteArticle(art)"
                                class="p-1.5 rounded-lg hover:bg-rose-50 transition-all"
                                title="Xóa bài viết">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="font-black text-slate-900 text-base mt-2 line-clamp-1" x-text="art.title"></h3>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2" x-text="art.excerpt"></p>

                    <div class="flex items-center gap-4 mt-3 flex-wrap">
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span x-text="art.author"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-text="art.date"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span x-text="art.views.toLocaleString() + ' lượt xem'"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="art.readTime + ' phút đọc'"></span>
                        </span>
                        <div class="ml-auto flex gap-1 flex-wrap">
                            <template x-for="tag in art.tags" :key="tag">
                                <span class="tag-chip" x-text="'#' + tag"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="filteredArticles.length===0" class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <p class="text-slate-500 font-semibold">Không có bài viết nào</p>
        </div>
    </div>

    {{-- ════════════════════════════
         EDITOR MODAL (full-screen)
    ════════════════════════════ --}}
    <div x-show="editorOpen" x-transition class="fixed inset-0 z-50 flex flex-col bg-white" style="display:none">

        {{-- Editor topbar --}}
        <div class="flex items-center justify-between px-6 py-3 border-b border-slate-200 bg-white shrink-0">
            <div class="flex items-center gap-3">
                <button @click="editorOpen=false" class="p-2 rounded-xl hover:bg-slate-100 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </button>
                <div>
                    <p class="text-sm font-black text-slate-900" x-text="editingArticle ? 'Chỉnh sửa bài viết' : 'Viết bài mới'"></p>
                    <p class="text-[10px] text-slate-400" x-text="form.status==='draft' ? '● Đang lưu nháp' : '● Đã lưu'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <button @click="editorView='write'"
                        :class="editorView==='write' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Viết
                    </button>
                    <button @click="editorView='preview'"
                        :class="editorView==='preview' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Preview
                    </button>
                    <button @click="editorView='split'"
                        :class="editorView==='split' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                        Split
                    </button>
                </div>
                <button @click="saveArticle('draft')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Lưu nháp
                </button>

                {{-- Nút này chỉ hiện khi trạng thái đang chọn là "Lên lịch" --}}
                <button x-show="form.status==='scheduled'" @click="saveArticle('scheduled')"
                    class="px-4 py-2 border border-indigo-200 text-indigo-600 rounded-xl text-sm font-semibold hover:bg-indigo-50 transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Lên lịch đăng
                </button>

                {{-- Nút "Đăng bài" chỉ hiện khi KHÔNG chọn lên lịch --}}
                <button x-show="form.status!=='scheduled'" @click="saveArticle('published')"
                    class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span x-text="editingArticle ? 'Cập nhật' : 'Đăng bài'"></span>
                </button>
            </div>
        </div>

        {{-- Editor body --}}
        <div class="flex-1 overflow-hidden flex">
            {{-- LEFT: Metadata sidebar --}}
            <div class="w-72 border-r border-slate-100 p-5 space-y-4 overflow-y-auto shrink-0">

                {{-- THUMBNAIL UPLOAD --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Ảnh thumbnail</label>

                    {{-- Đã có ảnh → hiển thị preview + nút xóa --}}
                    <div x-show="form.thumbnail_url" class="thumb-preview">
                        <img :src="form.thumbnail_url" alt="thumbnail"
                            class="w-full h-36 object-cover rounded-xl border border-slate-200">
                        <button type="button"
                            @click="form.thumbnail_url = ''"
                            class="thumb-remove" title="Xóa ảnh">✕</button>
                    </div>

                    {{-- Chưa có ảnh → drop zone --}}
                    <div x-show="!form.thumbnail_url"
                        class="upload-zone flex flex-col items-center justify-center gap-2 py-6 px-3 text-center"
                        :class="{ uploading: uploadingThumb, dragover: dragOver }"
                        @click="$refs.thumbInput.click()"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="onThumbDrop($event)">

                        <template x-if="!uploadingThumb">
                            <div class="flex flex-col items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-xs font-semibold text-slate-500">Click hoặc kéo thả ảnh</p>
                                <p class="text-[10px] text-slate-400">PNG, JPG, WEBP · tối đa 5MB</p>
                            </div>
                        </template>
                        <template x-if="uploadingThumb">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="animate-spin w-6 h-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <p class="text-xs text-indigo-500 font-semibold">Đang upload...</p>
                            </div>
                        </template>
                    </div>

                    {{-- Hidden file input --}}
                    <input type="file" x-ref="thumbInput" accept="image/jpeg,image/png,image/webp,image/gif"
                        class="hidden" @change="onThumbSelect($event)">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Chủ đề</label>
                    <select x-model="form.category" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <template x-for="c in categories" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Tác giả</label>
                    <input x-model="form.author" type="text"
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Trạng thái</label>
                    <select x-model="form.status" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="draft">Nháp</option>
                        <option value="published">Đăng ngay</option>
                        <option value="scheduled">Lên lịch</option>
                    </select>
                </div>
                <div x-show="form.status==='scheduled'">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Ngày đăng</label>
                    <input x-model="form.scheduleDate" type="datetime-local"
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Tags</label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <template x-for="(tag, i) in form.tags" :key="i">
                            <span class="tag-chip cursor-pointer" @click="form.tags.splice(i,1)" x-text="'#'+tag+' ×'"></span>
                        </template>
                    </div>
                    <input x-model="tagInput" type="text" placeholder="Nhập tag + Enter"
                        @keydown.enter.prevent="addTag()"
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.pinned" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
                        <span class="text-xs font-semibold text-slate-600">Ghim bài viết</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Mô tả ngắn</label>
                    <textarea x-model="form.excerpt" rows="3" placeholder="Tóm tắt nội dung bài viết..."
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                    </textarea>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">SEO Preview</p>
                    <p class="text-xs font-bold text-blue-600 truncate" x-text="form.title || 'Tiêu đề bài viết'"></p>
                    <p class="text-[10px] text-slate-400 mt-0.5">edunova.vn/news/...</p>
                    <p class="text-[10px] text-slate-500 mt-1 line-clamp-2" x-text="form.excerpt || 'Mô tả ngắn...'"></p>
                </div>
            </div>

            {{-- RIGHT: Editor + Preview --}}
            <div class="flex-1 flex overflow-hidden">
                {{-- WRITE pane --}}
                <div class="flex-1 flex flex-col overflow-hidden"
                    :class="editorView==='preview' ? 'hidden' : editorView==='split' ? 'w-1/2' : 'w-full'">
                    <div class="px-8 pt-6 pb-2 border-b border-slate-100">
                        <input x-model="form.title" type="text"
                            placeholder="Tiêu đề bài viết..."
                            class="w-full text-2xl font-black text-slate-900 placeholder-slate-300 focus:outline-none bg-transparent">
                    </div>
                    <div class="flex items-center gap-1 px-8 py-2 border-b border-slate-100 flex-wrap">
                        <button class="toolbar-btn" @click="execCmd('bold')" title="Bold"><b>B</b></button>
                        <button class="toolbar-btn" @click="execCmd('italic')" title="Italic"><i>I</i></button>
                        <button class="toolbar-btn" @click="execCmd('underline')" title="Underline"><u>U</u></button>
                        <div class="w-px h-5 bg-slate-200 mx-1"></div>
                        <button class="toolbar-btn" @click="execCmd('formatBlock','h2')" title="Heading">H2</button>
                        <button class="toolbar-btn" @click="execCmd('formatBlock','h3')" title="Heading 3">H3</button>
                        <button class="toolbar-btn" @click="execCmd('formatBlock','p')" title="Paragraph">¶</button>
                        <div class="w-px h-5 bg-slate-200 mx-1"></div>
                        <button class="toolbar-btn" @click="execCmd('insertUnorderedList')" title="List">• List</button>
                        <button class="toolbar-btn" @click="toggleQuote()" title="Quote">" Quote</button>
                        <div class="w-px h-5 bg-slate-200 mx-1"></div>
                        <button class="toolbar-btn" @click="insertLink()" title="Link">🔗 Link</button>
                        <button class="toolbar-btn text-slate-400 text-xs ml-auto" x-text="`~${wordCount} từ · ${readTime} phút đọc`"></button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-8 py-6">
                        <div id="editorContent"
                            contenteditable="true"
                            data-placeholder="Bắt đầu viết nội dung bài viết..."
                            class="min-h-full text-slate-700 text-sm leading-relaxed focus:outline-none prose-preview"
                            @input="onEditorInput($event)">
                        </div>
                    </div>
                </div>

                <div x-show="editorView==='split'" class="w-px bg-slate-200 shrink-0"></div>

                {{-- PREVIEW pane --}}
                <div class="flex-1 overflow-y-auto bg-slate-50"
                    :class="editorView==='write' ? 'hidden' : editorView==='split' ? 'w-1/2' : 'w-full'">
                    <div class="max-w-2xl mx-auto px-8 py-10">
                        <div class="mb-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600"
                                    x-text="form.category || 'Chủ đề'"></span>
                                <span x-show="form.pinned" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-500">📌 Ghim</span>
                            </div>
                            <h1 class="text-3xl font-black text-slate-900 leading-tight" x-text="form.title || 'Tiêu đề bài viết...'"></h1>
                            <p class="text-slate-500 mt-2" x-text="form.excerpt || ''"></p>
                            <div class="flex items-center gap-3 mt-4 text-xs text-slate-400">
                                <span x-text="form.author || 'Tác giả'"></span>
                                <span>·</span>
                                <span x-text="new Date().toLocaleDateString('vi-VN')"></span>
                                <span>·</span>
                                <span x-text="readTime + ' phút đọc'"></span>
                            </div>
                        </div>
                        {{-- Thumbnail preview --}}
                        <div class="w-full h-48 rounded-2xl overflow-hidden mb-6 flex items-center justify-center"
                            :class="form.thumbnail_url ? '' : 'bg-gradient-to-br from-indigo-100 to-violet-100'">
                            <template x-if="form.thumbnail_url">
                                <img :src="form.thumbnail_url" alt="thumbnail" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!form.thumbnail_url">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </template>
                        </div>
                        <div class="prose-preview" x-html="form.content || '<p>Nội dung bài viết sẽ hiển thị ở đây...</p>'"></div>
                        <div x-show="form.tags.length > 0" class="flex flex-wrap gap-2 mt-6 pt-6 border-t border-slate-200">
                            <template x-for="tag in form.tags" :key="tag">
                                <span class="tag-chip" x-text="'#'+tag"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="toast.type==='success'?'bg-emerald-600':'bg-rose-600'">
        <svg x-show="toast.type==='success'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <svg x-show="toast.type!=='success'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function newsManager() {
    return {
        // ── State ──────────────────────────────────────────────────────────
        activeTab:      'all',
        search:         '',
        filterCat:      '',
        editorOpen:     false,
        editorView:     'write',
        editingArticle: null,
        tagInput:       '',
        loading:        false,
        uploadingThumb: false,          // trạng thái đang upload ảnh
        dragOver:       false,          // drag-over drop zone
        toast: { show: false, msg: '', type: 'success' },

        articles:   [],
        kpis:       [],
        categories: [],
        tabs: [
            { key: 'all',       label: 'Tất cả',   count: 0 },
            { key: 'published', label: 'Đã đăng',  count: 0 },
            { key: 'draft',     label: 'Nháp',     count: 0 },
            { key: 'scheduled', label: 'Lên lịch', count: 0 },
        ],

        form: {
            title: '', category: 'Học tập', author: 'Admin EduNova',
            content: '', excerpt: '', tags: [],
            status: 'draft', pinned: false, scheduleDate: '',
            thumbnail_url: '',
        },

        // ── Computed ───────────────────────────────────────────────────────
        get filteredArticles() {
            const q = this.search.toLowerCase();
            return this.articles.filter(a =>
                (this.activeTab === 'all' || a.status === this.activeTab)
                && (!q || a.title.toLowerCase().includes(q))
                && (!this.filterCat || a.category === this.filterCat)
            );
        },
        get wordCount() {
            const text = (this.form.content || '').replace(/<[^>]*>/g, '');
            return text.trim() ? text.trim().split(/\s+/).length : 0;
        },
        get readTime() { return Math.max(1, Math.ceil(this.wordCount / 200)); },

        // ── Init ───────────────────────────────────────────────────────────
        async init() {
            await this.fetchData();

            // Re-init Lucide bất cứ khi nào tab, search, filterCat thay đổi
            // Dùng $watch thay vì dựa vào $nextTick trong setter riêng lẻ
            this.$watch('activeTab',  () => this.$nextTick(() => this.reinitIcons()));
            this.$watch('search',     () => this.$nextTick(() => this.reinitIcons()));
            this.$watch('filterCat',  () => this.$nextTick(() => this.reinitIcons()));
            this.$watch('articles',   () => this.$nextTick(() => this.reinitIcons()));
            this.$watch('editorOpen', (val) => {
                if (!val) this.$nextTick(() => this.reinitIcons());
            });
        },

        // ── Tab change (explicit method để đảm bảo reinitIcons chạy sau render) ──
        setTab(key) {
            this.activeTab = key;
            // $watch đã xử lý reinitIcons, nhưng thêm fallback an toàn
            this.$nextTick(() => this.reinitIcons());
        },

        // ── Lucide re-init helper ──────────────────────────────────────────
        reinitIcons() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        },

        // ── API helper ─────────────────────────────────────────────────────
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },
        async api(url, method = 'GET', body = null) {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                },
                body: body ? JSON.stringify(body) : null,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message ?? `Lỗi HTTP ${res.status}`);
            return data;
        },

        // ── Fetch list ─────────────────────────────────────────────────────
        async fetchData() {
            try {
                const res = await this.api('/admin/news/data');

                console.group('--- API News Data Response ---');
                console.log('Full Response:', res);
                console.log('Articles count:', res.data?.length ?? 0);
                console.groupEnd();

                this.articles   = res.data       ?? [];
                this.kpis       = res.kpis       ?? [];
                this.categories = res.categories ?? [];
                this._syncTabs(res.tabs);
                this.$nextTick(() => this.reinitIcons());
            } catch (e) {
                console.error('Error fetching news data:', e);
                this.showToast(e.message, 'error');
            }
        },

        _syncTabs(serverTabs) {
            if (!serverTabs) return;
            serverTabs.forEach(st => {
                const t = this.tabs.find(t => t.key === st.key);
                if (t) t.count = st.count;
            });
        },

        // ── Open editor ────────────────────────────────────────────────────
        openEditor(art = null) {
            this.editingArticle = art;
            if (art) {
                this.form = {
                    title:         art.title,
                    category:      art.category,
                    author:        art.author,
                    content:       art.content ?? '',
                    excerpt:       art.excerpt ?? '',
                    tags:          [...(art.tags ?? [])],
                    status:        art.status,
                    pinned:        art.pinned ?? false,
                    scheduleDate:  art.scheduled_at ?? '',
                    thumbnail_url: art.thumbnail_url ?? '',
                };
            } else {
                this.form = {
                    title: '', category: 'Học tập', author: 'Admin EduNova',
                    content: '', excerpt: '', tags: [],
                    status: 'draft', pinned: false, scheduleDate: '',
                    thumbnail_url: '',
                };
            }
            this.editorView = 'write';
            this.editorOpen = true;
            this.$nextTick(() => {
                const el = document.getElementById('editorContent');
                if (el) el.innerHTML = this.form.content;
            });
        },

        toggleQuote() {
            const editor = document.getElementById('editorContent');
            if (!editor) return;
            editor.focus();

            const sel = window.getSelection();
            if (!sel.rangeCount) return;

            // Tìm blockquote gần nhất chứa con trỏ hiện tại
            let node = sel.anchorNode;
            let bq = null;
            while (node && node !== editor) {
                if (node.nodeType === 1 && node.tagName === 'BLOCKQUOTE') {
                    bq = node;
                    break;
                }
                node = node.parentNode;
            }

            if (bq) {
                // Đang trong blockquote -> gỡ ra (unwrap), giữ nguyên nội dung bên trong
                const parent = bq.parentNode;
                while (bq.firstChild) {
                    parent.insertBefore(bq.firstChild, bq);
                }
                parent.removeChild(bq);
            } else {
                // Chưa có blockquote -> bật
                document.execCommand('formatBlock', false, 'blockquote');
            }

            this.form.content = editor.innerHTML;
        },
        onEditorInput(e) {
            this.form.content = e.target.innerHTML;
        },
        execCmd(cmd, val = null) {
            document.getElementById('editorContent')?.focus();
            document.execCommand(cmd, false, val);
        },
        insertLink() {
            const url = prompt('Nhập URL:');
            if (url) document.execCommand('createLink', false, url);
        },
        addTag() {
            const t = this.tagInput.trim().replace(/^#/, '').replace(/\s+/g, '-').toLowerCase();
            if (t && !this.form.tags.includes(t)) this.form.tags.push(t);
            this.tagInput = '';
        },

        // ── Save ───────────────────────────────────────────────────────────
        async saveArticle(status) {
            this.form.content = document.getElementById('editorContent')?.innerHTML ?? this.form.content;
            this.form.status  = status;

            if (status === 'scheduled') {
                if (!this.form.scheduleDate) {
                    this.showToast('Vui lòng chọn ngày giờ đăng bài', 'error');
                    return;
                }
                if (new Date(this.form.scheduleDate) <= new Date()) {
                    this.showToast('Ngày giờ đăng phải ở tương lai', 'error');
                    return;
                }
            }

            if (!this.form.title?.trim()) {
                this.showToast('Vui lòng nhập tiêu đề bài viết', 'error');
                return;
            }

            this.loading = true;

            const payload = {
                title:         this.form.title,
                category:      this.form.category,
                author_name:   this.form.author,
                content:       this.form.content,
                excerpt:       this.form.excerpt,
                tags:          this.form.tags,
                status:        this.form.status,
                is_featured:   this.form.pinned,
                scheduled_at:  status === 'scheduled' ? this.form.scheduleDate : null,
                thumbnail_url: this.form.thumbnail_url || null,
            };

            try {
                let res;
                if (this.editingArticle) {
                    res = await this.api(`/admin/news/${this.editingArticle.id}`, 'PUT', payload);
                    this._syncArticle(res.article);
                } else {
                    res = await this.api('/admin/news', 'POST', payload);
                    this.articles.unshift(res.article);
                }
                this._syncTabs(res.tabs);
                this.kpis      = res.kpis ?? this.kpis;
                this.editorOpen = false;
                this.showToast(res.message ?? (
                    status === 'published'  ? 'Đã đăng bài!' :
                    status === 'scheduled'  ? 'Đã lên lịch đăng bài!' :
                    'Đã lưu nháp'
                ));
                this.$nextTick(() => this.reinitIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // ── Toggle pin ─────────────────────────────────────────────────────
        async togglePin(art) {
            try {
                const res = await this.api(`/admin/news/${art.id}/pin`, 'PATCH');
                art.pinned = res.is_featured;
                this.showToast(res.message);
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Toggle status ──────────────────────────────────────────────────
        async toggleStatus(art) {
            try {
                const res = await this.api(`/admin/news/${art.id}/toggle-status`, 'PATCH');
                art.status = res.status;
                this._syncTabs(res.tabs);
                this.showToast(res.message);
                this.$nextTick(() => this.reinitIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Delete ─────────────────────────────────────────────────────────
        async deleteArticle(art) {
            if (!confirm(`Xóa bài "${art.title}"?`)) return;
            try {
                const res = await this.api(`/admin/news/${art.id}`, 'DELETE');
                this.articles = this.articles.filter(a => a.id !== art.id);
                this._syncTabs(res.tabs);
                this.kpis = res.kpis ?? this.kpis;
                this.showToast(res.message ?? 'Đã xóa bài viết', 'error');
                this.$nextTick(() => this.reinitIcons());
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        // ── Sync helpers ───────────────────────────────────────────────────
        _syncArticle(updated) {
            if (!updated) return;
            const idx = this.articles.findIndex(a => a.id === updated.id);
            if (idx !== -1) this.articles.splice(idx, 1, updated);
        },

        // ── Upload thumbnail ───────────────────────────────────────────────
        async uploadThumb(file) {
            if (!file) return;

            // Validate client-side trước khi gửi
            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!allowed.includes(file.type)) {
                this.showToast('Chỉ chấp nhận JPG, PNG, WEBP, GIF', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.showToast('Ảnh tối đa 5MB', 'error');
                return;
            }

            this.uploadingThumb = true;
            try {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('_token', this.csrfToken());

                const res = await fetch('/admin/news/upload-image', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: formData,     // FormData → không set Content-Type, browser tự set multipart
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message ?? `Lỗi HTTP ${res.status}`);

                this.form.thumbnail_url = data.url;
                this.showToast('Upload ảnh thành công!');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.uploadingThumb = false;
            }
        },

        onThumbSelect(e) {
            const file = e.target.files?.[0];
            if (file) this.uploadThumb(file);
            e.target.value = '';    // reset để chọn lại cùng file được
        },

        onThumbDrop(e) {
            this.dragOver = false;
            const file = e.dataTransfer.files?.[0];
            if (file) this.uploadThumb(file);
        },

        showToast(msg, type = 'success') {
            this.toast = { show: true, msg, type };
            setTimeout(() => this.toast.show = false, 2500);
        },
    };
}
</script>
@endpush
@endsection