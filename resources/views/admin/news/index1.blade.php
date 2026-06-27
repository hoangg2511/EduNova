@extends('layouts.app')
@section('title', 'Quản lý tin tức - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }

    /* Editor toolbar */
    .toolbar-btn { padding:4px 8px; border-radius:6px; font-size:12px; font-weight:600; color:#475569; transition:all .15s; cursor:pointer; }
    .toolbar-btn:hover { background:#f1f5f9; color:#1e293b; }
    .toolbar-btn.active { background:#1e293b; color:#fff; }

    /* Editable content area */
    [contenteditable]:focus { outline: none; }
    [contenteditable]:empty:before { content: attr(data-placeholder); color: #94a3b8; }

    /* Preview prose */
    .prose-preview h1 { font-size:1.5rem; font-weight:900; color:#0f172a; margin-bottom:.5rem; }
    .prose-preview h2 { font-size:1.2rem; font-weight:800; color:#1e293b; margin:1rem 0 .4rem; }
    .prose-preview p  { font-size:.9rem; color:#475569; line-height:1.7; margin-bottom:.75rem; }
    .prose-preview strong { font-weight:700; color:#1e293b; }
    .prose-preview ul { padding-left:1.2rem; margin-bottom:.75rem; }
    .prose-preview ul li { font-size:.9rem; color:#475569; margin-bottom:.3rem; }
    .prose-preview blockquote { border-left:3px solid #6366f1; padding-left:1rem; color:#64748b; font-style:italic; margin:.75rem 0; }

    /* Tag input */
    .tag-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 10px; border-radius:99px;
                background:#e0e7ff; color:#4338ca; font-size:11px; font-weight:700; }

    /* Article card hover */
    .article-card { transition: all .2s; }
    .article-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,.08); }
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
                <button @click="activeTab=t.key"
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
                <input x-model="search" type="text" placeholder="Tìm bài viết..."
                    class="pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 w-56">
            </div>
            <select x-model="filterCat" class="px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả chủ đề</option>
                <template x-for="c in categories" :key="c">
                    <option :value="c" x-text="c"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- ARTICLE LIST --}}
    <div class="space-y-3">
        <template x-for="art in filteredArticles" :key="art.id">
            <div class="article-card bg-white rounded-2xl border border-slate-200 p-5 flex flex-col sm:flex-row gap-4">
                {{-- Thumbnail placeholder --}}
                <div class="w-full sm:w-28 h-20 rounded-xl shrink-0 flex items-center justify-center overflow-hidden"
                    :style="`background:${art.color}15`">
                    <i :data-lucide="art.icon" class="w-8 h-8" :style="`color:${art.color}`"></i>
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
                        <div class="flex items-center gap-1 shrink-0">
                            <button @click="openEditor(art)" class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all" title="Chỉnh sửa">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                            </button>
                            <button @click="togglePin(art)" class="p-1.5 rounded-lg hover:bg-amber-50 transition-all" title="Ghim/bỏ ghim">
                                <i data-lucide="pin" class="w-3.5 h-3.5 text-amber-500"></i>
                            </button>
                            <button @click="toggleStatus(art)" class="p-1.5 rounded-lg hover:bg-emerald-50 transition-all" title="Đổi trạng thái">
                                <i data-lucide="toggle-left" class="w-3.5 h-3.5 text-emerald-500"></i>
                            </button>
                            <button @click="deleteArticle(art)" class="p-1.5 rounded-lg hover:bg-rose-50 transition-all" title="Xóa">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                            </button>
                        </div>
                    </div>

                    <h3 class="font-black text-slate-900 text-base mt-2 line-clamp-1" x-text="art.title"></h3>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2" x-text="art.excerpt"></p>

                    <div class="flex items-center gap-4 mt-3 flex-wrap">
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="user" class="w-3 h-3"></i>
                            <span x-text="art.author"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            <span x-text="art.date"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="eye" class="w-3 h-3"></i>
                            <span x-text="art.views.toLocaleString() + ' lượt xem'"></span>
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            <span x-text="art.readTime + ' phút đọc'"></span>
                        </span>
                        <div class="ml-auto flex gap-1">
                            <template x-for="tag in art.tags" :key="tag">
                                <span class="tag-chip" x-text="'#' + tag"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="filteredArticles.length===0" class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
            <i data-lucide="newspaper" class="w-12 h-12 text-slate-200 mx-auto mb-3"></i>
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
                    <i data-lucide="arrow-left" class="w-5 h-5 text-slate-600"></i>
                </button>
                <div>
                    <p class="text-sm font-black text-slate-900" x-text="editingArticle ? 'Chỉnh sửa bài viết' : 'Viết bài mới'"></p>
                    <p class="text-[10px] text-slate-400" x-text="form.status==='draft' ? '● Đang lưu nháp' : '● Đã lưu'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                {{-- View toggle --}}
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <button @click="editorView='write'"
                        :class="editorView==='write' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-3 h-3"></i> Viết
                    </button>
                    <button @click="editorView='preview'"
                        :class="editorView==='preview' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <i data-lucide="eye" class="w-3 h-3"></i> Preview
                    </button>
                    <button @click="editorView='split'"
                        :class="editorView==='split' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                        <i data-lucide="columns" class="w-3 h-3"></i> Split
                    </button>
                </div>
                <button @click="saveArticle('draft')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Lưu nháp
                </button>
                <button @click="saveArticle('published')"
                    class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                    <i data-lucide="send" class="w-4 h-4 inline-block mr-1"></i>
                    <span x-text="editingArticle ? 'Cập nhật' : 'Đăng bài'"></span>
                </button>
            </div>
        </div>

        {{-- Editor body --}}
        <div class="flex-1 overflow-hidden flex">

            {{-- LEFT: Metadata sidebar --}}
            <div class="w-72 border-r border-slate-100 p-5 space-y-4 overflow-y-auto shrink-0">
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
                {{-- SEO preview --}}
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

                    {{-- Title input --}}
                    <div class="px-8 pt-6 pb-2 border-b border-slate-100">
                        <input x-model="form.title" type="text"
                            placeholder="Tiêu đề bài viết..."
                            class="w-full text-2xl font-black text-slate-900 placeholder-slate-300 focus:outline-none bg-transparent">
                    </div>

                    {{-- Toolbar --}}
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
                        <button class="toolbar-btn" @click="execCmd('formatBlock','blockquote')" title="Quote">" Quote</button>
                        <div class="w-px h-5 bg-slate-200 mx-1"></div>
                        <button class="toolbar-btn" @click="insertLink()" title="Link">🔗 Link</button>
                        <button class="toolbar-btn text-slate-400 text-xs ml-auto" x-text="`~${wordCount} từ · ${readTime} phút đọc`"></button>
                    </div>

                    {{-- Content area --}}
                    <div class="flex-1 overflow-y-auto px-8 py-6">
                        <div id="editorContent"
                            contenteditable="true"
                            data-placeholder="Bắt đầu viết nội dung bài viết..."
                            class="min-h-full text-slate-700 text-sm leading-relaxed focus:outline-none prose-preview"
                            @input="onEditorInput($event)"
                            x-html="form.content">
                        </div>
                    </div>
                </div>

                {{-- Divider for split view --}}
                <div x-show="editorView==='split'" class="w-px bg-slate-200 shrink-0"></div>

                {{-- PREVIEW pane --}}
                <div class="flex-1 overflow-y-auto bg-slate-50"
                    :class="editorView==='write' ? 'hidden' : editorView==='split' ? 'w-1/2' : 'w-full'">
                    <div class="max-w-2xl mx-auto px-8 py-10">
                        {{-- Preview header --}}
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
                        {{-- Thumbnail placeholder --}}
                        <div class="w-full h-48 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mb-6">
                            <i data-lucide="image" class="w-10 h-10 text-indigo-300"></i>
                        </div>
                        {{-- Article body preview --}}
                        <div class="prose-preview" x-html="form.content || '<p>Nội dung bài viết sẽ hiển thị ở đây...</p>'"></div>

                        {{-- Tags --}}
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
        <i :data-lucide="toast.type==='success'?'check-circle':'alert-circle'" class="w-4 h-4"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function newsManager() {
    return {
        activeTab: 'all',
        search: '', filterCat: '',
        editorOpen: false,
        editorView: 'write',
        editingArticle: null,
        tagInput: '',
        toast: { show:false, msg:'', type:'success' },

        form: {
            title:'', category:'Học tập', author:'Admin EduNova',
            content:'', excerpt:'', tags:[], status:'draft',
            pinned:false, scheduleDate:'',
        },

        tabs: [
            { key:'all',       label:'Tất cả',    count:6 },
            { key:'published', label:'Đã đăng',   count:4 },
            { key:'draft',     label:'Nháp',      count:1 },
            { key:'scheduled', label:'Lên lịch',  count:1 },
        ],
        categories: ['Học tập','Thông báo','Khuyến mãi','Sự kiện','Công nghệ','Hướng dẫn'],

        kpis: [
            { key:'total',   icon:'newspaper',    color:'#6366f1', label:'Tổng bài viết', value:'48',     sub:'6 tháng qua' },
            { key:'pub',     icon:'send',         color:'#10b981', label:'Đã xuất bản',  value:'41',     sub:'85% tổng số' },
            { key:'views',   icon:'eye',          color:'#f59e0b', label:'Tổng lượt xem',value:'124.8K', sub:'+3.2K tuần này' },
            { key:'avg',     icon:'clock',        color:'#8b5cf6', label:'T.g đọc TB',   value:'4.2 phút',sub:'Theo dõi hành vi' },
        ],

        articles: [
            { id:1, title:'EduNova ra mắt tính năng Flashcard thông minh tích hợp AI', category:'Công nghệ', author:'Ngô Văn Nam', date:'10/06/2025', excerpt:'Hệ thống flashcard mới sử dụng thuật toán SM-2 và AI để cá nhân hóa lộ trình học từ vựng, giúp học viên ghi nhớ hiệu quả hơn 3 lần so với phương pháp truyền thống.', status:'published', views:4821, readTime:5, tags:['flashcard','ai','học-tập'], pinned:true, color:'#6366f1', icon:'zap' },
            { id:2, title:'5 chiến lược học tập hiệu quả cho kỳ thi cuối kỳ', category:'Học tập', author:'Lê Văn Hùng', date:'08/06/2025', excerpt:'Tổng hợp các phương pháp học tập được nghiên cứu khoa học chứng minh giúp cải thiện kết quả thi cử, bao gồm kỹ thuật Pomodoro, spaced repetition và active recall.', status:'published', views:8234, readTime:7, tags:['học-tập','mẹo','kỳ-thi'], pinned:false, color:'#10b981', icon:'book-open' },
            { id:3, title:'Thông báo: Khuyến mãi 50% gói Premium tháng 6/2025', category:'Khuyến mãi', author:'Admin EduNova', date:'05/06/2025', excerpt:'Nhân dịp hè 2025, EduNova triển khai chương trình ưu đãi đặc biệt giảm 50% cho tất cả học viên đăng ký gói Premium trước ngày 30/06/2025.', status:'published', views:12450, readTime:2, tags:['khuyến-mãi','premium'], pinned:false, color:'#f59e0b', icon:'tag' },
            { id:4, title:'Hướng dẫn sử dụng tính năng Lịch học cá nhân hóa', category:'Hướng dẫn', author:'Đinh Thị Mai', date:'01/06/2025', excerpt:'Bài viết hướng dẫn chi tiết cách thiết lập và sử dụng lịch học thông minh trên EduNova, bao gồm tích hợp Google Calendar và nhắc nhở tự động.', status:'published', views:3102, readTime:6, tags:['hướng-dẫn','lịch-học'], pinned:false, color:'#8b5cf6', icon:'calendar' },
            { id:5, title:'[NHÁP] Giới thiệu khóa học Data Science mới', category:'Học tập', author:'Admin EduNova', date:'09/06/2025', excerpt:'Khóa học Data Science toàn diện sắp ra mắt trên EduNova, bao gồm Python, Machine Learning, Data Visualization và dự án thực tế...', status:'draft', views:0, readTime:4, tags:['data-science','khóa-học'], pinned:false, color:'#06b6d4', icon:'database' },
            { id:6, title:'Sự kiện: Webinar "Học lập trình từ đầu" – 20/06/2025', category:'Sự kiện', author:'Ngô Văn Nam', date:'20/06/2025', excerpt:'EduNova tổ chức buổi webinar miễn phí dành cho người mới bắt đầu học lập trình, với sự tham gia của các chuyên gia hàng đầu từ các công ty công nghệ lớn.', status:'scheduled', views:0, readTime:3, tags:['webinar','sự-kiện','miễn-phí'], pinned:false, color:'#ec4899', icon:'video' },
        ],

        get filteredArticles() {
            return this.articles.filter(a => {
                const q = this.search.toLowerCase();
                return (this.activeTab==='all' || a.status===this.activeTab)
                    && (!q || a.title.toLowerCase().includes(q))
                    && (!this.filterCat || a.category===this.filterCat);
            });
        },
        get wordCount() {
            const text = (this.form.content || '').replace(/<[^>]*>/g,'');
            return text.trim() ? text.trim().split(/\s+/).length : 0;
        },
        get readTime() { return Math.max(1, Math.ceil(this.wordCount / 200)); },

        init() { this.$nextTick(() => lucide.createIcons()); },

        openEditor(art=null) {
            this.editingArticle = art;
            this.form = art ? { ...art } : {
                title:'', category:'Học tập', author:'Admin EduNova',
                content:'', excerpt:'', tags:[], status:'draft',
                pinned:false, scheduleDate:'',
            };
            this.editorView = 'write';
            this.editorOpen = true;
            this.$nextTick(() => {
                const el = document.getElementById('editorContent');
                if (el) el.innerHTML = this.form.content || '';
                lucide.createIcons();
            });
        },
        onEditorInput(e) {
            this.form.content = e.target.innerHTML;
        },
        execCmd(cmd, val=null) {
            document.getElementById('editorContent').focus();
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
        saveArticle(status) {
            this.form.status = status;
            this.form.content = document.getElementById('editorContent')?.innerHTML || this.form.content;
            if (this.editingArticle) {
                const idx = this.articles.findIndex(a => a.id===this.editingArticle.id);
                if (idx !== -1) this.articles.splice(idx, 1, { ...this.articles[idx], ...this.form });
            } else {
                this.articles.unshift({
                    id: Date.now(), ...this.form,
                    date: new Date().toLocaleDateString('vi-VN'),
                    views:0, readTime: this.readTime,
                    color:'#6366f1', icon:'file-text',
                });
                this.tabs[0].count++;
            }
            this.updateTabCounts();
            this.editorOpen = false;
            this.showToast(status==='published' ? 'Đã đăng bài thành công!' : 'Đã lưu nháp');
        },
        togglePin(art)    { art.pinned = !art.pinned; this.showToast(art.pinned ? 'Đã ghim bài viết' : 'Đã bỏ ghim'); },
        toggleStatus(art) {
            art.status = art.status==='published' ? 'draft' : 'published';
            this.updateTabCounts();
            this.showToast(art.status==='published' ? 'Đã xuất bản bài viết' : 'Đã chuyển về nháp');
        },
        deleteArticle(art) {
            if (!confirm(`Xóa bài "${art.title}"?`)) return;
            this.articles = this.articles.filter(a => a.id!==art.id);
            this.updateTabCounts();
            this.showToast('Đã xóa bài viết', 'error');
        },
        updateTabCounts() {
            this.tabs[0].count = this.articles.length;
            this.tabs[1].count = this.articles.filter(a=>a.status==='published').length;
            this.tabs[2].count = this.articles.filter(a=>a.status==='draft').length;
            this.tabs[3].count = this.articles.filter(a=>a.status==='scheduled').length;
        },
        showToast(msg, type='success') {
            this.toast={show:true,msg,type};
            this.$nextTick(()=>lucide.createIcons());
            setTimeout(()=>this.toast.show=false,2500);
        },
    };
}
</script>
@endpush
@endsection