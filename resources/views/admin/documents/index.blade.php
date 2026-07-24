@extends('layouts.app')
@section('title', 'Quản lý tài liệu - EduNova Admin')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .tbl-row { transition: background .15s; }
    .tbl-row:hover { background: #f8fafc; }
    .doc-card { transition: all .2s; }
    .doc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99,102,241,.1); }
    .skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
                background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius:8px; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
    @keyframes pulse-ai { 0%,100%{opacity:1} 50%{opacity:.55} }
    .ai-pulse { animation: pulse-ai 1.2s ease-in-out infinite; }
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
                <span class="text-xs font-bold text-amber-700" x-text="tabs[0].count + ' chờ duyệt'"></span>
            </span>
            <button @click="tab='pending'"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95">
                <i data-lucide="shield-check" class="w-4 h-4"></i> Xét duyệt ngay
            </button>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <template x-if="kpis.length === 0">
            <template x-for="i in [1,2,3,4]" :key="i">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="skeleton h-9 w-9 rounded-xl mb-3"></div>
                    <div class="skeleton h-7 w-20 mb-2"></div>
                    <div class="skeleton h-3 w-28"></div>
                </div>
            </template>
        </template>
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
    <div class="flex items-center justify-between gap-3 flex-wrap">
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

        {{-- Nút xét duyệt hàng loạt bằng AI, chỉ hiện ở tab chờ duyệt --}}
        <div x-show="tab==='pending' && pendingDocs.length > 0"
            class="flex items-center gap-3 px-4 py-2.5 bg-gradient-to-r from-violet-50 to-indigo-50 border border-violet-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="bot" class="w-4 h-4 text-violet-600" :class="bulkAiRunning ? 'ai-pulse' : ''"></i>
                <span class="text-sm font-semibold text-violet-700"
                    x-text="bulkAiRunning ? `AI đang xét duyệt (${bulkAiDone}/${bulkAiTotal})...` : 'Tự động xét duyệt bằng AI'"></span>
            </div>

            <button type="button"
                @click="aiReviewAllPending()"
                :disabled="bulkAiRunning"
                :aria-checked="bulkAiRunning.toString()"
                role="switch"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 ease-in-out
                    disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-violet-300 focus:ring-offset-1"
                :class="bulkAiRunning ? 'bg-violet-600' : 'bg-slate-200'">
                <span class="inline-block transform rounded-full bg-white shadow transition-transform duration-200 ease-in-out"
                    style="height:18px;width:18px"
                    :class="bulkAiRunning ? 'translate-x-6' : 'translate-x-1'">
                </span>
            </button>
        </div>
    </div>

    {{-- ═══ TAB: PENDING ═══ --}}
    <div x-show="tab==='pending'" class="space-y-4">

        <div x-show="loading.pending" class="space-y-3">
            <template x-for="i in [1,2,3]" :key="i">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 flex gap-4">
                    <div class="skeleton w-14 h-14 rounded-2xl shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="skeleton h-5 w-2/3"></div>
                        <div class="skeleton h-3 w-1/3"></div>
                        <div class="skeleton h-3 w-full"></div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading.pending && pendingDocs.length===0"
            class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
            <i data-lucide="check-circle" class="w-12 h-12 text-emerald-200 mx-auto mb-3"></i>
            <p class="text-slate-500 font-semibold">Không có tài liệu nào chờ duyệt</p>
            <p class="text-slate-400 text-sm mt-1">Tất cả tài liệu đã được xét duyệt</p>
        </div>

        <template x-for="doc in pendingDocs" :key="doc.id">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col sm:flex-row gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                    :style="`background:${fileColor(doc.type)}15`">
                    <i :data-lucide="fileIcon(doc.type)" class="w-7 h-7" :style="`color:${fileColor(doc.type)}`"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <h3 class="font-black text-slate-900 text-base" x-text="doc.name"></h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Bởi <span class="font-semibold text-slate-700" x-text="doc.author"></span>
                                <span x-show="doc.subject"> · <span x-text="doc.subject"></span></span>
                                · <span x-text="doc.upload_date"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span x-show="doc.type" class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                                x-text="doc.type?.toUpperCase()"></span>
                            <span x-show="doc.size" class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"
                                x-text="doc.size"></span>
                            {{-- MỚI: badge kết quả scan bảo mật --}}
                            <span x-show="doc.scan_status === 'flagged'"
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 flex items-center gap-1">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i> Cần chú ý
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 line-clamp-2" x-text="doc.description"></p>
                    <div class="flex items-center gap-3 mt-3 flex-wrap">
                        <button @click="openPreview(doc)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-all">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i> Xem trước
                        </button>

                        {{-- FIX: dùng processingIds thay vì doc._loading --}}
                        <button @click="approveDoc(doc)"
                            :disabled="processingIds.includes(doc.id)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 rounded-xl hover:bg-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg x-show="processingIds.includes(doc.id)" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <i x-show="!processingIds.includes(doc.id)" data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span x-text="processingIds.includes(doc.id) ? 'Đang xử lý...' : 'Phê duyệt'"></span>
                        </button>

                        <button @click="openRejectModal(doc)"
                            :disabled="processingIds.includes(doc.id)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Từ chối
                        </button>

                        {{-- NÚT MỚI: tự động xét duyệt bằng AI cho từng tài liệu --}}
                        <button @click="aiReviewDoc(doc)"
                            :disabled="processingIds.includes(doc.id)"
                            title="AI phân tích tên/mô tả tài liệu và tự quyết định phê duyệt/từ chối"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-violet-600 bg-violet-50 rounded-xl hover:bg-violet-100 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="bot" class="w-3.5 h-3.5" :class="processingIds.includes(doc.id) ? 'ai-pulse' : ''"></i>
                            <span x-text="processingIds.includes(doc.id) ? 'AI đang xét...' : 'AI xét duyệt'"></span>
                        </button>

                        <div class="ml-auto flex items-center gap-1 text-xs text-slate-400">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span x-text="(doc.downloads ?? 0) + ' lượt tải'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ═══ TAB: ALL DOCS ═══ --}}
    <div x-show="tab==='all'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-48">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input x-model="docSearch" type="text" placeholder="Tìm tên tài liệu, tác giả..."
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
                   <option value="hidden">Đã ẩn</option>
            </select>
            <select x-model="docFilterSubject" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-slate-700">
                <option value="">Tất cả môn học</option>
                <template x-for="s in subjects" :key="s">
                    <option :value="s" x-text="s"></option>
                </template>
            </select>
            <button @click="docSearch='';docFilterType='';docFilterStatus='';docFilterSubject=''"
                class="px-3 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-500 hover:bg-slate-50 transition-all">
                <i data-lucide="x" class="w-4 h-4 inline-block"></i>
            </button>
            <div class="flex items-center gap-2 ml-auto">
                <button @click="viewMode='grid'" class="p-2 rounded-xl transition-all"
                    :class="viewMode==='grid' ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-500'">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                </button>
                <button @click="viewMode='list'" class="p-2 rounded-xl transition-all"
                    :class="viewMode==='list' ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-500'">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div x-show="loading.all && viewMode==='grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="i in [1,2,3,4,5,6]" :key="i">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3">
                    <div class="skeleton h-12 w-12 rounded-2xl"></div>
                    <div class="skeleton h-4 w-3/4"></div>
                    <div class="skeleton h-3 w-1/2"></div>
                </div>
            </template>
        </div>

        <div x-show="!loading.all && viewMode==='grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="doc in filteredAllDocs" :key="doc.id">
                <div class="doc-card bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer" @click="openPreview(doc)">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :style="`background:${fileColor(doc.type)}15`">
                            <i :data-lucide="fileIcon(doc.type)" class="w-6 h-6" :style="`color:${fileColor(doc.type)}`"></i>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :class="statusBadgeClass(doc)"
                            x-text="statusLabel(doc)">
                        </span>
                    </div>
                    <h3 class="font-black text-slate-900 text-sm mb-1 line-clamp-2" x-text="doc.name"></h3>
                    <p class="text-xs text-slate-400">
                        <span x-text="doc.subject || 'Chưa phân loại'"></span>
                        <span x-show="doc.size"> · <span x-text="doc.size"></span></span>
                    </p>
                    <p x-show="doc.status==='hidden'"
                        class="text-[10px] text-slate-500 mt-1.5 flex items-center gap-1">
                        <i data-lucide="eye-off" class="w-3 h-3"></i> Đang ẩn khỏi kho tài liệu
                    </p>
                    <p x-show="doc.status==='rejected' && doc.rejection_reason"
                        class="text-[10px] text-rose-500 mt-1.5 line-clamp-1 italic"
                        x-text="'Lý do: ' + doc.rejection_reason"></p>
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            <span x-text="doc.downloads ?? 0"></span>
                        </span>
                        <span x-show="doc.rate > 0" class="text-xs text-amber-500 flex items-center gap-0.5">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400"></i>
                            <span x-text="doc.rate"></span>
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                            x-text="doc.type?.toUpperCase() || 'FILE'">
                        </span>
                    </div>
                </div>
            </template>
            <div x-show="filteredAllDocs.length===0" class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200">
                <i data-lucide="folder-open" class="w-10 h-10 text-slate-200 mx-auto mb-3"></i>
                <p class="text-sm text-slate-400">Không tìm thấy tài liệu nào</p>
            </div>
        </div>

        <div x-show="!loading.all && viewMode==='list'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden" >
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Tài liệu</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Môn học</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Loại</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Tải</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Rate</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Trạng thái</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Scan</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Ngày duyệt</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Ngày duyệt</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="doc in filteredAllDocs" :key="doc.id">
                        <tr @click="openPreview(doc)" class="tbl-row border-b border-slate-50 cursor-pointer hover:bg-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :style="`background:${fileColor(doc.type)}15`">
                                        <i :data-lucide="fileIcon(doc.type)" class="w-4 h-4" :style="`color:${fileColor(doc.type)}`"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800 max-w-xs truncate" x-text="doc.name"></p>
                                        <p class="text-[10px] text-slate-400" x-text="doc.author + ' · ' + doc.upload_date"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600" x-text="doc.subject || '—'"></td>
                            <td class="px-4 py-3.5 text-center">
                                <span x-show="doc.type" class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :style="`background:${fileColor(doc.type)}15;color:${fileColor(doc.type)}`"
                                    x-text="doc.type?.toUpperCase()"></span>
                                <span x-show="!doc.type" class="text-[10px] text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-700" x-text="doc.downloads ?? 0"></td>
                            <td class="px-4 py-3.5 text-right">
                                <span x-show="doc.rate > 0" class="text-xs font-bold text-amber-500 flex items-center justify-end gap-0.5">
                                    <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                    <span x-text="doc.rate"></span>
                                </span>
                                <span x-show="!doc.rate" class="text-xs text-slate-300">—</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="statusBadgeClass(doc)"
                                    x-text="statusLabel(doc)">
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span x-show="doc.scan_status === 'flagged'"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"
                                    title="Hệ thống phát hiện điểm cần kiểm tra kỹ hơn">⚠ Flagged</span>
                                <span x-show="doc.scan_status === 'passed'"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">✓ OK</span>
                                <span x-show="!doc.scan_status" class="text-[10px] text-slate-300">—</span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-[10px] text-slate-400" x-text="doc.reviewed_at ?? '—'"></td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- <button @click.stop="openPreview(doc)" class="p-1.5 rounded-lg hover:bg-indigo-50 transition-all">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    </button> -->
                                    <button x-show="doc.status==='pending'" @click.stop="approveDoc(doc)"
                                        :disabled="processingIds.includes(doc.id)"
                                        title="phê duyệt tài liệu"
                                        class="p-1.5 rounded-lg hover:bg-emerald-50 disabled:opacity-50 transition-all">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </button>
                                    <button x-show="doc.status==='pending'" @click.stop="openRejectModal(doc)"
                                        :disabled="processingIds.includes(doc.id)"
                                        title="từ chối tài liệu"
                                        class="p-1.5 rounded-lg hover:bg-amber-50 disabled:opacity-50 transition-all">
                                        <i data-lucide="x" class="w-3.5 h-3.5 text-amber-500"></i>
                                    </button>
                                    <button x-show="doc.status==='pending'" @click.stop="toggleAiReview(doc)"
                                        :disabled="processingIds.includes(doc.id)"
                                        title="AI xét duyệt"
                                        :class="doc.aiReviewEnabled ? 'p-1.5 rounded-lg bg-violet-100 text-violet-700' : 'p-1.5 rounded-lg hover:bg-violet-50 text-violet-500'"
                                        class="disabled:opacity-50 transition-all">
                                        <i data-lucide="bot" class="w-3.5 h-3.5" :class="processingIds.includes(doc.id) ? 'ai-pulse' : ''"></i>
                                    </button>
                                    <button x-show="doc.status==='approved' || doc.status==='hidden'" @click.stop="toggleVisibility(doc)"
                                        :disabled="processingIds.includes(doc.id)"
                                        :title="doc.status==='hidden' ? 'Hiện lại tài liệu' : 'Ẩn tài liệu khỏi kho'"
                                        class="p-1.5 rounded-lg disabled:opacity-50 transition-all"
                                        :class="doc.status==='hidden' ? 'bg-slate-100 text-slate-400' : 'hover:bg-slate-100 text-slate-500'">
                                        <i :data-lucide="doc.status==='hidden' ? 'eye-off' : 'eye'" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <!-- <button @click.stop="deleteDoc(doc)" class="p-1.5 rounded-lg hover:bg-rose-50 transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-500"></i>
                                    </button> -->
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredAllDocs.length===0">
                        <td colspan="9" class="py-14 text-center">
                            <i data-lucide="folder-open" class="w-10 h-10 text-slate-200 mx-auto mb-3"></i>
                            <p class="text-sm text-slate-400">Không tìm thấy tài liệu nào</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ TAB: STATS ═══ --}}
    <div x-show="tab==='stats'" class="space-y-5">
        <div x-show="loading.stats" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="skeleton h-5 w-40 mb-5"></div>
                <div class="space-y-4">
                    <template x-for="i in [1,2,3,4]" :key="i">
                        <div class="space-y-1.5">
                            <div class="skeleton h-3 w-full"></div>
                            <div class="skeleton h-2 w-full rounded-full"></div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="skeleton h-5 w-48 mb-5"></div>
                <div class="space-y-3">
                    <template x-for="i in [1,2,3,4,5]" :key="i">
                        <div class="flex items-center gap-3">
                            <div class="skeleton w-8 h-8 rounded-xl shrink-0"></div>
                            <div class="flex-1 space-y-1">
                                <div class="skeleton h-3 w-3/4"></div>
                                <div class="skeleton h-2 w-1/4"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="!loading.stats" class="space-y-5">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-base font-black text-slate-900 mb-5">Phân loại theo định dạng</h2>
                    <div x-show="typeStats.length===0" class="py-8 text-center text-sm text-slate-400">Không có dữ liệu</div>
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
                                    <div class="h-full rounded-full transition-all duration-700" :style="`width:${t.pct}%;background:${t.color}`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-base font-black text-slate-900 mb-5">Tài liệu được tải nhiều nhất</h2>
                    <div x-show="topDocs.length===0" class="py-8 text-center text-sm text-slate-400">Không có dữ liệu</div>
                    <div class="space-y-3">
                        <template x-for="(doc, i) in topDocs" :key="doc.id">
                            <div class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 p-1.5 rounded-xl transition-all" @click="openPreview(doc)">
                                <span class="w-6 text-center text-xs font-black shrink-0"
                                    :class="i===0?'text-amber-500':i===1?'text-slate-400':i===2?'text-amber-700':'text-slate-300'"
                                    x-text="i+1"></span>
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :style="`background:${fileColor(doc.type)}15`">
                                    <i :data-lucide="fileIcon(doc.type)" class="w-4 h-4" :style="`color:${fileColor(doc.type)}`"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate" x-text="doc.name"></p>
                                    <p class="text-[10px] text-slate-400" x-text="doc.subject || '—'"></p>
                                </div>
                                <div class="flex items-center gap-1 text-xs font-black text-indigo-600 shrink-0">
                                    <i data-lucide="download" class="w-3 h-3"></i>
                                    <span x-text="doc.downloads ?? 0"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-black text-slate-900 mb-5">Tài liệu theo môn học (Tags)</h2>
                <div x-show="subjectStats.length===0" class="py-8 text-center text-sm text-slate-400">Không có dữ liệu</div>
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
                    <template x-for="s in subjectStats" :key="s.subject">
                        <div class="text-center p-4 bg-slate-50 rounded-2xl hover:bg-indigo-50 transition-all cursor-pointer"
                            @click="docFilterSubject=s.subject; tab='all'">
                            <p class="text-2xl font-black text-slate-900" x-text="s.count"></p>
                            <p class="text-xs text-slate-500 mt-1 font-semibold line-clamp-2" x-text="s.subject"></p>
                            <div class="mt-2 h-1 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-400 rounded-full" :style="`width:${s.pct}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-base font-black text-slate-900 mb-4">Tình trạng xét duyệt</h2>
                <div class="grid grid-cols-3 gap-4">
                    <template x-for="st in approvalStats" :key="st.status">
                        <div class="text-center p-4 rounded-2xl" :style="`background:${st.color}08;border:1px solid ${st.color}20`">
                            <p class="text-3xl font-black" :style="`color:${st.color}`" x-text="st.count"></p>
                            <p class="text-xs font-bold text-slate-600 mt-1" x-text="st.label"></p>
                            <p class="text-[10px] text-slate-400 mt-0.5" x-text="st.pct + '% tổng số'"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Preview --}}
    <div x-show="previewOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="previewOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
            <div class="h-1" :style="`background:${fileColor(selectedDoc?.type)}`"></div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="`background:${fileColor(selectedDoc?.type)}15`">
                        <i :data-lucide="fileIcon(selectedDoc?.type)" class="w-5 h-5" :style="`color:${fileColor(selectedDoc?.type)}`"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 line-clamp-1" x-text="selectedDoc?.name"></h3>
                        <p class="text-xs text-slate-400" x-text="selectedDoc?.author + (selectedDoc?.size ? ' · ' + selectedDoc?.size : '')"></p>
                    </div>
                </div>
                <button @click="previewOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="bg-slate-50 rounded-2xl h-56 flex items-center justify-center border-2 border-dashed border-slate-200 mb-5 relative">
                    <div class="text-center">
                        <i :data-lucide="fileIcon(selectedDoc?.type)" class="w-16 h-16 mx-auto mb-3" :style="`color:${fileColor(selectedDoc?.type)}`"></i>
                        <p class="text-sm font-bold text-slate-600" x-text="selectedDoc?.name"></p>
                        <p class="text-xs text-slate-400 mt-1" x-text="(selectedDoc?.type?.toUpperCase() || 'FILE') + (selectedDoc?.size ? ' · ' + selectedDoc?.size : '')"></p>
                    </div>
                    <a x-show="selectedDoc?.url" :href="selectedDoc?.url" target="_blank"
                        class="absolute top-3 right-3 flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 transition-all">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Mở file
                    </a>
                </div>

                <div class="space-y-4 mb-5">
                    <div>
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Mô tả</h4>
                        <p class="text-sm text-slate-700" x-text="selectedDoc?.description || 'Chưa có mô tả'"></p>
                    </div>
                    <div x-show="selectedDoc?.subject">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Môn học (Tag)</h4>
                        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600" x-text="selectedDoc?.subject"></span>
                    </div>
                    <div x-show="selectedDoc?.status==='rejected' && selectedDoc?.rejection_reason" class="p-3 bg-rose-50 rounded-xl border border-rose-100">
                        <p class="text-xs font-bold text-rose-600 mb-1">Lý do từ chối</p>
                        <p class="text-sm text-rose-700" x-text="selectedDoc?.rejection_reason"></p>
                    </div>
                    {{-- MỚI: Chi tiết kết quả kiểm duyệt tự động (scan_result) --}}
                        <div x-show="selectedDoc?.scan_status" class="mb-5">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kiểm duyệt tự động</h4>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="selectedDoc?.scan_status === 'flagged' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-600'"
                                    x-text="selectedDoc?.scan_status === 'flagged' ? '⚠ Cần chú ý' : '✓ Đã qua kiểm tra'">
                                </span>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-4 space-y-2.5 text-xs" x-show="selectedDoc?.scan_result">
                                {{-- Chữ ký file --}}
                                <div x-show="selectedDoc?.scan_result?.signature" class="flex items-start gap-2">
                                    <i :data-lucide="selectedDoc?.scan_result?.signature?.valid ? 'check-circle' : 'x-circle'"
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                        :class="selectedDoc?.scan_result?.signature?.valid ? 'text-emerald-500' : 'text-rose-500'"></i>
                                    <div>
                                        <p class="font-semibold text-slate-700">Chữ ký file (magic bytes)</p>
                                        <p class="text-slate-500" x-text="'MIME phát hiện: ' + (selectedDoc?.scan_result?.signature?.detected_mime || '—')"></p>
                                    </div>
                                </div>

                                {{-- Virus scan --}}
                                <div x-show="selectedDoc?.scan_result?.virus" class="flex items-start gap-2">
                                    <i :data-lucide="selectedDoc?.scan_result?.virus?.status === 'clean' ? 'shield-check' : selectedDoc?.scan_result?.virus?.status === 'skipped' ? 'shield-off' : 'shield-alert'"
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                        :class="selectedDoc?.scan_result?.virus?.status === 'clean' ? 'text-emerald-500' : selectedDoc?.scan_result?.virus?.status === 'skipped' ? 'text-slate-400' : 'text-amber-500'"></i>
                                    <div>
                                        <p class="font-semibold text-slate-700">Quét virus (ClamAV)</p>
                                        <p class="text-slate-500" x-text="{
                                            'clean':'Không phát hiện mã độc',
                                            'skipped':'Chưa cấu hình ClamAV, bỏ qua bước này',
                                            'error':'Không thể kết nối ClamAV khi quét'
                                        }[selectedDoc?.scan_result?.virus?.status] || selectedDoc?.scan_result?.virus?.status"></p>
                                    </div>
                                </div>

                                {{-- Trích xuất nội dung --}}
                                <div x-show="selectedDoc?.scan_result?.extraction" class="flex items-start gap-2">
                                    <i :data-lucide="selectedDoc?.scan_result?.extraction?.supported ? 'file-text' : 'file-x'"
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                        :class="selectedDoc?.scan_result?.extraction?.supported ? 'text-indigo-500' : 'text-slate-400'"></i>
                                    <div>
                                        <p class="font-semibold text-slate-700">Trích xuất nội dung</p>
                                        <p class="text-slate-500"
                                            x-text="selectedDoc?.scan_result?.extraction?.supported
                                                ? ('Đã đọc được ' + selectedDoc?.scan_result?.extraction?.length + ' ký tự')
                                                : 'Định dạng này chưa hỗ trợ trích xuất tự động'"></p>
                                    </div>
                                </div>

                                {{-- Kiểm tra nội dung --}}
                                <div x-show="selectedDoc?.scan_result?.content" class="flex items-start gap-2">
                                    <i :data-lucide="selectedDoc?.scan_result?.content?.suspicious ? 'alert-triangle' : 'check-circle'"
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                        :class="selectedDoc?.scan_result?.content?.suspicious ? 'text-amber-500' : 'text-emerald-500'"></i>
                                    <div>
                                        <p class="font-semibold text-slate-700">Kiểm tra nội dung</p>
                                        <p class="text-slate-500" x-text="selectedDoc?.scan_result?.content?.reason || 'Không có gì bất thường'"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                <div class="grid grid-cols-4 gap-3 mb-5">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.downloads ?? 0"></p>
                        <p class="text-[10px] text-slate-400">Lượt tải</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.rate > 0 ? selectedDoc.rate : '—'"></p>
                        <p class="text-[10px] text-slate-400">Đánh giá</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center col-span-2">
                        <p class="text-sm font-black text-slate-900" x-text="selectedDoc?.upload_date"></p>
                        <p class="text-[10px] text-slate-400">Ngày tải lên</p>
                    </div>
                </div>

                <div x-show="selectedDoc?.reviewed_at" class="flex items-center gap-2 text-xs text-slate-400 mb-5">
                    <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                    <span>Đã xét duyệt lúc: </span>
                    <span class="font-semibold text-slate-600" x-text="selectedDoc?.reviewed_at"></span>
                </div>
                {{-- Toggle ẩn/hiện — chỉ hiển thị cho tài liệu approved hoặc hidden --}}
                <div x-show="selectedDoc?.status==='approved' || selectedDoc?.status==='hidden'"
                    class="flex items-center justify-between px-4 py-3 border border-slate-200 bg-slate-50 rounded-xl mb-5">
                    <div class="flex items-center gap-2.5">
                        <i :data-lucide="selectedDoc?.status==='hidden' ? 'eye-off' : 'eye'" class="w-4 h-4 text-slate-500"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-700"
                                x-text="selectedDoc?.status==='hidden' ? 'Đang ẩn khỏi kho' : 'Đang hiển thị trong kho'"></p>
                            <p class="text-[11px] text-slate-400">Học viên sẽ không thấy tài liệu này khi bị ẩn</p>
                        </div>
                    </div>

                    <button type="button"
                        @click="toggleVisibility(selectedDoc)"
                        :disabled="selectedDoc && processingIds.includes(selectedDoc.id)"
                        role="switch"
                        :aria-checked="(selectedDoc?.status !== 'hidden').toString()"
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 ease-in-out
                            disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1"
                        :class="selectedDoc?.status !== 'hidden' ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="inline-block transform rounded-full bg-white shadow transition-transform duration-200 ease-in-out"
                            style="height:18px;width:18px"
                            :class="selectedDoc?.status !== 'hidden' ? 'translate-x-6' : 'translate-x-1'">
                        </span>
                    </button>
                </div>
                {{-- FIX: approve từ modal không đóng modal trước — để approveDoc tự đóng sau khi xong --}}
                <div x-show="selectedDoc?.status==='pending'" class="flex gap-3">
                    <button @click="approveFromModal()"
                        :disabled="selectedDoc && processingIds.includes(selectedDoc.id)"
                        class="flex-1 py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                        <svg x-show="selectedDoc && processingIds.includes(selectedDoc.id)" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <i x-show="!selectedDoc || !processingIds.includes(selectedDoc.id)" data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="selectedDoc && processingIds.includes(selectedDoc.id) ? 'Đang xử lý...' : 'Phê duyệt'"></span>
                    </button>
                    <button @click="rejectFromModal()"
                        :disabled="selectedDoc && processingIds.includes(selectedDoc.id)"
                        class="flex-1 py-2.5 border border-rose-200 text-rose-600 rounded-xl text-sm font-semibold hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                        <i data-lucide="x" class="w-4 h-4"></i> Từ chối
                    </button>
                </div>

                {{-- NÚT MỚI: AI xét duyệt ngay trong modal preview --}}
                <button x-show="selectedDoc?.status==='pending'" @click="aiReviewFromModal()"
                    :disabled="selectedDoc && processingIds.includes(selectedDoc.id)"
                    class="w-full mt-3 py-2.5 border border-violet-200 text-violet-600 bg-violet-50 rounded-xl text-sm font-semibold hover:bg-violet-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                    <i data-lucide="bot" class="w-4 h-4" :class="selectedDoc && processingIds.includes(selectedDoc.id) ? 'ai-pulse' : ''"></i>
                    <span x-text="selectedDoc && processingIds.includes(selectedDoc.id) ? 'AI đang phân tích...' : 'Tự động xét duyệt bằng AI'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL: Từ chối --}}
    <div x-show="rejectModalOpen" x-transition class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="rejectModalOpen=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-500"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Từ chối tài liệu</h2>
                        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1" x-text="rejectTarget?.name"></p>
                    </div>
                </div>
                <button @click="rejectModalOpen=false" class="p-2 rounded-xl hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Lý do từ chối</label>
                <textarea x-model="rejectionReason" rows="4"
                    placeholder="VD: Nội dung không phù hợp, thiếu thông tin tác giả, vi phạm bản quyền..."
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 resize-none"></textarea>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-500 mb-2">Lý do nhanh</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="r in quickReasons" :key="r">
                        <button @click="rejectionReason=r"
                            class="text-xs px-3 py-1.5 rounded-xl border transition-all text-slate-600"
                            :class="rejectionReason===r ? 'border-rose-300 bg-rose-50 text-rose-600' : 'border-slate-200 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600'"
                            x-text="r">
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="rejectModalOpen=false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Hủy</button>
                <button @click="confirmReject()"
                    :disabled="rejectTarget && processingIds.includes(rejectTarget.id)"
                    class="flex-1 py-2.5 bg-rose-500 text-white rounded-xl text-sm font-semibold hover:bg-rose-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    Xác nhận từ chối
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL: Xác nhận dùng chung (thay cho confirm() của trình duyệt) --}}
    <div x-show="confirmModalOpen" x-transition class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="resolveConfirm(false)"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm p-7 space-y-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                    :class="confirmModalDanger ? 'bg-rose-100' : 'bg-violet-100'">
                    <i :data-lucide="confirmModalDanger ? 'alert-triangle' : 'help-circle'"
                       class="w-5 h-5" :class="confirmModalDanger ? 'text-rose-500' : 'text-violet-500'"></i>
                </div>
                <div class="flex-1 pt-0.5">
                    <h2 class="text-base font-black text-slate-900" x-text="confirmModalTitle"></h2>
                    <p class="text-sm text-slate-500 mt-1" x-text="confirmModalMessage"></p>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="resolveConfirm(false)"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="resolveConfirm(true)"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                    :class="confirmModalDanger ? 'bg-rose-500 hover:bg-rose-600' : 'bg-slate-900 hover:bg-slate-700'"
                    x-text="confirmModalConfirmText">
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition style="display:none"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2 max-w-md text-center"
        :class="{'bg-emerald-600':toast.type==='success','bg-rose-600':toast.type==='error','bg-amber-500':toast.type==='warning','bg-violet-600':toast.type==='ai'}">
        <i :data-lucide="toast.type==='success'?'check-circle':toast.type==='error'?'x-circle':toast.type==='ai'?'bot':'alert-triangle'" class="w-4 h-4 shrink-0"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@push('scripts')
