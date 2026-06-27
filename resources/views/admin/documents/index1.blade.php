@extends('layouts.app')
@section('title', 'Quản lý tài liệu - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }
    .doc-card { transition: all .2s; }
    .doc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,.1); }
</style>
@endpush

@section('content')
<div x-data="docManager()" x-cloak class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Admin</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs text-slate-400">Tài liệu</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Quản lý tài liệu</h1>
            <p class="text-slate-500 text-sm mt-0.5">Xét duyệt, thống kê và quản lý kho tài liệu học tập</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl">
                <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                <span class="text-xs font-bold text-amber-700" x-text="pendingDocs.length + ' chờ duyệt'"></span>
            </span>
            <button @click="tab='pending'"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95">
                <i data-lucide="shield-check" class="w-4 h-4"></i> Xét duyệt ngay
            </button>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-for="kpi in kpis" :key="kpi.key">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 relative overflow-hidden">
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
    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-2xl p-1.5 w-fit">
        <template x-for="t in tabs" :key="t.key">
            <button @click="tab=t.key"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
                :class="tab===t.key ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-700'">
                <i :data-lucide="t.icon" class="w-4 h-4"></i>
                <span x-text="t.label"></span>
                <span x-show="t.count > 0"
                    class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                    :class="tab===t.key ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-600'"
                    x-text="t.count">
                </span>
            </button>
        </template>
    </div>

    {{-- ═══ TAB: PENDING (xét duyệt) ═══ --}}
    <div x-show="tab==='pending'" class="space-y-4">
        <div x-show="pendingDocs.length===0" class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
            <i data-lucide="check-circle" class="w-12 h-12 text-emerald-200 mx-auto mb-3"></i>
            <p class="text-slate-500 font-semibold">Không có tài liệu nào chờ duyệt</p>
            <p class="text-slate-400 text-sm mt-1">Tất cả tài liệu đã được xét duyệt</p>
        </div>
        <template x-for="doc in pendingDocs" :key="doc.id">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col sm:flex-row gap-4">
                {{-- File icon --}}
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                    :style="`background:${fileColor(doc.type)}15`">
                    <i :data-lucide="fileIcon(doc.type)" class="w-7 h-7" :style="`color:${fileColor(doc.type)}`"></i>
                </div>
                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <h3 class="font-black text-slate-900 text-base" x-text="doc.title"></h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Bởi <span class="font-semibold text-slate-700" x-text="doc.author"></span>
                                · <span x-text="doc.subject"></span>
                                · <span x-text="doc.uploadDate"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                                x-text="doc.type.toUpperCase()">
                            </span>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full" x-text="doc.size"></span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 line-clamp-2" x-text="doc.description"></p>
                    <div class="flex items-center gap-3 mt-3 flex-wrap">
                        <button @click="previewDoc(doc)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-all">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Xem trước
                        </button>
                        <button @click="approveDoc(doc)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 rounded-xl hover:bg-emerald-600 transition-all">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Phê duyệt
                        </button>
                        <button @click="rejectDoc(doc)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-all">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Từ chối
                        </button>
                        <div class="ml-auto flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span x-text="doc.downloads + ' lượt tải'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ═══ TAB: ALL DOCS ═══ --}}
    <div x-show="tab==='all'" class="space-y-4">
        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-48">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="docSearch" type="text" placeholder="Tìm tài liệu..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <select x-model="docFilterType" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả loại</option>
                <option value="pdf">PDF</option>
                <option value="docx">DOCX</option>
                <option value="pptx">PPTX</option>
                <option value="xlsx">XLSX</option>
            </select>
            <select x-model="docFilterStatus" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả trạng thái</option>
                <option value="approved">Đã duyệt</option>
                <option value="pending">Chờ duyệt</option>
                <option value="rejected">Từ chối</option>
            </select>
            <div class="flex items-center gap-2 ml-auto">
                <button @click="viewMode='grid'" class="p-2 rounded-xl transition-all" :class="viewMode==='grid' ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-500'">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                </button>
                <button @click="viewMode='list'" class="p-2 rounded-xl transition-all" :class="viewMode==='list' ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-500'">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Grid view --}}
        <div x-show="viewMode==='grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="doc in filteredAllDocs" :key="doc.id">
                <div class="doc-card bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer" @click="previewDoc(doc)">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                            :style="`background:${fileColor(doc.type)}15`">
                            <i :data-lucide="fileIcon(doc.type)" class="w-6 h-6" :style="`color:${fileColor(doc.type)}`"></i>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :class="{
                                'bg-emerald-50 text-emerald-600': doc.status==='approved',
                                'bg-amber-50 text-amber-600':    doc.status==='pending',
                                'bg-rose-50 text-rose-600':      doc.status==='rejected',
                            }"
                            x-text="{'approved':'Đã duyệt','pending':'Chờ duyệt','rejected':'Từ chối'}[doc.status]">
                        </span>
                    </div>
                    <h3 class="font-black text-slate-900 text-sm mb-1 line-clamp-2" x-text="doc.title"></h3>
                    <p class="text-xs text-slate-400" x-text="doc.subject + ' · ' + doc.size"></p>
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span x-text="doc.downloads"></span>
                        </span>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <i data-lucide="eye" class="w-3 h-3"></i>
                            <span x-text="doc.views"></span>
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                            x-text="doc.type.toUpperCase()">
                        </span>
                    </div>
                </div>
            </template>
        </div>

        {{-- List view --}}
        <div x-show="viewMode==='list'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Tài liệu</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Môn học</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Loại</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Tải</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Xem</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="doc in filteredAllDocs" :key="doc.id">
                        <tr class="tbl-row border-b border-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                        :style="`background:${fileColor(doc.type)}15`">
                                        <i :data-lucide="fileIcon(doc.type)" class="w-4 h-4" :style="`color:${fileColor(doc.type)}`"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800" x-text="doc.title"></p>
                                        <p class="text-[10px] text-slate-400" x-text="doc.author + ' · ' + doc.uploadDate"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600" x-text="doc.subject"></td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                                    x-text="doc.type.toUpperCase()">
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-700" x-text="doc.downloads"></td>
                            <td class="px-4 py-3.5 text-right text-xs text-slate-500" x-text="doc.views"></td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-50 text-emerald-600': doc.status==='approved',
                                        'bg-amber-50 text-amber-600':    doc.status==='pending',
                                        'bg-rose-50 text-rose-600':      doc.status==='rejected',
                                    }"
                                    x-text="{'approved':'Đã duyệt','pending':'Chờ duyệt','rejected':'Từ chối'}[doc.status]">
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="previewDoc(doc)" class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    </button>
                                    <button @click="deleteDoc(doc)" class="p-1.5 rounded-lg hover:bg-rose-50 transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ TAB: STATS ═══ --}}
    <div x-show="tab==='stats'" class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Type breakdown --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-black text-slate-900 mb-5">Phân loại theo định dạng</h2>
                <div class="space-y-3">
                    <template x-for="t in typeStats" :key="t.type">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg flex items-center justify-center" :style="`background:${t.color}15`">
                                        <i :data-lucide="fileIcon(t.type)" class="w-3.5 h-3.5" :style="`color:${t.color}`"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700" x-text="t.type.toUpperCase()"></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="font-black text-slate-800" x-text="t.count + ' tài liệu'"></span>
                                    <span class="text-slate-400" x-text="t.pct + '%'"></span>
                                </div>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" :style="`width:${t.pct}%;background:${t.color}`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Top downloads --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-black text-slate-900 mb-5">Tài liệu được tải nhiều nhất</h2>
                <div class="space-y-3">
                    <template x-for="(doc, i) in topDocs" :key="doc.id">
                        <div class="flex items-center gap-3">
                            <span class="w-6 text-center text-xs font-black"
                                :class="i===0?'text-amber-500':i===1?'text-slate-400':i===2?'text-amber-700':'text-slate-300'"
                                x-text="i+1">
                            </span>
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                :style="`background:${fileColor(doc.type)}15`">
                                <i :data-lucide="fileIcon(doc.type)" class="w-4 h-4" :style="`color:${fileColor(doc.type)}`"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate" x-text="doc.title"></p>
                                <p class="text-[10px] text-slate-400" x-text="doc.subject"></p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-black text-indigo-600 shrink-0">
                                <i data-lucide="download" class="w-3 h-3"></i>
                                <span x-text="doc.downloads"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Upload by subject --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-base font-black text-slate-900 mb-5">Tài liệu theo môn học</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
                <template x-for="s in subjectStats" :key="s.subject">
                    <div class="text-center p-4 bg-slate-50 rounded-2xl hover:bg-indigo-50 transition-all cursor-pointer">
                        <p class="text-2xl font-black text-slate-900" x-text="s.count"></p>
                        <p class="text-xs text-slate-500 mt-1 font-semibold" x-text="s.subject"></p>
                        <div class="mt-2 h-1 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-400 rounded-full" :style="`width:${s.pct}%`"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- PREVIEW MODAL --}}
    <div x-show="previewOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="previewOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :style="`background:${fileColor(previewDoc?.type)}15`">
                        <i :data-lucide="fileIcon(previewDoc?.type)" class="w-5 h-5" :style="`color:${fileColor(previewDoc?.type)}`"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900" x-text="previewDoc?.title"></h3>
                        <p class="text-xs text-slate-400" x-text="previewDoc?.author + ' · ' + previewDoc?.size"></p>
                    </div>
                </div>
                <button @click="previewOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>
            <div class="p-6">
                {{-- Mock preview area --}}
                <div class="bg-slate-50 rounded-2xl h-64 flex items-center justify-center border-2 border-dashed border-slate-200 mb-5">
                    <div class="text-center">
                        <i :data-lucide="fileIcon(previewDoc?.type)" class="w-16 h-16 mx-auto mb-3" :style="`color:${fileColor(previewDoc?.type)}`"></i>
                        <p class="text-sm font-bold text-slate-600" x-text="previewDoc?.title"></p>
                        <p class="text-xs text-slate-400 mt-1" x-text="previewDoc?.type?.toUpperCase() + ' · ' + previewDoc?.size"></p>
                    </div>
                </div>
                <div class="space-y-2 mb-5">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mô tả</h4>
                    <p class="text-sm text-slate-700" x-text="previewDoc?.description"></p>
                </div>
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900" x-text="previewDoc?.downloads"></p>
                        <p class="text-[10px] text-slate-400">Lượt tải</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900" x-text="previewDoc?.views"></p>
                        <p class="text-[10px] text-slate-400">Lượt xem</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900" x-text="previewDoc?.uploadDate"></p>
                        <p class="text-[10px] text-slate-400">Ngày tải lên</p>
                    </div>
                </div>
                <div x-show="previewDoc?.status==='pending'" class="flex gap-3">
                    <button @click="approveDoc(previewDoc); previewOpen=false"
                        class="flex-1 py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 transition-all">
                        <i data-lucide="check" class="w-4 h-4 inline-block mr-1"></i>Phê duyệt
                    </button>
                    <button @click="rejectDoc(previewDoc); previewOpen=false"
                        class="flex-1 py-2.5 border border-rose-200 text-rose-600 rounded-xl text-sm font-semibold hover:bg-rose-50 transition-all">
                        <i data-lucide="x" class="w-4 h-4 inline-block mr-1"></i>Từ chối
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="toast.type==='success'?'bg-emerald-600':toast.type==='error'?'bg-rose-600':'bg-amber-500'">
        <i :data-lucide="toast.type==='success'?'check-circle':'alert-circle'" class="w-4 h-4"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function docManager() {
    return {
        tab: 'pending',
        viewMode: 'grid',
        docSearch: '', docFilterType: '', docFilterStatus: '',
        previewOpen: false, previewDoc: null,
        toast: { show:false, msg:'', type:'success' },

        tabs: [
            { key:'pending', label:'Chờ duyệt', icon:'clock',        count: 3 },
            { key:'all',     label:'Tất cả',    icon:'folder-open',   count: 0 },
            { key:'stats',   label:'Thống kê',  icon:'bar-chart-2',   count: 0 },
        ],
        kpis: [
            { key:'total',    icon:'files',        color:'#6366f1', label:'Tổng tài liệu',   value:'1,247', sub:'Trong kho lưu trữ' },
            { key:'pending',  icon:'clock',        color:'#f59e0b', label:'Chờ duyệt',        value:'3',     sub:'Cần xét duyệt hôm nay' },
            { key:'download', icon:'download',     color:'#10b981', label:'Tổng lượt tải',   value:'48.2K', sub:'+1,240 tuần này' },
            { key:'size',     icon:'hard-drive',   color:'#8b5cf6', label:'Dung lượng',       value:'12.4 GB', sub:'Supabase Storage' },
        ],

        docs: [
            { id:1,  title:'Bài giảng Toán cao cấp A1 - Chương 1',      author:'Lê Văn Hùng',    subject:'Toán học',   type:'pdf',  size:'4.2 MB', status:'pending',  downloads:0,   views:12,   uploadDate:'10/06/2025', description:'Tài liệu bài giảng toán cao cấp A1 chương 1, bao gồm lý thuyết và bài tập có lời giải đầy đủ.' },
            { id:2,  title:'Đề thi Python cuối kỳ 2024',                 author:'Đinh Thị Mai',   subject:'CNTT',       type:'docx', size:'1.1 MB', status:'pending',  downloads:0,   views:5,    uploadDate:'09/06/2025', description:'Đề thi lập trình Python kỳ 2 năm 2024, bao gồm phần lý thuyết và thực hành.' },
            { id:3,  title:'Slide Tiếng Anh giao tiếp - Unit 5',         author:'Nguyễn T. Hà',   subject:'Ngoại ngữ',  type:'pptx', size:'8.7 MB', status:'pending',  downloads:0,   views:8,    uploadDate:'08/06/2025', description:'Slide bài giảng tiếng Anh giao tiếp unit 5, chủ đề du lịch và văn hóa.' },
            { id:4,  title:'Vật lý đại cương - Cơ học',                  author:'Lê Văn Hùng',    subject:'Vật lý',     type:'pdf',  size:'6.3 MB', status:'approved', downloads:892, views:2341, uploadDate:'01/06/2025', description:'Giáo trình vật lý đại cương phần cơ học.' },
            { id:5,  title:'Bảng công thức Toán rút gọn',                author:'Ngô Văn Nam',    subject:'Toán học',   type:'pdf',  size:'0.8 MB', status:'approved', downloads:2140,views:5820, uploadDate:'15/05/2025', description:'Tổng hợp công thức toán học thường dùng.' },
            { id:6,  title:'Excel nâng cao - PivotTable & Chart',         author:'Đinh Thị Mai',   subject:'CNTT',       type:'xlsx', size:'3.2 MB', status:'approved', downloads:437, views:1203, uploadDate:'20/05/2025', description:'Hướng dẫn sử dụng PivotTable và Chart trong Excel nâng cao.' },
            { id:7,  title:'Kỹ năng thuyết trình hiệu quả',              author:'Vũ Thị Hoa',     subject:'Kỹ năng',    type:'pptx', size:'12.1 MB',status:'approved', downloads:1823,views:4210, uploadDate:'10/05/2025', description:'Slide hướng dẫn kỹ năng thuyết trình chuyên nghiệp.' },
            { id:8,  title:'Đề cương ôn thi Hóa học',                    author:'Trần Thị Lan',   subject:'Hóa học',    type:'docx', size:'2.5 MB', status:'rejected', downloads:0,   views:3,    uploadDate:'05/06/2025', description:'Đề cương ôn tập hóa học dành cho kỳ thi cuối học kỳ.' },
        ],

        typeStats:    [ { type:'pdf',  count:623, pct:50, color:'#ef4444' }, { type:'docx', count:312, pct:25, color:'#3b82f6' }, { type:'pptx', count:187, pct:15, color:'#f59e0b' }, { type:'xlsx', count:125, pct:10, color:'#10b981' } ],
        subjectStats: [ { subject:'Toán học', count:287, pct:100 }, { subject:'CNTT', count:234, pct:82 }, { subject:'Ngoại ngữ', count:198, pct:69 }, { subject:'Vật lý', count:156, pct:54 }, { subject:'Hóa học', count:124, pct:43 }, { subject:'Kỹ năng', count:248, pct:86 } ],

        get pendingDocs() { return this.docs.filter(d=>d.status==='pending'); },
        get topDocs()     { return [...this.docs].filter(d=>d.status==='approved').sort((a,b)=>b.downloads-a.downloads).slice(0,5); },
        get filteredAllDocs() {
            return this.docs.filter(d => {
                const q = this.docSearch.toLowerCase();
                return (!q || d.title.toLowerCase().includes(q) || d.author.toLowerCase().includes(q))
                    && (!this.docFilterType   || d.type   === this.docFilterType)
                    && (!this.docFilterStatus || d.status === this.docFilterStatus);
            });
        },

        init() { this.$nextTick(() => lucide.createIcons()); },

        fileIcon(type) { return { pdf:'file-text', docx:'file', pptx:'presentation', xlsx:'table-2' }[type] || 'file'; },
        fileColor(type) { return { pdf:'#ef4444', docx:'#3b82f6', pptx:'#f59e0b', xlsx:'#10b981' }[type] || '#64748b'; },

        approveDoc(doc) { doc.status='approved'; this.updateTabCount(); this.showToast('Đã phê duyệt: ' + doc.title); },
        rejectDoc(doc)  { doc.status='rejected'; this.updateTabCount(); this.showToast('Đã từ chối: ' + doc.title, 'error'); },
        deleteDoc(doc)  { if (!confirm('Xóa tài liệu này?')) return; this.docs=this.docs.filter(d=>d.id!==doc.id); this.showToast('Đã xóa tài liệu', 'error'); },
        previewDoc(doc) { this.previewDoc=doc; this.previewOpen=true; this.$nextTick(()=>lucide.createIcons()); },
        updateTabCount() { this.tabs[0].count = this.pendingDocs.length; },
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