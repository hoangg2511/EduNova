@extends('layouts.app')
@section('title', 'Bài thi của tôi - EduNova')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    .q-card { animation: slideIn .2s ease; }
    @keyframes slideIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
    .exam-shield { -webkit-user-select:none; -moz-user-select:none; user-select:none; }
    .ring-track { stroke: #e2e8f0; }
    .ring-progress { stroke:#3b82f6; stroke-linecap:round; transition:stroke-dashoffset .5s ease, stroke .5s ease; transform:rotate(-90deg); transform-origin:center; }
    .ring-progress.warning { stroke:#f59e0b; }
    .ring-progress.danger  { stroke:#ef4444; }
    @keyframes correctPulse { 0%,100%{box-shadow:0 0 0 0 #10b98140;} 50%{box-shadow:0 0 0 8px #10b98100;} }
    .correct-flash { animation: correctPulse .6s ease; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px);} to{opacity:1;transform:translateY(0);} }
    .fade-up { animation: fadeUp .3s ease forwards; }
    .tab-active { border-bottom: 2px solid #0f172a; color:#0f172a; font-weight:700; }
    .tab-inactive { color:#94a3b8; }
    .tab-inactive:hover { color:#475569; }
    .chart-bar { transition: height .6s cubic-bezier(.4,0,.2,1); }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="examApp()" x-cloak>

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Bài thi của tôi</h1>
            <p class="text-slate-500 text-sm mt-1">Tạo, quản lý, chia sẻ và theo dõi bài thi trắc nghiệm</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <label class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl font-semibold text-sm text-slate-700 hover:bg-slate-50 transition-all cursor-pointer active:scale-95">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i> Import Excel
                <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="importExcel($event)">
            </label>
            <button @click="openCreateExam()"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
                <i data-lucide="plus" class="w-4 h-4"></i> Tạo bài thi
            </button>
        </div>
    </div>

    {{-- ── STATS ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900" x-text="exams.length"></p>
                <p class="text-xs text-slate-500">Bài thi</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900" x-text="exams.filter(e=>e.status==='published').length"></p>
                <p class="text-xs text-slate-500">Xuất bản</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-5 h-5 text-violet-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900" x-text="allAttempts.length"></p>
                <p class="text-xs text-slate-500">Lượt làm bài</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                <i data-lucide="trending-up" class="w-5 h-5 text-rose-500"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900"
                    x-text="allAttempts.length > 0 ? Math.round(allAttempts.reduce((s,a)=>s+a.score,0)/allAttempts.length) + '%' : 'N/A'"></p>
                <p class="text-xs text-slate-500">Điểm TB</p>
            </div>
        </div>
    </div>

    {{-- ── MAIN TABS ── --}}
    <div class="flex items-center gap-1 border-b border-slate-200">
        <button @click="mainTab='exams'" class="px-4 py-2.5 text-sm transition-all -mb-px" :class="mainTab==='exams' ? 'tab-active' : 'tab-inactive'">Danh sách bài thi</button>
        <button @click="mainTab='reports'" class="px-4 py-2.5 text-sm transition-all -mb-px" :class="mainTab==='reports' ? 'tab-active' : 'tab-inactive'">Báo cáo & Thống kê</button>
    </div>

    {{-- ══════════════ TAB: DANH SÁCH ══════════════ --}}
    <div x-show="mainTab === 'exams'">
        {{-- Search + Filter --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" x-model="searchQuery" placeholder="Tìm bài thi..."
                    class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>
            <select x-model="filterStatus"
                class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 min-w-[140px]">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">Bản nháp</option>
                <option value="published">Đã xuất bản</option>
            </select>
        </div>

        {{-- Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="exam in filteredExams" :key="exam.id">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="exam.status==='published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                    x-text="exam.status==='published' ? 'Xuất bản' : 'Nháp'"></span>
                                <span x-show="exam.shuffle" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">Random</span>
                                <span x-show="exam.security?.accessKey" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Key</span>
                            </div>
                            <h3 class="font-black text-slate-900 text-base truncate" x-text="exam.title"></h3>
                            <p class="text-xs text-slate-500 mt-0.5 truncate" x-text="exam.description || 'Không có mô tả'"></p>
                        </div>
                        <div class="relative ml-2" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-slate-100 transition-all">
                                <i data-lucide="more-vertical" class="w-4 h-4 text-slate-500"></i>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-transition
                                class="absolute right-0 top-8 bg-white border border-slate-200 rounded-xl shadow-lg z-10 min-w-[160px] py-1">
                                <button @click="editExam(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Chỉnh sửa
                                </button>
                                <button @click="openShareModal(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="share-2" class="w-3.5 h-3.5 text-blue-500"></i> Chia sẻ
                                </button>
                                <button @click="openReportModal(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-violet-500"></i> Báo cáo
                                </button>
                                <button @click="openExamEntry(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="play" class="w-3.5 h-3.5 text-emerald-600"></i> Làm bài
                                </button>
                                <button @click="duplicateExam(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> Nhân bản
                                </button>
                                <button @click="exportExam(exam); open=false" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Xuất Excel
                                </button>
                                <div class="border-t border-slate-100 my-1"></div>
                                <button @click="deleteExam(exam.id); open=false" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 my-3">
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-sm font-black text-slate-900" x-text="exam.questions.length"></p>
                            <p class="text-[10px] text-slate-500">Câu hỏi</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-sm font-black text-slate-900" x-text="exam.duration+'p'"></p>
                            <p class="text-[10px] text-slate-500">Thời gian</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-sm font-black text-slate-900"
                                x-text="getExamAttempts(exam.id).length"></p>
                            <p class="text-[10px] text-slate-500">Lượt thi</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span x-show="exam.security?.noTab" class="text-[10px] font-semibold px-2 py-0.5 bg-rose-50 text-rose-600 rounded-full">🔒 Anti-tab</span>
                        <span x-show="exam.security?.noCopy" class="text-[10px] font-semibold px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full">🚫 Anti-copy</span>
                        <span x-show="exam.security?.accessKey" class="text-[10px] font-semibold px-2 py-0.5 bg-rose-50 text-rose-700 rounded-full">🔑 Mã bảo mật</span>
                    </div>

                    <div class="flex gap-2">
                        <button @click="openShareModal(exam)"
                            class="flex-1 py-2 text-xs font-semibold text-blue-700 border border-blue-200 rounded-xl hover:bg-blue-50 transition-all flex items-center justify-center gap-1">
                            <i data-lucide="share-2" class="w-3 h-3"></i> Chia sẻ
                        </button>
                        <button @click="openExamEntry(exam)"
                            class="flex-1 py-2 text-xs font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-700 transition-all flex items-center justify-center gap-1">
                            <i data-lucide="play" class="w-3 h-3"></i> Làm bài
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="filteredExams.length === 0" class="col-span-full bg-white rounded-2xl border border-slate-200 p-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-question" class="w-8 h-8 text-slate-400"></i>
                </div>
                <p class="font-bold text-slate-700">Chưa có bài thi nào</p>
                <p class="text-sm text-slate-500 mt-1">Tạo bài thi mới hoặc import từ Excel</p>
            </div>
        </div>
    </div>

    {{-- ══════════════ TAB: BÁO CÁO ══════════════ --}}
    <div x-show="mainTab === 'reports'" class="space-y-5">
        {{-- Overview cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-bold text-slate-500 mb-1">Tổng lượt thi</p>
                <p class="text-3xl font-black text-slate-900" x-text="allAttempts.length"></p>
                <p class="text-xs text-slate-400 mt-1">tất cả bài thi</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-bold text-slate-500 mb-1">Tỷ lệ đậu</p>
                <p class="text-3xl font-black text-emerald-600"
                    x-text="allAttempts.length > 0 ? Math.round(allAttempts.filter(a=>a.passed).length/allAttempts.length*100)+'%' : 'N/A'"></p>
                <p class="text-xs text-slate-400 mt-1" x-text="`${allAttempts.filter(a=>a.passed).length} / ${allAttempts.length} lượt`"></p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-bold text-slate-500 mb-1">Điểm TB</p>
                <p class="text-3xl font-black text-blue-600"
                    x-text="allAttempts.length > 0 ? Math.round(allAttempts.reduce((s,a)=>s+a.score,0)/allAttempts.length)+'%' : 'N/A'"></p>
                <p class="text-xs text-slate-400 mt-1">toàn hệ thống</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-xs font-bold text-slate-500 mb-1">Điểm cao nhất</p>
                <p class="text-3xl font-black text-violet-600"
                    x-text="allAttempts.length > 0 ? Math.max(...allAttempts.map(a=>a.score))+'%' : 'N/A'"></p>
                <p class="text-xs text-slate-400 mt-1">kỷ lục cá nhân</p>
            </div>
        </div>

        {{-- Per-exam table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-black text-slate-900">Thống kê theo bài thi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-6 py-3 text-left">Bài thi</th>
                            <th class="px-4 py-3 text-center">Lượt thi</th>
                            <th class="px-4 py-3 text-center">Điểm TB</th>
                            <th class="px-4 py-3 text-center">Tỷ lệ đậu</th>
                            <th class="px-4 py-3 text-center">Điểm cao</th>
                            <th class="px-4 py-3 text-center">Điểm thấp</th>
                            <th class="px-4 py-3 text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="exam in exams" :key="exam.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-800 truncate max-w-[200px]" x-text="exam.title"></p>
                                    <p class="text-xs text-slate-400" x-text="`${exam.questions.length} câu · ${exam.duration} phút`"></p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="font-black text-slate-900" x-text="getExamAttempts(exam.id).length"></span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="font-bold text-blue-600"
                                        x-text="getExamAttempts(exam.id).length > 0 ? Math.round(getExamAttempts(exam.id).reduce((s,a)=>s+a.score,0)/getExamAttempts(exam.id).length)+'%' : '—'">
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="font-bold"
                                        :class="getPassRate(exam.id) >= 60 ? 'text-emerald-600' : 'text-rose-500'"
                                        x-text="getExamAttempts(exam.id).length > 0 ? getPassRate(exam.id)+'%' : '—'">
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-emerald-600 font-bold"
                                        x-text="getExamAttempts(exam.id).length > 0 ? Math.max(...getExamAttempts(exam.id).map(a=>a.score))+'%' : '—'">
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-rose-500 font-bold"
                                        x-text="getExamAttempts(exam.id).length > 0 ? Math.min(...getExamAttempts(exam.id).map(a=>a.score))+'%' : '—'">
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button @click="openReportModal(exam)"
                                        class="px-3 py-1.5 text-xs font-semibold text-violet-700 border border-violet-200 rounded-lg hover:bg-violet-50 transition-all">
                                        Xem
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="exams.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">Chưa có dữ liệu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent attempts --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-slate-900">Lịch sử làm bài gần đây</h3>
                <button x-show="allAttempts.length > 0" @click="if(confirm('Xóa toàn bộ lịch sử?')) { allAttempts=[]; saveAttempts(); }"
                    class="text-xs text-red-500 hover:text-red-700 font-semibold">Xóa tất cả</button>
            </div>
            <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                <template x-for="(att, i) in [...allAttempts].reverse().slice(0,20)" :key="i">
                    <div class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50 transition-colors">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-black text-sm"
                            :class="att.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                            x-text="att.score+'%'">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate" x-text="att.examTitle"></p>
                            <p class="text-xs text-slate-400" x-text="`${att.name} · ${att.correct}/${att.total} câu đúng · ${att.date}`"></p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
                            :class="att.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                            x-text="att.passed ? 'Đậu' : 'Rớt'">
                        </span>
                    </div>
                </template>
                <div x-show="allAttempts.length === 0" class="px-6 py-8 text-center text-slate-400 text-sm">
                    Chưa có lịch sử làm bài
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         MODAL: TẠO / CHỈNH SỬA BÀI THI
    ══════════════════════════════════════ --}}
    <div x-show="showEditor" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="confirmClose()"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100 shrink-0">
                <div>
                    <h2 class="text-xl font-black text-slate-900" x-text="editingExam ? 'Chỉnh sửa bài thi' : 'Tạo bài thi mới'"></h2>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="`${examForm.questions.length} câu hỏi`"></p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                        <button @click="editorTab='info'" :class="editorTab==='info' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg text-xs transition-all">Thông tin</button>
                        <button @click="editorTab='questions'" :class="editorTab==='questions' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg text-xs transition-all">
                            Câu hỏi <span class="ml-1 bg-slate-200 text-slate-700 rounded-full px-1.5 text-[10px] font-bold" x-text="examForm.questions.length"></span>
                        </button>
                        <button @click="editorTab='security'" :class="editorTab==='security' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg text-xs transition-all">Bảo mật</button>
                    </div>
                    <button @click="confirmClose()" class="p-2 rounded-xl hover:bg-slate-100 transition-all ml-2">
                        <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-8">

                {{-- TAB: THÔNG TIN --}}
                <div x-show="editorTab === 'info'" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tên bài thi <span class="text-red-500">*</span></label>
                            <input type="text" x-model="examForm.title" placeholder="VD: Kiểm tra Toán cao cấp A1"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả</label>
                            <textarea x-model="examForm.description" rows="2" placeholder="Mô tả ngắn..."
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Thời gian (phút)</label>
                            <input type="number" x-model.number="examForm.duration" min="1" max="300"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Điểm đậu (%)</label>
                            <input type="number" x-model.number="examForm.passMark" min="0" max="100"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Trạng thái</label>
                            <select x-model="examForm.status" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                                <option value="draft">Bản nháp</option>
                                <option value="published">Xuất bản</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Số lần làm lại</label>
                            <input type="number" x-model.number="examForm.maxAttempts" min="1" max="99"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-5 space-y-3">
                        <p class="text-xs font-black text-slate-700 uppercase tracking-wide">Tùy chọn</p>
                        <template x-for="(opt, key) in [{model:'shuffle',label:'Xáo trộn câu hỏi',desc:'Thứ tự ngẫu nhiên mỗi lần'},{model:'shuffleOptions',label:'Xáo trộn đáp án',desc:'A/B/C/D ngẫu nhiên'},{model:'showResult',label:'Hiển thị kết quả ngay',desc:'Cho xem đáp án sau nộp bài'},{model:'requireName',label:'Yêu cầu nhập tên',desc:'Người làm bài phải nhập tên trước khi thi'}]" :key="key">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" x-model="examForm[opt.model]" class="sr-only peer">
                                    <div class="w-10 h-5 bg-slate-200 rounded-full peer-checked:bg-slate-900 transition-colors"></div>
                                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800" x-text="opt.label"></p>
                                    <p class="text-xs text-slate-500" x-text="opt.desc"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- TAB: CÂU HỎI --}}
                <div x-show="editorTab === 'questions'" class="space-y-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <button @click="addQuestion()" class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i> Thêm câu hỏi
                        </button>
                        <label class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all cursor-pointer">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i> Import Excel
                            <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="importQuestionsFromExcel($event)">
                        </label>
                        <a href="#" @click.prevent="downloadTemplate()" class="flex items-center gap-2 px-4 py-2.5 border border-dashed border-slate-300 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i> Tải template
                        </a>
                        <span class="ml-auto text-xs text-slate-400" x-text="`${examForm.questions.length} câu`"></span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(q, qi) in examForm.questions" :key="q._id">
                            <div class="q-card bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 border-b border-slate-100">
                                    <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 text-xs font-black flex items-center justify-center shrink-0" x-text="qi+1"></span>
                                    <input type="text" x-model="q.text" :placeholder="`Nhập câu hỏi số ${qi+1}...`"
                                        class="flex-1 bg-transparent text-sm font-semibold text-slate-800 focus:outline-none placeholder:text-slate-400">
                                    <div class="flex items-center gap-1 shrink-0">
                                        <select x-model="q.type" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white focus:outline-none">
                                            <option value="single">1 đáp án</option>
                                            <option value="multiple">Nhiều đáp án</option>
                                            <option value="truefalse">Đúng/Sai</option>
                                        </select>
                                        <input type="number" x-model.number="q.points" min="1" class="w-14 text-xs border border-slate-200 rounded-lg px-2 py-1 text-center focus:outline-none" placeholder="Điểm">
                                        <button @click="moveQuestion(qi,-1)" :disabled="qi===0" class="p-1.5 rounded-lg hover:bg-slate-200 disabled:opacity-30 transition-all">
                                            <i data-lucide="chevron-up" class="w-3.5 h-3.5 text-slate-500"></i>
                                        </button>
                                        <button @click="moveQuestion(qi,1)" :disabled="qi===examForm.questions.length-1" class="p-1.5 rounded-lg hover:bg-slate-200 disabled:opacity-30 transition-all">
                                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-500"></i>
                                        </button>
                                        <button @click="removeQuestion(qi)" class="p-1.5 rounded-lg hover:bg-red-100 transition-all">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-500"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-5 space-y-2">
                                    <template x-if="q.type==='truefalse'">
                                        <div class="flex gap-3">
                                            <template x-for="(opt,oi) in [{label:'✅ Đúng',val:'true'},{label:'❌ Sai',val:'false'}]" :key="oi">
                                                <button @click="q.correctAnswers=[opt.val]"
                                                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all"
                                                    :class="q.correctAnswers.includes(opt.val) ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                                    x-text="opt.label">
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="q.type!=='truefalse'">
                                        <div class="space-y-2">
                                            <template x-for="(opt,oi) in q.options" :key="oi">
                                                <div class="flex items-center gap-3">
                                                    <button @click="toggleCorrect(q,oi)"
                                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-all"
                                                        :class="q.correctAnswers.includes(oi) ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 hover:border-emerald-400'">
                                                        <i data-lucide="check" class="w-3 h-3" x-show="q.correctAnswers.includes(oi)"></i>
                                                    </button>
                                                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 text-xs font-black flex items-center justify-center shrink-0"
                                                        x-text="['A','B','C','D','E','F'][oi]"></span>
                                                    <input type="text" x-model="q.options[oi]" :placeholder="`Đáp án ${['A','B','C','D','E','F'][oi]}...`"
                                                        class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                                                    <button x-show="q.options.length>2" @click="removeOption(q,oi)" class="p-1.5 rounded-lg hover:bg-red-50 transition-all shrink-0">
                                                        <i data-lucide="x" class="w-3.5 h-3.5 text-red-400"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <button x-show="q.options.length<6" @click="addOption(q)" class="ml-9 text-xs font-semibold text-slate-500 hover:text-slate-900 flex items-center gap-1 transition-all">
                                                <i data-lucide="plus" class="w-3 h-3"></i> Thêm đáp án
                                            </button>
                                        </div>
                                    </template>
                                    <div class="mt-3 pt-3 border-t border-slate-100">
                                        <input type="text" x-model="q.explanation" placeholder="Giải thích đáp án (tùy chọn)..."
                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="examForm.questions.length===0"
                            class="border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center cursor-pointer hover:border-slate-400 transition-all" @click="addQuestion()">
                            <i data-lucide="plus-circle" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
                            <p class="font-semibold text-slate-500">Click để thêm câu hỏi đầu tiên</p>
                        </div>
                    </div>
                </div>

                {{-- TAB: BẢO MẬT --}}
                <div x-show="editorTab === 'security'" class="space-y-4">
                    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex gap-3">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                        <p class="text-sm text-rose-700">Kết hợp nhiều cơ chế bảo mật để giảm thiểu gian lận.</p>
                    </div>

                    {{-- Access Key --}}
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center">
                                    <i data-lucide="key" class="w-5 h-5 text-rose-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Mã truy cập (Access Key)</p>
                                    <p class="text-xs text-slate-500">Người dùng phải nhập mã mới được làm bài</p>
                                </div>
                            </div>
                            <label class="relative cursor-pointer">
                                <input type="checkbox" x-model="examForm.security.useAccessKey" class="sr-only peer">
                                <div class="w-10 h-5 bg-slate-200 rounded-full peer-checked:bg-rose-500 transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </label>
                        </div>
                        <div x-show="examForm.security.useAccessKey" class="space-y-3">
                            <div class="flex gap-2">
                                <input type="text" x-model="examForm.security.accessKey" placeholder="Nhập mã bảo mật..."
                                    class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rose-400">
                                <button @click="examForm.security.accessKey = generateKey()"
                                    class="px-4 py-2.5 bg-rose-100 text-rose-700 rounded-xl text-sm font-semibold hover:bg-rose-200 transition-all whitespace-nowrap">
                                    🎲 Tạo ngẫu nhiên
                                </button>
                            </div>
                            <div x-show="examForm.security.accessKey" class="flex items-center gap-2 bg-rose-50 rounded-xl px-4 py-2.5">
                                <i data-lucide="key" class="w-4 h-4 text-rose-500 shrink-0"></i>
                                <p class="text-sm font-mono font-bold text-rose-700" x-text="examForm.security.accessKey"></p>
                                <button @click="copyText(examForm.security.accessKey)" class="ml-auto text-xs text-rose-500 hover:text-rose-700 font-semibold">Copy</button>
                            </div>
                        </div>
                    </div>

                    {{-- Other security options --}}
                    <template x-for="sec in securityOptions" :key="sec.key">
                        <div class="bg-white border border-slate-200 rounded-2xl p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="sec.bg">
                                        <i :data-lucide="sec.icon" class="w-5 h-5" :class="sec.iconColor"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800" x-text="sec.label"></p>
                                        <p class="text-xs text-slate-500" x-text="sec.desc"></p>
                                    </div>
                                </div>
                                <label class="relative cursor-pointer">
                                    <input type="checkbox" x-model="examForm.security[sec.key]" class="sr-only peer">
                                    <div class="w-10 h-5 bg-slate-200 rounded-full transition-colors" :class="`peer-checked:${sec.color}`"></div>
                                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                                </label>
                            </div>
                            <div x-show="sec.key==='noTab' && examForm.security.noTab" class="mt-3 flex items-center gap-3">
                                <label class="text-xs text-slate-600 shrink-0">Số lần cảnh báo tối đa:</label>
                                <input type="number" x-model.number="examForm.security.maxTabWarnings" min="1" max="10"
                                    class="w-20 px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:outline-none">
                                <span class="text-xs text-slate-400">lần → tự động nộp bài</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-between px-8 py-5 border-t border-slate-100 shrink-0 bg-white">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    <span x-text="examForm.questions.length > 0 ? `${examForm.questions.length} câu · ${examForm.questions.reduce((s,q)=>s+(q.points||1),0)} điểm` : 'Chưa có câu hỏi'"></span>
                </div>
                <div class="flex gap-3">
                    <button @click="confirmClose()" class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Hủy</button>
                    <button @click="saveExam()" :disabled="!examForm.title"
                        class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all"
                        x-text="editingExam ? 'Cập nhật' : 'Lưu bài thi'">
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         MODAL: CHIA SẺ
    ══════════════════════════════════════ --}}
    <div x-show="showShare" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showShare=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">Chia sẻ bài thi</h2>
                <button @click="showShare=false" class="p-2 rounded-xl hover:bg-slate-100 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div x-show="shareExam" class="space-y-4">
                {{-- Exam info --}}
                <div class="bg-slate-50 rounded-2xl p-4">
                    <p class="font-black text-slate-900 text-sm" x-text="shareExam?.title"></p>
                    <p class="text-xs text-slate-500 mt-1" x-text="`${shareExam?.questions?.length} câu · ${shareExam?.duration} phút · Đậu ${shareExam?.passMark}%`"></p>
                </div>

                {{-- Share URL --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Link chia sẻ</label>
                    <div class="flex gap-2">
                        <input type="text" :value="getShareUrl(shareExam)" readonly
                            class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 font-mono text-slate-600">
                        <button @click="copyShareUrl()" class="px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                            <span x-text="urlCopied ? '✓ Đã copy' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                {{-- Access key info --}}
                <div x-show="shareExam?.security?.useAccessKey && shareExam?.security?.accessKey"
                    class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-center gap-3">
                    <i data-lucide="key" class="w-5 h-5 text-rose-500 shrink-0"></i>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-rose-800">Mã truy cập bài thi</p>
                        <p class="text-xs text-rose-600 mt-0.5">Gửi mã này kèm link cho người dùng</p>
                        <p class="text-lg font-black font-mono text-rose-700 mt-1" x-text="shareExam?.security?.accessKey"></p>
                    </div>
                    <button @click="copyText(shareExam?.security?.accessKey)" class="text-xs text-rose-600 font-semibold hover:text-rose-800">Copy</button>
                </div>

                {{-- QR placeholder --}}
                <div class="border border-slate-200 rounded-2xl p-5 text-center">
                    <div class="w-24 h-24 bg-slate-100 rounded-xl mx-auto flex items-center justify-center mb-2">
                        <i data-lucide="qr-code" class="w-12 h-12 text-slate-400"></i>
                    </div>
                    <p class="text-xs text-slate-500">QR Code (tích hợp thư viện qrcode.js để kích hoạt)</p>
                </div>

                {{-- Share buttons --}}
                <div class="grid grid-cols-3 gap-2">
                    <button @click="shareVia('copy')"
                        class="py-3 rounded-xl text-xs font-semibold border border-slate-200 hover:bg-slate-50 transition-all flex flex-col items-center gap-1">
                        <i data-lucide="link" class="w-4 h-4 text-slate-600"></i> Copy link
                    </button>
                    <button @click="shareVia('email')"
                        class="py-3 rounded-xl text-xs font-semibold border border-slate-200 hover:bg-slate-50 transition-all flex flex-col items-center gap-1">
                        <i data-lucide="mail" class="w-4 h-4 text-blue-500"></i> Email
                    </button>
                    <button @click="shareVia('whatsapp')"
                        class="py-3 rounded-xl text-xs font-semibold border border-slate-200 hover:bg-slate-50 transition-all flex flex-col items-center gap-1">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-500"></i> WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         MODAL: BÁO CÁO CHI TIẾT
    ══════════════════════════════════════ --}}
    <div x-show="showReport" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showReport=false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden">
            <div class="sticky top-0 bg-white border-b border-slate-100 px-8 py-5 flex items-center justify-between z-10 rounded-t-3xl">
                <div>
                    <h2 class="font-black text-slate-900 text-lg" x-text="reportExam?.title"></h2>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="`${getExamAttempts(reportExam?.id).length} lượt làm bài`"></p>
                </div>
                <button @click="showReport=false" class="p-2 rounded-xl hover:bg-slate-100 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8 space-y-6">
                {{-- Summary stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" x-data>
                    <template x-for="stat in getReportStats(reportExam?.id)" :key="stat.label">
                        <div class="bg-slate-50 rounded-2xl p-4 text-center">
                            <p class="text-2xl font-black" :class="stat.color" x-text="stat.value"></p>
                            <p class="text-xs text-slate-500 mt-1" x-text="stat.label"></p>
                        </div>
                    </template>
                </div>

                {{-- Score distribution chart --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <p class="text-sm font-black text-slate-900 mb-4">Phân bố điểm số</p>
                    <div class="flex items-end gap-2 h-32" x-data>
                        <template x-for="(bucket, bi) in getScoreDistribution(reportExam?.id)" :key="bi">
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[9px] text-slate-500 font-bold" x-text="bucket.count > 0 ? bucket.count : ''"></span>
                                <div class="w-full rounded-t-lg transition-all duration-700 chart-bar"
                                    :style="`height:${bucket.height}px`"
                                    :class="bucket.label === '90-100' ? 'bg-emerald-500' : bucket.label.startsWith('0') || bucket.label.startsWith('1') || bucket.label.startsWith('2') || bucket.label.startsWith('3') || bucket.label.startsWith('4') ? 'bg-rose-400' : 'bg-blue-400'">
                                </div>
                                <span class="text-[8px] text-slate-400 font-semibold" x-text="bucket.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Attempts table --}}
                <div>
                    <p class="text-sm font-black text-slate-900 mb-3">Chi tiết lượt làm bài</p>
                    <div class="border border-slate-200 rounded-2xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-xs font-bold text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Người dùng</th>
                                    <th class="px-4 py-3 text-center">Điểm</th>
                                    <th class="px-4 py-3 text-center">Đúng/Tổng</th>
                                    <th class="px-4 py-3 text-center">Kết quả</th>
                                    <th class="px-4 py-3 text-center">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                <template x-for="(att, i) in getExamAttempts(reportExam?.id)" :key="i">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-slate-800" x-text="att.name || 'Ẩn danh'"></p>
                                            <p class="text-xs text-slate-400" x-text="att.date"></p>
                                        </td>
                                        <td class="px-4 py-3 text-center font-black" :class="att.passed ? 'text-emerald-600' : 'text-rose-500'" x-text="att.score+'%'"></td>
                                        <td class="px-4 py-3 text-center text-slate-600" x-text="`${att.correct}/${att.total}`"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                :class="att.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                                                x-text="att.passed ? 'Đậu' : 'Rớt'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-xs text-slate-400" x-text="att.timeTaken || '—'"></td>
                                    </tr>
                                </template>
                                <tr x-show="getExamAttempts(reportExam?.id).length===0">
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400 text-sm">Chưa có lượt làm bài nào</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         MODAL: NHẬP THÔNG TIN TRƯỚC KHI THI
    ══════════════════════════════════════ --}}
    <div x-show="showEntry" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6 fade-up">
            {{-- Exam header --}}
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-900 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-text" class="w-8 h-8 text-white"></i>
                </div>
                <h2 class="text-xl font-black text-slate-900" x-text="entryExam?.title"></h2>
                <p class="text-sm text-slate-500 mt-1" x-text="entryExam?.description"></p>
            </div>

            {{-- Exam info badges --}}
            <div class="flex justify-center gap-3 flex-wrap">
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5"></i>
                    <span x-text="`${entryExam?.questions?.length} câu hỏi`"></span>
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span x-text="`${entryExam?.duration} phút`"></span>
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="target" class="w-3.5 h-3.5"></i>
                    <span x-text="`Đậu ${entryExam?.passMark}%`"></span>
                </span>
            </div>

            <div class="space-y-4">
                {{-- Tên --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Họ và tên <span x-show="entryExam?.requireName" class="text-red-500">*</span>
                        <span x-show="!entryExam?.requireName" class="text-slate-400 font-normal">(tùy chọn)</span>
                    </label>
                    <input type="text" x-model="entryForm.name"
                        placeholder="Nhập họ và tên của bạn..."
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                {{-- Access key --}}
                <div x-show="entryExam?.security?.useAccessKey">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mã truy cập <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="key" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input :type="entryForm.showKey ? 'text' : 'password'" x-model="entryForm.accessKey"
                            placeholder="Nhập mã bảo mật..."
                            class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-slate-900"
                            :class="entryForm.keyError ? 'border-red-400 ring-2 ring-red-200' : ''">
                        <button @click="entryForm.showKey = !entryForm.showKey" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <i :data-lucide="entryForm.showKey ? 'eye-off' : 'eye'" class="w-4 h-4 text-slate-400"></i>
                        </button>
                    </div>
                    <p x-show="entryForm.keyError" class="text-xs text-red-500 mt-1 font-semibold">❌ Mã không đúng, vui lòng thử lại</p>
                </div>

                {{-- Rules --}}
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-800 space-y-1.5">
                    <p class="font-bold">📋 Lưu ý trước khi bắt đầu:</p>
                    <p x-show="entryExam?.security?.noTab">• Không chuyển tab/cửa sổ trong khi thi</p>
                    <p x-show="entryExam?.security?.noCopy">• Không copy/paste nội dung bài thi</p>
                    <p x-show="entryExam?.security?.forceFullscreen">• Bài thi sẽ mở toàn màn hình</p>
                    <p x-show="entryExam?.shuffle">• Câu hỏi được xáo trộn ngẫu nhiên</p>
                    <p>• Thời gian làm bài: <strong x-text="`${entryExam?.duration} phút`"></strong></p>
                </div>
            </div>

            <div class="flex gap-3">
                <button @click="showEntry=false" class="flex-1 py-3 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="startExamFromEntry()"
                    :disabled="(entryExam?.requireName && !entryForm.name.trim()) || (entryExam?.security?.useAccessKey && !entryForm.accessKey.trim())"
                    class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="play" class="w-4 h-4"></i> Bắt đầu thi
                </button>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         MODAL: LÀM BÀI THI
    ══════════════════════════════════════ --}}
    <div x-show="showTaker" x-transition class="fixed inset-0 z-[60] flex items-center justify-center p-4 exam-shield"
        style="display:none;"
        @contextmenu.prevent="securityViolation('rightclick')"
        @copy.prevent="securityViolation('copy')"
        @paste.prevent="securityViolation('paste')">
        <div class="absolute inset-0 bg-slate-950/95 backdrop-blur-md"></div>
        <div class="relative w-full max-w-3xl max-h-[95vh] flex flex-col">

            {{-- Header --}}
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl mb-4 px-6 py-4 flex items-center justify-between text-white">
                <div>
                    <p class="font-black text-lg" x-text="takerExam?.title"></p>
                    <p class="text-white/60 text-xs mt-0.5" x-text="`${takerState.name || 'Ẩn danh'} · Câu ${takerState.current+1}/${takerQuestions.length}`"></p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative w-14 h-14">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
                            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4" class="ring-track"/>
                            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4"
                                class="ring-progress"
                                :class="takerState.timeLeft < 60 ? 'danger' : takerState.timeLeft < 300 ? 'warning' : ''"
                                :stroke-dasharray="`${2*Math.PI*24}`"
                                :stroke-dashoffset="`${2*Math.PI*24*(1-takerState.timeLeft/(takerExam?.duration*60))}`"
                                style="transform-origin:28px 28px">
                            </circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-[10px] font-black text-white" x-text="formatTime(takerState.timeLeft)"></span>
                        </div>
                    </div>
                    <button @click="submitExam()" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold transition-all">
                        Nộp bài
                    </button>
                     <button @click="showTaker=false" class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Đóng</button>
                </div>
            </div>

            {{-- Warning bar --}}
            <div x-show="takerState.warnings > 0"
                class="bg-amber-500/20 border border-amber-500/40 rounded-xl px-4 py-2.5 mb-3 flex items-center gap-2 text-amber-300 text-sm font-semibold">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                <span x-text="`⚠️ Cảnh báo ${takerState.warnings}/${takerExam?.security?.maxTabWarnings||3}: Phát hiện chuyển tab!`"></span>
            </div>

            {{-- Question --}}
            <div class="bg-white rounded-2xl flex-1 overflow-y-auto p-8 space-y-6" x-show="!takerState.finished">
                <template x-if="takerQuestions[takerState.current]">
                    <div>
                        <div class="flex gap-3 mb-6">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white text-sm font-black flex items-center justify-center shrink-0"
                                x-text="takerState.current+1"></span>
                            <p class="text-base font-semibold text-slate-900 leading-relaxed"
                                x-text="takerQuestions[takerState.current].text"></p>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(opt,oi) in getDisplayOptions(takerQuestions[takerState.current])" :key="oi">
                                <button @click="selectAnswer(takerState.current, opt.originalIndex)"
                                    class="w-full text-left px-5 py-4 rounded-2xl border-2 transition-all font-semibold text-sm"
                                    :class="getOptionClass(takerState.current, opt.originalIndex)">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-full border-2 flex items-center justify-center shrink-0 text-xs font-black transition-all"
                                            :class="isSelected(takerState.current,opt.originalIndex) ? 'bg-slate-900 border-slate-900 text-white' : 'border-slate-300 text-slate-400'"
                                            x-text="['A','B','C','D','E','F'][oi]">
                                        </span>
                                        <span x-text="opt.text"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                        <div x-show="takerState.showExplanation && takerQuestions[takerState.current].explanation"
                            class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <p class="text-xs font-bold text-blue-700 mb-1">💡 Giải thích</p>
                            <p class="text-sm text-blue-800" x-text="takerQuestions[takerState.current].explanation"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Result --}}
            <div x-show="takerState.finished" class="bg-white rounded-2xl flex-1 p-8 flex flex-col items-center justify-center text-center space-y-4">
                <div class="w-24 h-24 rounded-full flex items-center justify-center mx-auto"
                    :class="takerState.score>=(takerExam?.passMark||60) ? 'bg-emerald-100' : 'bg-rose-100'">
                    <span class="text-3xl font-black"
                        :class="takerState.score>=(takerExam?.passMark||60) ? 'text-emerald-600' : 'text-rose-600'"
                        x-text="takerState.score+'%'"></span>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900"
                        x-text="takerState.score>=(takerExam?.passMark||60) ? '🎉 Chúc mừng!' : '😔 Chưa đạt'"></p>
                    <p class="text-slate-500 text-sm mt-1"
                        x-text="`${takerState.name ? takerState.name+' · ' : ''}Đúng ${takerState.correct}/${takerQuestions.length} câu · Điểm đậu ${takerExam?.passMark}%`"></p>
                </div>
                <div class="flex gap-3 mt-4">
                    <button @click="showTaker=false" class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">Đóng</button>
                    <button x-show="takerExam?.showResult" @click="takerState.showExplanation=true; takerState.finished=false"
                        class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                        Xem lại đáp án
                    </button>
                </div>
            </div>

            {{-- Navigation --}}
            <div x-show="!takerState.finished" class="flex items-center justify-between mt-4">
                <div class="flex gap-1 flex-wrap max-w-xs">
                    <template x-for="(q,qi) in takerQuestions" :key="qi">
                        <button @click="takerState.current=qi"
                            class="w-6 h-6 rounded-full text-[10px] font-bold transition-all"
                            :class="{
                                'bg-slate-900 text-white': qi===takerState.current,
                                'bg-emerald-500 text-white': takerState.answers[qi]!==undefined && qi!==takerState.current,
                                'bg-white/20 text-white/60': takerState.answers[qi]===undefined && qi!==takerState.current,
                            }"
                            x-text="qi+1">
                        </button>
                    </template>
                </div>
                <div class="flex gap-2">
                    <button @click="takerState.current=Math.max(0,takerState.current-1)" :disabled="takerState.current===0"
                        class="px-4 py-2 bg-white/10 text-white rounded-xl text-sm font-semibold disabled:opacity-40 hover:bg-white/20 transition-all">← Trước</button>
                    <button @click="takerState.current=Math.min(takerQuestions.length-1,takerState.current+1)" :disabled="takerState.current===takerQuestions.length-1"
                        class="px-4 py-2 bg-white text-slate-900 rounded-xl text-sm font-semibold disabled:opacity-40 hover:bg-slate-100 transition-all">Tiếp →</button>
                </div>
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

@push('scripts')
<script>
// ── Anti-tab visibility listener ──
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) return;
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el._x_dataStack) el._x_dataStack.forEach(d => {
            if (d.showTaker !== undefined && d.showTaker) d.securityViolation('tab');
        });
    });
});

function examApp() {
    return {
        // ── Core state ──
        exams: [],
        allAttempts: [],
        mainTab: 'exams',
        searchQuery: '',
        filterStatus: '',
        toast: { show: false, message: '', type: 'success' },

        // ── Editor state ──
        showEditor: false,
        editingExam: null,
        editorTab: 'info',
        examForm: null,

        // ── Share state ──
        showShare: false,
        shareExam: null,
        urlCopied: false,

        // ── Report state ──
        showReport: false,
        reportExam: null,

        // ── Entry (pre-exam) state ──
        showEntry: false,
        entryExam: null,
        entryForm: { name: '', accessKey: '', showKey: false, keyError: false },

        // ── Taker state ──
        showTaker: false,
        takerExam: null,
        takerQuestions: [],
        takerState: { current:0, answers:{}, timeLeft:0, warnings:0, finished:false, score:0, correct:0, showExplanation:false, name:'', startTime:null },
        takerTimer: null,

        securityOptions: [
            { key:'noTab',          label:'Phát hiện chuyển tab',    desc:'Cảnh báo khi rời trang thi',       bg:'bg-rose-100',   iconColor:'text-rose-600',   icon:'monitor-x',       color:'bg-rose-500' },
            { key:'noCopy',         label:'Chặn copy/paste',         desc:'Vô hiệu hóa Ctrl+C, Ctrl+V',      bg:'bg-orange-100', iconColor:'text-orange-600', icon:'copy-x',          color:'bg-orange-500' },
            { key:'noRightClick',   label:'Chặn chuột phải',         desc:'Vô hiệu hóa context menu',        bg:'bg-yellow-100', iconColor:'text-yellow-600', icon:'mouse-pointer-x', color:'bg-yellow-500' },
            { key:'forceFullscreen',label:'Bắt buộc toàn màn hình',  desc:'Yêu cầu fullscreen, cảnh báo nếu thoát', bg:'bg-blue-100',  iconColor:'text-blue-600',  icon:'maximize',        color:'bg-blue-500' },
        ],

        // ── Computed ──
        get filteredExams() {
            return this.exams.filter(e => {
                const q = !this.searchQuery || e.title.toLowerCase().includes(this.searchQuery.toLowerCase());
                const s = !this.filterStatus || e.status === this.filterStatus;
                return q && s;
            });
        },

        // ── Init ──
        init() {
            this.exams = JSON.parse(localStorage.getItem('edunova_exams') || '[]');
            this.allAttempts = JSON.parse(localStorage.getItem('edunova_attempts') || '[]');
            if (this.exams.length === 0) this.seedDemo();

            // Handle shared exam URL
            const params = new URLSearchParams(window.location.search);
            if (params.get('exam')) {
                const examId = parseInt(params.get('exam'));
                const exam = this.exams.find(e => e.id === examId);
                if (exam) setTimeout(() => this.openExamEntry(exam), 500);
            }
            this.$nextTick(() => lucide.createIcons());
        },

        seedDemo() {
            this.exams = [{
                id: 1, title: 'Kiểm tra Toán cao cấp A1', description: 'Chương 1 - Giới hạn và liên tục',
                duration: 30, passMark: 60, status: 'published', maxAttempts: 3,
                shuffle: true, shuffleOptions: true, showResult: true, requireName: true,
                security: { useAccessKey: true, accessKey: 'MATH2024', noTab: true, noCopy: true, noRightClick: false, forceFullscreen: false, maxTabWarnings: 3 },
                questions: [
                    { _id:1, text:'lim(x→0) (sin x)/x = ?', type:'single', points:1, options:['0','1','∞','Không tồn tại'], correctAnswers:[1], explanation:'Giới hạn cơ bản: lim sin(x)/x = 1' },
                    { _id:2, text:'Hàm f(x)=1/x liên tục tại x=0?', type:'truefalse', points:1, options:['true','false'], correctAnswers:['false'], explanation:'f(x)=1/x không xác định tại x=0' },
                    { _id:3, text:'Đạo hàm của x² là?', type:'single', points:1, options:['x','2x','x²','2'], correctAnswers:[1], explanation:'d/dx(xⁿ) = n·xⁿ⁻¹' },
                ],
            }];
            // Seed some demo attempts
            this.allAttempts = [
                { examId:1, examTitle:'Kiểm tra Toán cao cấp A1', name:'Nguyễn Văn A', score:80, correct:2, total:3, passed:true,  date:'2024-01-10 09:30', timeTaken:'12 phút' },
                { examId:1, examTitle:'Kiểm tra Toán cao cấp A1', name:'Trần Thị B',   score:33, correct:1, total:3, passed:false, date:'2024-01-11 14:20', timeTaken:'8 phút' },
                { examId:1, examTitle:'Kiểm tra Toán cao cấp A1', name:'Lê Văn C',     score:100,correct:3, total:3, passed:true,  date:'2024-01-12 10:00', timeTaken:'5 phút' },
            ];
            this.saveExams(); this.saveAttempts();
        },

        saveExams()    { localStorage.setItem('edunova_exams', JSON.stringify(this.exams)); },
        saveAttempts() { localStorage.setItem('edunova_attempts', JSON.stringify(this.allAttempts)); },

        // ── CRUD ──
        openCreateExam() {
            this.editingExam = null;
            this.examForm = {
                title:'', description:'', duration:30, passMark:60,
                status:'draft', maxAttempts:3,
                shuffle:false, shuffleOptions:false, showResult:true, requireName:true,
                questions:[],
                security:{ useAccessKey:false, accessKey:'', noTab:true, noCopy:true, noRightClick:true, forceFullscreen:false, maxTabWarnings:3 },
            };
            this.editorTab = 'info';
            this.showEditor = true;
            this.$nextTick(() => lucide.createIcons());
        },

        editExam(exam) {
            this.editingExam = exam;
            this.examForm = JSON.parse(JSON.stringify(exam));
            if (!this.examForm.security) this.examForm.security = { useAccessKey:false, accessKey:'', noTab:true, noCopy:true, noRightClick:false, forceFullscreen:false, maxTabWarnings:3 };
            this.editorTab = 'info';
            this.showEditor = true;
            this.$nextTick(() => lucide.createIcons());
        },

        saveExam() {
            if (!this.examForm.title) return;
            if (this.editingExam) {
                const idx = this.exams.findIndex(e => e.id === this.editingExam.id);
                if (idx !== -1) this.exams.splice(idx, 1, { ...this.editingExam, ...this.examForm });
                this.showToast('Đã cập nhật bài thi!');
            } else {
                this.exams.unshift({ ...this.examForm, id: Date.now() });
                this.showToast('Đã tạo bài thi mới!');
            }
            this.saveExams();
            this.showEditor = false;
            this.$nextTick(() => lucide.createIcons());
        },

        deleteExam(id) {
            if (!confirm('Xóa bài thi này? Lịch sử làm bài cũng sẽ bị xóa.')) return;
            this.exams = this.exams.filter(e => e.id !== id);
            this.allAttempts = this.allAttempts.filter(a => a.examId !== id);
            this.saveExams(); this.saveAttempts();
            this.showToast('Đã xóa bài thi');
        },

        duplicateExam(exam) {
            const copy = { ...JSON.parse(JSON.stringify(exam)), id:Date.now(), title:exam.title+' (bản sao)', status:'draft' };
            this.exams.unshift(copy);
            this.saveExams();
            this.showToast('Đã nhân bản bài thi');
            this.$nextTick(() => lucide.createIcons());
        },

        confirmClose() {
            if (this.examForm?.title || this.examForm?.questions?.length > 0) {
                if (!confirm('Bạn có thay đổi chưa lưu. Đóng không?')) return;
            }
            this.showEditor = false;
        },

        // ── Questions ──
        addQuestion() {
            this.examForm.questions.push({ _id:Date.now(), text:'', type:'single', points:1, options:['','','',''], correctAnswers:[], explanation:'' });
            this.editorTab = 'questions';
            this.$nextTick(() => lucide.createIcons());
        },
        removeQuestion(qi)  { this.examForm.questions.splice(qi, 1); },
        moveQuestion(qi, d) {
            const t = qi + d;
            if (t < 0 || t >= this.examForm.questions.length) return;
            const qs = [...this.examForm.questions];
            [qs[qi], qs[t]] = [qs[t], qs[qi]];
            this.examForm.questions = qs;
        },
        addOption(q)         { q.options.push(''); },
        removeOption(q, oi)  { q.options.splice(oi,1); q.correctAnswers = q.correctAnswers.filter(a=>a!==oi).map(a=>a>oi?a-1:a); },
        toggleCorrect(q, oi) {
            if (q.type === 'single') { q.correctAnswers = [oi]; }
            else { const i = q.correctAnswers.indexOf(oi); i===-1 ? q.correctAnswers.push(oi) : q.correctAnswers.splice(i,1); }
        },

        // ── Security ──
        generateKey() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            return Array.from({length:8}, ()=>chars[Math.floor(Math.random()*chars.length)]).join('');
        },

        copyText(text) {
            navigator.clipboard?.writeText(text).then(()=>this.showToast('Đã copy!')).catch(()=>this.showToast('Không thể copy','error'));
        },

        // ── Share ──
        openShareModal(exam) {
            this.shareExam = exam;
            this.urlCopied = false;
            this.showShare = true;
            this.$nextTick(() => lucide.createIcons());
        },

        getShareUrl(exam) {
            if (!exam) return '';
            return `${window.location.origin}${window.location.pathname}?exam=${exam.id}`;
        },

        copyShareUrl() {
            this.copyText(this.getShareUrl(this.shareExam));
            this.urlCopied = true;
            setTimeout(() => this.urlCopied = false, 2000);
        },

        shareVia(method) {
            const url = this.getShareUrl(this.shareExam);
            const title = this.shareExam?.title || 'Bài thi';
            const key = this.shareExam?.security?.useAccessKey ? `\nMã truy cập: ${this.shareExam.security.accessKey}` : '';
            const text = `📝 ${title}\n${url}${key}`;
            if (method === 'copy')      { this.copyShareUrl(); }
            else if (method === 'email')     { window.open(`mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(text)}`); }
            else if (method === 'whatsapp')  { window.open(`https://wa.me/?text=${encodeURIComponent(text)}`); }
        },

        // ── Reports ──
        openReportModal(exam) {
            this.reportExam = exam;
            this.showReport = true;
            this.$nextTick(() => lucide.createIcons());
        },

        getExamAttempts(examId) {
            if (!examId) return [];
            return this.allAttempts.filter(a => a.examId === examId);
        },

        getPassRate(examId) {
            const atts = this.getExamAttempts(examId);
            if (!atts.length) return 0;
            return Math.round(atts.filter(a=>a.passed).length / atts.length * 100);
        },

        getReportStats(examId) {
            const atts = this.getExamAttempts(examId);
            const exam = this.exams.find(e => e.id === examId);
            if (!atts.length) return [
                { label:'Lượt thi', value:'0', color:'text-slate-900' },
                { label:'Tỷ lệ đậu', value:'—', color:'text-slate-400' },
                { label:'Điểm TB', value:'—', color:'text-slate-400' },
                { label:'Điểm cao nhất', value:'—', color:'text-slate-400' },
            ];
            return [
                { label:'Lượt thi',      value: atts.length,                                                    color:'text-slate-900' },
                { label:'Tỷ lệ đậu',     value: this.getPassRate(examId)+'%',                                   color:'text-emerald-600' },
                { label:'Điểm TB',       value: Math.round(atts.reduce((s,a)=>s+a.score,0)/atts.length)+'%',   color:'text-blue-600' },
                { label:'Điểm cao nhất', value: Math.max(...atts.map(a=>a.score))+'%',                          color:'text-violet-600' },
            ];
        },

        getScoreDistribution(examId) {
            const atts = this.getExamAttempts(examId);
            const buckets = ['0-9','10-19','20-29','30-39','40-49','50-59','60-69','70-79','80-89','90-100'];
            const counts = buckets.map((label, i) => {
                const lo = i*10, hi = i===9 ? 100 : i*10+9;
                const count = atts.filter(a => a.score >= lo && a.score <= hi).length;
                return { label, count };
            });
            const max = Math.max(...counts.map(c=>c.count), 1);
            return counts.map(c => ({ ...c, height: Math.max(4, Math.round((c.count/max)*100)) }));
        },

        // ── Exam Entry ──
        openExamEntry(exam) {
            this.entryExam = exam;
            this.entryForm = { name:'', accessKey:'', showKey:false, keyError:false };
            this.showEntry = true;
            this.$nextTick(() => lucide.createIcons());
        },

        startExamFromEntry() {
            // Validate name
            if (this.entryExam.requireName && !this.entryForm.name.trim()) {
                this.showToast('Vui lòng nhập họ tên!', 'error'); return;
            }
            // Validate access key
            if (this.entryExam.security?.useAccessKey) {
                if (this.entryForm.accessKey.trim().toUpperCase() !== (this.entryExam.security.accessKey||'').toUpperCase()) {
                    this.entryForm.keyError = true;
                    this.showToast('Mã truy cập không đúng!', 'error'); return;
                }
            }
            this.entryForm.keyError = false;
            this.showEntry = false;
            this.openExamTaker(this.entryExam, this.entryForm.name.trim());
        },

        // ── Exam Taker ──
        openExamTaker(exam, candidateName = '') {
            if (!exam.questions.length) { this.showToast('Bài thi chưa có câu hỏi!', 'error'); return; }
            this.takerExam = exam;
            let qs = JSON.parse(JSON.stringify(exam.questions));
            if (exam.shuffle) qs = this.shuffleArray(qs);
            this.takerQuestions = qs;
            this.takerState = {
                current:0, answers:{}, timeLeft:exam.duration*60,
                warnings:0, finished:false, score:0, correct:0,
                showExplanation:false, name:candidateName, startTime:Date.now(),
            };
            if (exam.security?.forceFullscreen) document.documentElement.requestFullscreen?.().catch(()=>{});
            this.showTaker = true;
            this.startTimer();
            this.$nextTick(() => lucide.createIcons());
        },

        startTimer() {
            clearInterval(this.takerTimer);
            this.takerTimer = setInterval(() => {
                if (!this.showTaker) { clearInterval(this.takerTimer); return; }
                this.takerState.timeLeft--;
                if (this.takerState.timeLeft <= 0) { clearInterval(this.takerTimer); this.submitExam(); }
            }, 1000);
        },

        formatTime(secs) {
            const m = Math.floor(secs/60), s = secs%60;
            return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        },

        getDisplayOptions(q) {
            if (!q) return [];
            if (q.type === 'truefalse') return [{ text:'✅ Đúng', originalIndex:'true' },{ text:'❌ Sai', originalIndex:'false' }];
            let opts = q.options.map((text,i) => ({ text, originalIndex:i }));
            if (this.takerExam?.shuffleOptions) opts = this.shuffleArray(opts);
            return opts;
        },

        isSelected(qi, oi) {
            const ans = this.takerState.answers[qi];
            return Array.isArray(ans) ? ans.includes(oi) : ans === oi;
        },

        getOptionClass(qi, oi) {
            const selected = this.isSelected(qi, oi);
            if (this.takerState.showExplanation) {
                const q = this.takerQuestions[qi];
                const correct = q.correctAnswers.includes(oi);
                if (correct)  return 'bg-emerald-50 border-emerald-400 text-emerald-800 correct-flash';
                if (selected) return 'bg-rose-50 border-rose-400 text-rose-800';
                return 'border-slate-200 text-slate-600';
            }
            return selected ? 'bg-slate-900 border-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-400 hover:bg-slate-50';
        },

        selectAnswer(qi, oi) {
            if (this.takerState.showExplanation) return;
            const q = this.takerQuestions[qi];
            if (q.type === 'multiple') {
                const cur = this.takerState.answers[qi] || [];
                const idx = cur.indexOf(oi);
                this.takerState.answers[qi] = idx === -1 ? [...cur, oi] : cur.filter(x=>x!==oi);
            } else {
                this.takerState.answers[qi] = oi;
            }
        },

        submitExam() {
            clearInterval(this.takerTimer);
            let correct=0, totalPoints=0, earnedPoints=0;
            this.takerQuestions.forEach((q,qi) => {
                const ans = this.takerState.answers[qi];
                totalPoints += q.points||1;
                const cSet = q.correctAnswers.map(String).sort().join(',');
                const gSet = (Array.isArray(ans)?ans:[ans]).map(String).sort().join(',');
                if (cSet === gSet) { correct++; earnedPoints += q.points||1; }
            });
            const score = totalPoints>0 ? Math.round((earnedPoints/totalPoints)*100) : 0;
            const elapsed = this.takerState.startTime ? Math.round((Date.now()-this.takerState.startTime)/60000) : null;
            this.takerState.correct = correct;
            this.takerState.score   = score;
            this.takerState.finished = true;

            // Save attempt
            const attempt = {
                examId:    this.takerExam.id,
                examTitle: this.takerExam.title,
                name:      this.takerState.name || 'Ẩn danh',
                score,
                correct,
                total:     this.takerQuestions.length,
                passed:    score >= (this.takerExam.passMark || 60),
                date:      new Date().toLocaleString('vi-VN'),
                timeTaken: elapsed ? `${elapsed} phút` : '—',
            };
            this.allAttempts.push(attempt);
            this.saveAttempts();

            if (document.fullscreenElement) document.exitFullscreen?.();
        },

        securityViolation(type) {
            if (!this.showTaker) return;
            const sec = this.takerExam?.security;
            if (type === 'tab' && sec?.noTab) {
                this.takerState.warnings++;
                const max = sec.maxTabWarnings || 3;
                if (this.takerState.warnings >= max) {
                    this.showToast(`Vượt ${max} lần cảnh báo! Nộp bài tự động.`, 'error');
                    setTimeout(() => this.submitExam(), 1500);
                } else {
                    this.showToast(`Cảnh báo ${this.takerState.warnings}/${max}: Đừng rời trang thi!`, 'error');
                }
            }
            if (type === 'copy'       && sec?.noCopy)       this.showToast('Copy bị chặn trong bài thi!', 'error');
            if (type === 'rightclick' && sec?.noRightClick)  this.showToast('Chuột phải bị chặn!', 'error');
        },

        // ── Excel ──
        importExcel(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const lines = e.target.result.split('\n').filter(l=>l.trim());
                    if (lines.length < 2) { this.showToast('File không hợp lệ', 'error'); return; }
                    const info = lines[0].split(',');
                    const newExam = {
                        id:Date.now(), status:'draft', maxAttempts:3,
                        shuffle:true, shuffleOptions:true, showResult:true, requireName:true,
                        security:{ useAccessKey:false, accessKey:'', noTab:true, noCopy:true, noRightClick:false, forceFullscreen:false, maxTabWarnings:3 },
                        title:       info[0]?.trim() || 'Bài thi từ Excel',
                        description: info[1]?.trim() || '',
                        duration:    parseInt(info[2]) || 30,
                        passMark:    parseInt(info[3]) || 60,
                        questions:   [],
                    };
                    for (let i=2; i<lines.length; i++) {
                        const c = lines[i].split(',');
                        if (!c[0]?.trim()) continue;
                        newExam.questions.push({ _id:Date.now()+i, text:c[0].trim(), type:'single', points:1, options:[c[1]||'',c[2]||'',c[3]||'',c[4]||''].filter(Boolean), correctAnswers:[parseInt(c[5])||0], explanation:c[6]?.trim()||'' });
                    }
                    this.exams.unshift(newExam);
                    this.saveExams();
                    this.showToast(`Import thành công ${newExam.questions.length} câu!`);
                    this.$nextTick(() => lucide.createIcons());
                } catch(err) { this.showToast('Lỗi đọc file', 'error'); }
            };
            reader.readAsText(file);
            event.target.value = '';
        },

        importQuestionsFromExcel(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const lines = e.target.result.split('\n').filter(l=>l.trim());
                let added = 0;
                for (const line of lines) {
                    const c = line.split(',');
                    if (!c[0]?.trim() || c[0].toLowerCase()==='question') continue;
                    this.examForm.questions.push({ _id:Date.now()+added, text:c[0].trim(), type:'single', points:1, options:[c[1]||'',c[2]||'',c[3]||'',c[4]||''].filter(Boolean), correctAnswers:[parseInt(c[5])||0], explanation:c[6]?.trim()||'' });
                    added++;
                }
                this.showToast(`Đã thêm ${added} câu hỏi!`);
                this.$nextTick(() => lucide.createIcons());
            };
            reader.readAsText(file);
            event.target.value = '';
        },

        downloadTemplate() {
            const csv = ['Tên bài thi,Mô tả,Thời gian(phút),Điểm đậu(%)','(dòng này là thông tin bài thi)','Câu hỏi,Đáp án A,Đáp án B,Đáp án C,Đáp án D,Đáp án đúng(0-3),Giải thích','Thủ đô Việt Nam?,Hà Nội,TP HCM,Đà Nẵng,Huế,0,Hà Nội là thủ đô VN'].join('\n');
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'}));
            a.download = 'template_baithi.csv'; a.click();
        },

        exportExam(exam) {
            const rows = [[exam.title,exam.description,exam.duration,exam.passMark],['(header)'],['Câu hỏi','Đáp án A','Đáp án B','Đáp án C','Đáp án D','Đáp án đúng','Giải thích'],...exam.questions.map(q=>[q.text,...q.options,q.correctAnswers[0],q.explanation])];
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob(['\uFEFF'+rows.map(r=>r.join(',')).join('\n')],{type:'text/csv;charset=utf-8;'}));
            a.download = `${exam.title.replace(/\s+/g,'_')}.csv`; a.click();
            this.showToast('Đã xuất Excel!');
        },

        // ── Helpers ──
        shuffleArray(arr) {
            const a=[...arr];
            for(let i=a.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[a[i],a[j]]=[a[j],a[i]];}
            return a;
        },

        showToast(message, type='success') {
            this.toast = { show:true, message, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}

document.addEventListener('alpine:initialized', () => lucide.createIcons());
</script>
@endpush
@endsection