<script>
function docManager() {
    return {
        tab: 'pending',
        viewMode: 'grid',
        docSearch: '', docFilterType: '', docFilterStatus: '', docFilterSubject: '',
        previewOpen: false,
        selectedDoc: null,
        rejectModalOpen: false,
        rejectTarget: null,
        rejectionReason: '',
        toast: { show: false, msg: '', type: 'success' },
        loading: { pending: true, all: true, stats: false },

        // ── Modal xác nhận dùng chung (thay cho confirm() của trình duyệt) ──
        confirmModalOpen: false,
        confirmModalTitle: '',
        confirmModalMessage: '',
        confirmModalConfirmText: 'Xác nhận',
        confirmModalDanger: false,
        _confirmResolve: null,

        // ── FIX: dùng array reactive thay vì doc._loading ──────────────────
        processingIds: [],

        // ── AI xét duyệt hàng loạt ───────────────────────────────────────────
        bulkAiRunning: false,
        bulkAiTotal: 0,
        bulkAiDone: 0,

        pendingDocs: [], allDocs: [], kpis: [],
        typeStats: [], subjectStats: [], topDocs: [], approvalStats: [], subjects: [],

        tabs: [
            { key: 'pending', label: 'Chờ duyệt', icon: 'clock',      count: 0 },
            { key: 'all',     label: 'Tất cả',    icon: 'folder-open', count: 0 },
            { key: 'stats',   label: 'Thống kê',  icon: 'bar-chart-2', count: 0 },
        ],
        quickReasons: [
            'Nội dung không phù hợp', 'Vi phạm bản quyền',
            'Thiếu thông tin tác giả', 'Định dạng không được hỗ trợ',
            'Tài liệu trùng lặp', 'Chất lượng thấp',
        ],

        get filteredAllDocs() {
            const q = this.docSearch.toLowerCase();
            return this.allDocs.filter(d =>
                (!q || d.name?.toLowerCase().includes(q) || d.author?.toLowerCase().includes(q))
                && (!this.docFilterType    || d.type    === this.docFilterType)
                && (!this.docFilterStatus  || d.status  === this.docFilterStatus)
                && (!this.docFilterSubject || d.subject === this.docFilterSubject)
            );
        },

        async init() {
            await Promise.all([this.fetchPending(), this.fetchAll()]);
            this.$watch('tab', async (val) => {
                if (val === 'stats' && !this.typeStats.length) await this.fetchStats();
                this.$nextTick(() => lucide.createIcons());
            });
        },

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

        async toggleVisibility(doc) {
            if (!doc || this.processingIds.includes(doc.id)) return;
            this.processingIds.push(doc.id);
            try {
                const res = await this.api(`/admin/documents/${doc.id}/toggle-visibility`, 'PATCH');
                this._syncDoc(res.doc);
                this.showToast(res.message ?? 'Đã cập nhật trạng thái hiển thị');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== doc.id);
                this.$nextTick(() => lucide.createIcons());
            }
        },

        statusLabel(doc) {
            return { approved: 'Đã duyệt', pending: 'Chờ duyệt', rejected: 'Từ chối', hidden: 'Đã ẩn' }[doc.status] ?? doc.status;
        },
        statusBadgeClass(doc) {
            return {
                approved: 'bg-emerald-50 text-emerald-600',
                pending:  'bg-amber-50 text-amber-600',
                rejected: 'bg-rose-50 text-rose-600',
                hidden:   'bg-slate-100 text-slate-500',
            }[doc.status] ?? 'bg-slate-100 text-slate-500';
        },

        // ── Modal xác nhận dùng chung — trả về Promise<boolean> ─────────────
        askConfirm(title, message, opts = {}) {
            this.confirmModalTitle       = title;
            this.confirmModalMessage     = message;
            this.confirmModalConfirmText = opts.confirmText || 'Xác nhận';
            this.confirmModalDanger      = !!opts.danger;
            this.confirmModalOpen        = true;
            this.$nextTick(() => lucide.createIcons());
            return new Promise((resolve) => { this._confirmResolve = resolve; });
        },

        resolveConfirm(result) {
            this.confirmModalOpen = false;
            if (this._confirmResolve) {
                this._confirmResolve(result);
                this._confirmResolve = null;
            }
        },

        async fetchPending() {
            this.loading.pending = true;
            try {
                const res = await this.api('/admin/documents/pending');
                this.pendingDocs = (res.data ?? []).map(doc => ({ ...doc, aiReviewEnabled: false }));
                this.tabs[0].count = this.pendingDocs.length;
            } catch (e) { this.showToast(e.message, 'error'); }
            finally {
                this.loading.pending = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async fetchAll() {
            this.loading.all = true;
            try {
                const res = await this.api('/admin/documents/data');
                this.allDocs = (res.data ?? []).map(doc => ({ ...doc, aiReviewEnabled: false }));
                this.kpis     = res.kpis     ?? [];
                this.subjects = res.subjects ?? [];
            } catch (e) { this.showToast(e.message, 'error'); }
            finally {
                this.loading.all = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async fetchStats() {
            this.loading.stats = true;
            try {
                const res = await this.api('/admin/documents/stats');
                this.typeStats    = res.typeStats    ?? [];
                this.subjectStats = res.subjectStats ?? [];
                this.topDocs      = res.topDocs      ?? [];
                this.approvalStats = res.approvalStats ?? [];
            } catch (e) { this.showToast(e.message, 'error'); }
            finally {
                this.loading.stats = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        // ── FIX: processingIds thay vì doc._loading ────────────────────────
        async approveDoc(doc) {
            if (!doc || this.processingIds.includes(doc.id)) return;
            this.processingIds.push(doc.id);
            try {
                const res = await this.api(`/admin/documents/${doc.id}/approve`, 'PATCH');
                this._syncDoc(res.doc);
                this.pendingDocs   = this.pendingDocs.filter(d => d.id !== doc.id);
                this.tabs[0].count = this.pendingDocs.length;
                this.showToast(res.message ?? 'Đã phê duyệt tài liệu');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== doc.id);
                this.$nextTick(() => lucide.createIcons());
            }
        },

        // ── FIX: approve/reject từ modal không đóng modal trước khi fetch xong
        async approveFromModal() {
            if (!this.selectedDoc) return;
            const doc = this.selectedDoc;
            await this.approveDoc(doc);
            // Chỉ đóng sau khi xong (approveDoc đã remove khỏi processingIds)
            if (!this.processingIds.includes(doc.id)) this.previewOpen = false;
        },

        rejectFromModal() {
            if (!this.selectedDoc) return;
            const doc = this.selectedDoc;
            this.previewOpen = false;        // đóng preview trước
            this.$nextTick(() => {
                this.openRejectModal(doc);   // rồi mở reject modal
                lucide.createIcons();
            });
        },

        openRejectModal(doc) {
            this.rejectTarget    = doc;
            this.rejectionReason = '';
            this.rejectModalOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async confirmReject() {
            const doc = this.rejectTarget;
            if (!doc || this.processingIds.includes(doc.id)) return;
            this.processingIds.push(doc.id);
            try {
                const res = await this.api(`/admin/documents/${doc.id}/reject`, 'PATCH', {
                    rejection_reason: this.rejectionReason,
                });
                this._syncDoc(res.doc);
                this.pendingDocs   = this.pendingDocs.filter(d => d.id !== doc.id);
                this.tabs[0].count = this.pendingDocs.length;
                this.rejectModalOpen = false;
                this.showToast(res.message ?? 'Đã từ chối tài liệu', 'warning');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== doc.id);
            }
        },

        // ── MỚI: AI tự động xét duyệt 1 tài liệu ────────────────────────────
        async aiReviewDoc(doc) {
            if (!doc || this.processingIds.includes(doc.id)) return;
            this.processingIds.push(doc.id);
            try {
                const res = await this.api(`/admin/documents/${doc.id}/ai-review`, 'PATCH');
                this._syncDoc(res.doc);
                this.pendingDocs   = this.pendingDocs.filter(d => d.id !== doc.id);
                this.tabs[0].count = this.pendingDocs.length;

                const verb = res.ai_decision === 'approve' ? 'Đã phê duyệt' : 'Đã từ chối';
                const confidence = res.ai_confidence != null ? ` (độ tin cậy ${res.ai_confidence}%)` : '';
                const reason = res.ai_reason ? ` — Lý do: ${res.ai_reason}` : '';
                this.showToast(`${verb}${confidence}${reason}`, 'ai');

                return true;
            } catch (e) {
                this.showToast(e.message, 'error');
                return false;
            } finally {
                this.processingIds = this.processingIds.filter(id => id !== doc.id);
                this.$nextTick(() => lucide.createIcons());
            }
        },

        toggleAiReview(doc) {
            if (!doc) return;
            doc.aiReviewEnabled = !doc.aiReviewEnabled;
            this.$nextTick(() => lucide.createIcons());
        },

        async aiReviewFromModal() {
            if (!this.selectedDoc) return;
            const doc = this.selectedDoc;
            const ok = await this.aiReviewDoc(doc);
            if (ok) this.previewOpen = false;
        },

        // ── MỚI: AI xét duyệt hàng loạt toàn bộ danh sách chờ duyệt ─────────
        async aiReviewAllPending() {
            if (this.bulkAiRunning || this.pendingDocs.length === 0) return;

            const ok = await this.askConfirm(
                'Tự động xét duyệt bằng AI',
                `Để AI tự động xét duyệt ${this.pendingDocs.length} tài liệu đang chờ? Hành động sẽ được áp dụng ngay cho từng tài liệu, không cần xác nhận lại.`,
                { confirmText: 'Bắt đầu' }
            );
            if (!ok) return;

            this.bulkAiRunning = true;
            const queue = [...this.pendingDocs];
            this.bulkAiTotal = queue.length;
            this.bulkAiDone  = 0;

            for (const doc of queue) {
                await this.aiReviewDoc(doc);
                this.bulkAiDone++;
            }

            this.bulkAiRunning = false;
            this.showToast(`🤖 AI đã xét duyệt xong ${this.bulkAiDone} tài liệu`, 'ai');
        },

        async deleteDoc(doc) {
            const ok = await this.askConfirm(
                'Xóa tài liệu',
                `Xóa tài liệu "${doc.name}"? Hành động này không thể hoàn tác.`,
                { confirmText: 'Xóa', danger: true }
            );
            if (!ok) return;
            try {
                const res = await this.api(`/admin/documents/${doc.id}`, 'DELETE');
                this.allDocs     = this.allDocs.filter(d => d.id !== doc.id);
                this.pendingDocs = this.pendingDocs.filter(d => d.id !== doc.id);
                this.tabs[0].count = this.pendingDocs.length;
                if (this.selectedDoc?.id === doc.id) this.previewOpen = false;
                this.showToast(res.message ?? 'Đã xóa tài liệu', 'error');
                await this.fetchAll();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        openPreview(doc) {
            this.selectedDoc = doc;
            this.previewOpen = true;
            this.$nextTick(() => lucide.createIcons());

            // ✅ Ghi nhận lượt xem, không cần chờ kết quả, không chặn UI
            this.api(`/admin/documents/${doc.id}/view`, 'POST')
                .then(res => {
                    doc.views = res.views;
                    if (this.selectedDoc?.id === doc.id) this.selectedDoc.views = res.views;
                })
                .catch(() => {}); // im lặng bỏ qua nếu lỗi, không ảnh hưởng trải nghiệm xem
        },

        _syncDoc(updated) {
            if (!updated) return;
            const idx = this.allDocs.findIndex(d => d.id === updated.id);
            if (idx !== -1) this.allDocs.splice(idx, 1, updated);
            if (this.selectedDoc?.id === updated.id) this.selectedDoc = updated;
        },

        fileIcon(type)  { return { pdf:'file-text', docx:'file', pptx:'presentation', xlsx:'table-2' }[type] ?? 'file'; },
        fileColor(type) { return { pdf:'#ef4444', docx:'#3b82f6', pptx:'#f59e0b', xlsx:'#10b981' }[type] ?? '#64748b'; },

        showToast(msg, type = 'success') {
            this.toast = { show: true, msg, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => this.toast.show = false, 4000);
        },
    };
}
</script>
@endpush
@endsection