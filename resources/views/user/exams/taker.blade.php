<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Làm bài thi - EduNova</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .exam-shield { -webkit-user-select:none; -moz-user-select:none; user-select:none; }
        .ring-track { stroke: #e2e8f0; }
        .ring-progress { stroke:#3b82f6; stroke-linecap:round; transition:stroke-dashoffset .5s ease, stroke .5s ease; transform:rotate(-90deg); transform-origin:center; }
        .ring-progress.warning { stroke:#f59e0b; }
        .ring-progress.danger  { stroke:#ef4444; }
        @keyframes correctPulse { 0%,100%{box-shadow:0 0 0 0 #10b98140;} 50%{box-shadow:0 0 0 8px #10b98100;} }
        .correct-flash { animation: correctPulse .6s ease; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px);} to{opacity:1;transform:translateY(0);} }
        .fade-up { animation: fadeUp .3s ease forwards; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

{{-- Truyền data từ server xuống JS --}}
<script>
    window.__EXAM_DATA__ = @json($examData);
</script>

<div x-data="examTakerApp()" x-init="init()" x-cloak>

    {{-- ══════════════════════════════════════
         ENTRY: NHẬP THÔNG TIN TRƯỚC KHI THI
    ══════════════════════════════════════ --}}
    <div x-show="showEntry" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6 fade-up">

            {{-- Exam header --}}
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-900 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-text" class="w-8 h-8 text-white"></i>
                </div>
                <h2 class="text-xl font-black text-slate-900" x-text="exam?.title"></h2>
                <p class="text-sm text-slate-500 mt-1" x-text="exam?.description"></p>
            </div>

            {{-- Info badges --}}
            <div class="flex justify-center gap-3 flex-wrap">
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="list-checks" class="w-3.5 h-3.5"></i>
                    <span x-text="`${exam?.questions?.length} câu hỏi`"></span>
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span x-text="`${exam?.duration} phút`"></span>
                </span>
                <span class="flex items-center gap-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full">
                    <i data-lucide="target" class="w-3.5 h-3.5"></i>
                    <span x-text="`Đậu ${exam?.passMark}%`"></span>
                </span>
            </div>

            <div class="space-y-4">
                {{-- Họ tên --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Họ và tên
                        <span x-show="exam?.requireName" class="text-red-500">*</span>
                        <span x-show="!exam?.requireName" class="text-slate-400 font-normal">(tùy chọn)</span>
                    </label>
                    <input type="text" x-model="entryForm.name"
                        placeholder="Nhập họ và tên của bạn..."
                        @keydown.enter="startExam()"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                {{-- Access key --}}
                <div x-show="exam?.security?.useAccessKey">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mã truy cập <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="key" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input :type="entryForm.showKey ? 'text' : 'password'"
                            x-model="entryForm.accessKey"
                            placeholder="Nhập mã bảo mật..."
                            @keydown.enter="startExam()"
                            class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-slate-900"
                            :class="entryForm.keyError ? 'border-red-400 ring-2 ring-red-200' : ''">
                        <button @click="entryForm.showKey = !entryForm.showKey"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i :data-lucide="entryForm.showKey ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <p x-show="entryForm.keyError" class="text-xs text-red-500 mt-1 font-semibold">
                        Mã không đúng, vui lòng thử lại
                    </p>
                </div>

                {{-- Lưu ý --}}
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-800 space-y-1.5">
                    <p class="font-bold">Lưu ý trước khi bắt đầu:</p>
                    <p x-show="exam?.security?.noTab">• Không chuyển tab/cửa sổ trong khi thi</p>
                    <p x-show="exam?.security?.noCopy">• Không copy/paste nội dung bài thi</p>
                    <p x-show="exam?.security?.forceFullscreen">• Bài thi sẽ mở toàn màn hình</p>
                    <p x-show="exam?.shuffle">• Câu hỏi được xáo trộn ngẫu nhiên</p>
                    <p>• Thời gian làm bài: <strong x-text="`${exam?.duration} phút`"></strong></p>
                </div>
            </div>

            <div class="flex gap-3">
                <button @click="window.close()"
                    class="flex-1 py-3 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="startExam()"
                    :disabled="(exam?.requireName && !entryForm.name.trim()) || (exam?.security?.useAccessKey && !entryForm.accessKey.trim())"
                    class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 disabled:opacity-40 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="play" class="w-4 h-4"></i> Bắt đầu thi
                </button>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════
         TAKER: GIAO DIỆN LÀM BÀI
    ══════════════════════════════════════ --}}
    <div x-show="showTaker" x-transition
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 exam-shield"
        style="display:none;"
        {{--@contextmenu.prevent="!takerState.showExplanation && securityViolation('rightclick')"--}}
        @copy.prevent="!takerState.showExplanation && securityViolation('copy')"
        @paste.prevent="!takerState.showExplanation && securityViolation('paste')">

        <div class="absolute inset-0 bg-slate-950/95 backdrop-blur-md"></div>
        <div class="relative w-full max-w-3xl max-h-[95vh] flex flex-col">

            {{-- Header --}}
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl mb-4 px-6 py-4 flex items-center justify-between text-white">
                <div class="min-w-0 flex-1 mr-4">
                    <p class="font-black text-lg truncate" x-text="exam?.title"></p>
                    <p class="text-white/60 text-xs mt-0.5"
                        x-text="`${takerState.name || 'Ẩn danh'} · Câu ${takerState.current + 1}/${questions.length}`"></p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    {{-- Đồng hồ đếm ngược --}}
                    <div class="relative w-14 h-14">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
                            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4" class="ring-track"/>
                            <circle cx="28" cy="28" r="24" fill="none" stroke-width="4"
                                class="ring-progress"
                                :class="takerState.timeLeft < 60 ? 'danger' : takerState.timeLeft < 300 ? 'warning' : ''"
                                :stroke-dasharray="`${2 * Math.PI * 24}`"
                                :stroke-dashoffset="`${2 * Math.PI * 24 * (1 - takerState.timeLeft / (exam?.duration * 60))}`"
                                style="transform-origin:28px 28px">
                            </circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-[10px] font-black text-white" x-text="formatTime(takerState.timeLeft)"></span>
                        </div>
                    </div>
                    <button x-show="!takerState.showExplanation"
                        @click="confirmSubmit()"
                        class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold transition-all">
                        Nộp bài
                    </button>
                    <button x-show="takerState.showExplanation"
                        @click="window.close()"
                        class="px-4 py-2 bg-slate-600 hover:bg-slate-500 text-white rounded-xl text-sm font-bold transition-all flex items-center gap-1.5">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Đóng tab
                    </button>
                </div>
            </div>

            {{-- Cảnh báo chuyển tab --}}
            <div x-show="takerState.warnings > 0"
                class="bg-amber-500/20 border border-amber-500/40 rounded-xl px-4 py-2.5 mb-3 flex items-center gap-2 text-amber-300 text-sm font-semibold">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                <span x-text="`Cảnh báo ${takerState.warnings}/${exam?.security?.maxTabWarnings || 3}: Phát hiện chuyển tab!`"></span>
            </div>

            {{-- Câu hỏi --}}
            <div class="bg-white rounded-2xl flex-1 overflow-y-auto p-8" x-show="!takerState.finished">
                <template x-if="questions[takerState.current]" :key="takerState.current">
                     <div :key="takerState.current">
                        <div class="flex gap-3 mb-6">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white text-sm font-black flex items-center justify-center shrink-0"
                                x-text="takerState.current + 1"></span>
                            <div>
                                <p class="text-base font-semibold text-slate-900 leading-relaxed"
                                    x-text="questions[takerState.current].text"></p>
                                <template x-if="questions[takerState.current].type === 'multiple'">
                                    <p class="mt-2 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                                        Chọn nhiều đáp án
                                    </p>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3">
                            {{--
                                opt.originalIndex = index gốc của option (bất biến dù shuffle)
                                oi               = vị trí hiển thị (dùng cho nhãn A/B/C/D)
                                Tất cả logic đúng/sai dùng opt.originalIndex
                            --}}
                            <template x-for="(opt, oi) in getDisplayOptions(questions[takerState.current])" :key="opt.originalIndex">
                                <button @click="selectAnswer(takerState.current, opt.originalIndex)"
                                    class="w-full text-left px-5 py-4 rounded-2xl border-2 transition-all font-semibold text-sm"
                                    :class="getOptionClass(takerState.current, opt.originalIndex)">
                                    <div class="flex items-center gap-3">
                                        {{-- Nhãn A/B/C/D dùng oi (vị trí hiển thị) --}}
                                        <span class="w-7 h-7 rounded-full border-2 flex items-center justify-center shrink-0 text-xs font-black transition-all"
                                            :class="getLetterClass(takerState.current, opt.originalIndex)"
                                            x-text="['A','B','C','D','E','F'][oi]">
                                        </span>
                                        <span x-text="opt.text"></span>

                                        {{-- Badge: chọn ĐÚNG --}}
                                        <span x-show="takerState.showExplanation
                                                && isSelected(takerState.current, opt.originalIndex)
                                                && isCorrectAnswer(takerState.current, opt.originalIndex)"
                                            class="ml-auto flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300 shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Đúng
                                        </span>

                                        {{-- Badge: chọn SAI --}}
                                        <span x-show="takerState.showExplanation
                                                && isSelected(takerState.current, opt.originalIndex)
                                                && !isCorrectAnswer(takerState.current, opt.originalIndex)"
                                            class="ml-auto flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 border border-rose-300 shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Sai
                                        </span>

                                        {{-- Badge: đáp án đúng nhưng KHÔNG chọn --}}
                                        <span x-show="takerState.showExplanation
                                                && !isSelected(takerState.current, opt.originalIndex)
                                                && isCorrectAnswer(takerState.current, opt.originalIndex)"
                                            class="ml-auto flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                                            </svg>
                                            Đáp án đúng
                                        </span>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- Giải thích (sau khi xem lại đáp án) --}}
                        <div x-show="takerState.showExplanation && questions[takerState.current].explanation"
                            class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <p class="text-xs font-bold text-blue-700 mb-1">Giải thích</p>
                            <p class="text-sm text-blue-800" x-text="questions[takerState.current].explanation"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Kết quả --}}
            <div x-show="takerState.finished"
                class="bg-white rounded-2xl flex-1 p-8 flex flex-col items-center justify-center text-center space-y-5">
                {{-- Loading state --}}
                <div x-show="takerState.isSubmitting" class="space-y-4">
                    <div class="flex justify-center">
                        <svg class="w-16 h-16 animate-spin text-slate-900" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">Đang xử lý kết quả...</p>
                </div>

                {{-- Result content --}}
                <div x-show="!takerState.isSubmitting">
                    <div class="w-28 h-28 rounded-full flex items-center justify-center"
                        :class="isPassed ? 'bg-emerald-100' : 'bg-rose-100'">
                        <span class="text-4xl font-black"
                            :class="isPassed ? 'text-emerald-600' : 'text-rose-600'"
                            x-text="takerState.score + '%'"></span>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"
                            x-text="isPassed ? 'Chúc mừng!' : 'Chưa đạt'"></p>
                        <p class="text-slate-500 text-sm mt-1"
                            x-text="`${takerState.name ? takerState.name + ' · ' : ''}Đúng ${takerState.correct}/${questions.length} câu · Điểm đậu ${exam?.passMark}%`"></p>
                    </div>

                    {{-- Chi tiết điểm --}}
                    <div class="flex gap-6 py-4 border-y border-slate-100 w-full justify-center">
                        <div class="text-center">
                            <p class="text-2xl font-black text-emerald-600" x-text="takerState.correct"></p>
                            <p class="text-xs text-slate-500 mt-1">Câu đúng</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-rose-500" x-text="questions.length - takerState.correct"></p>
                            <p class="text-xs text-slate-500 mt-1">Câu sai</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-slate-900" x-text="takerState.score + '%'"></p>
                            <p class="text-xs text-slate-500 mt-1">Điểm số</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="window.close()"
                            class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                            Đóng tab
                        </button>
                        <button x-show="exam?.showResult"
                            @click="takerState.showExplanation = true; takerState.finished = false; takerState.current = 0"
                            class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-700 transition-all">
                            Xem lại đáp án
                        </button>
                    </div>
                </div>
            </div>

            {{-- Điều hướng câu hỏi --}}
            <div x-show="!takerState.finished" class="flex items-center justify-between mt-4">
                {{-- Số câu mini --}}
                <div class="flex gap-1 flex-wrap max-w-sm">
                    <template x-for="(q, qi) in questions" :key="qi">
                        <button @click="takerState.current = qi"
                            class="w-7 h-7 rounded-full text-[10px] font-bold transition-all"
                            :class="{
                                'bg-slate-900 text-white scale-110': qi === takerState.current,
                                'bg-emerald-500 text-white': takerState.answers[q._id] !== undefined && qi !== takerState.current,
                                'bg-white/20 text-white/60 hover:bg-white/30': takerState.answers[q._id] === undefined && qi !== takerState.current,
                            }"
                            x-text="qi + 1">
                        </button>
                    </template>
                </div>

                {{-- Prev / Next --}}
                <div class="flex gap-2">
                    <button @click="takerState.current = Math.max(0, takerState.current - 1)"
                        :disabled="takerState.current === 0"
                        class="px-4 py-2 bg-white/10 text-white rounded-xl text-sm font-semibold disabled:opacity-40 hover:bg-white/20 transition-all">
                        ← Trước
                    </button>
                    <button @click="takerState.current = Math.min(questions.length - 1, takerState.current + 1)"
                        :disabled="takerState.current === questions.length - 1"
                        class="px-4 py-2 bg-white text-slate-900 rounded-xl text-sm font-semibold disabled:opacity-40 hover:bg-slate-100 transition-all">
                        Tiếp →
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[70] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
        :class="toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-600'"
        style="display:none;">
        <i :data-lucide="toast.type === 'error' ? 'alert-circle' : 'check-circle'" class="w-4 h-4"></i>
        <span x-text="toast.message"></span>
    </div>

</div>

{{-- Anti-tab listener --}}
<script>
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) return;
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el._x_dataStack) el._x_dataStack.forEach(d => {
            if (d.showTaker && d.securityViolation && !d.takerState?.showExplanation) d.securityViolation('tab');
        });
    });
});

function examTakerApp() {
    return {
        // ── State ──
        exam:       null,
        questions:  [],
        showEntry:  false,
        showTaker:  false,
        toast:      { show: false, message: '', type: 'success' },

        entryForm: {
            name:    '',
            accessKey: '',
            showKey: false,
            keyError: false,
        },

        takerState: {
            current:         0,
            answers:         {},
            timeLeft:        0,
            warnings:        0,
            finished:        false,
            score:           0,
            correct:         0,
            showExplanation: false,
            name:            '',
            startTime:       null,
            isSubmitting:    false,
        },

        takerTimer: null,

        get isPassed() {
            return this.takerState.score >= (this.exam?.passMark ?? 60);
        },

        // ── Init ──────────────────────────────────────────────────────────
        init() {
            this.exam = window.__EXAM_DATA__ ?? null;

            if (!this.exam) {
                this.showToast('Không tìm thấy dữ liệu bài thi.', 'error');
                return;
            }

            this.showEntry = true;
            this.$nextTick(() => lucide.createIcons());
        },

        // ── Entry ─────────────────────────────────────────────────────────
        startExam() {
            if (this.exam.requireName && !this.entryForm.name.trim()) {
                this.showToast('Vui lòng nhập họ tên!', 'error');
                return;
            }

            if (this.exam.security?.useAccessKey) {
                const submitted = this.entryForm.accessKey.trim().toUpperCase();
                const expected  = (this.exam.security.accessKey ?? '').toUpperCase();
                if (submitted !== expected) {
                    this.entryForm.keyError = true;
                    this.showToast('Mã truy cập không đúng!', 'error');
                    return;
                }
            }

            this.entryForm.keyError = false;
            this.showEntry = false;
            this.beginTaker();
        },

        // ── Taker ─────────────────────────────────────────────────────────
        beginTaker() {
            if (!this.exam.questions?.length) {
                this.showToast('Bài thi chưa có câu hỏi!', 'error');
                return;
            }

            // Shuffle câu hỏi nhưng GIỮ NGUYÊN options gốc (originalIndex bất biến)
            let qs = JSON.parse(JSON.stringify(this.exam.questions));
            if (this.exam.shuffle) qs = this.shuffleArray(qs);
            this.questions = qs;

            this.takerState = {
                current:         0,
                answers:         {},
                timeLeft:        this.exam.duration * 60,
                warnings:        0,
                finished:        false,
                score:           0,
                correct:         0,
                showExplanation: false,
                name:            this.entryForm.name.trim(),
                startTime:       Date.now(),
            };

            if (this.exam.security?.forceFullscreen) {
                document.documentElement.requestFullscreen?.().catch(() => {});
            }

            this.showTaker = true;
            this.startTimer();
            this.$nextTick(() => lucide.createIcons());
        },

        startTimer() {
            clearInterval(this.takerTimer);
            this.takerTimer = setInterval(() => {
                this.takerState.timeLeft--;
                if (this.takerState.timeLeft <= 0) {
                    clearInterval(this.takerTimer);
                    this.submitExam();
                }
            }, 1000);
        },

        formatTime(secs) {
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        // ── Câu hỏi & đáp án ──────────────────────────────────────────────

        // Trả về mảng { text, originalIndex } — originalIndex KHÔNG thay đổi dù shuffle
        getDisplayOptions(q) {
            if (!q) return [];
            if (q.type === 'truefalse') {
                return [
                    { text: 'Đúng', originalIndex: 'true'  },
                    { text: 'Sai',  originalIndex: 'false' },
                ];
            }
            let opts = q.options.map((text, i) => ({ text, originalIndex: i }));
            if (this.exam?.shuffleOptions) opts = this.shuffleArray(opts);
            return opts;
        },

        // Kiểm tra option có được chọn không (dùng originalIndex)
        isSelected(qi, originalIndex) {
        const q = this.questions[qi];
        const ans = this.takerState.answers[q._id];
        
        // Log thông tin để debug
        console.log(`[Debug isSelected] Q_ID: ${q?._id} | Index: ${qi} | OriginalIdx: ${originalIndex} (${typeof originalIndex}) | CurrentAns:`, ans);

        if (ans === undefined || ans === null) return false;

        const isSelected = Array.isArray(ans)
            ? ans.map(String).includes(String(originalIndex))
            : String(ans) === String(originalIndex);

        // Log kết quả cuối cùng
        console.log(`[Debug isSelected] Result for ${originalIndex}: ${isSelected}`);
        
        return isSelected;
    },

        // Kiểm tra option có phải đáp án đúng không (dùng originalIndex)
        isCorrectAnswer(qi, originalIndex) {
            const q = this.questions[qi];
            console.log(`[correctAnswers] Q_ID: ${q?._id} | correctAnswers:`, q?.correctAnswers, `| checking: ${originalIndex}`);
            return q?.correctAnswers?.map(String).includes(String(originalIndex)) ?? false;
        },

        // Class cho button option
        getOptionClass(qi, originalIndex) {
            const selected = this.isSelected(qi, originalIndex);
            const correct = this.isCorrectAnswer(qi, originalIndex);
            
            // Log để kiểm tra trạng thái của từng option trong vòng lặp
            if (this.takerState.showExplanation) {
                console.log(`[Debug Style] Q_Index: ${qi} | Opt_Idx: ${originalIndex} | Selected: ${selected} | Correct: ${correct}`);
            }

            if (this.takerState.showExplanation) {
                // Chọn đúng → xanh đậm
                if (correct && selected)  return 'bg-emerald-50 border-emerald-500 text-emerald-900';
                
                // Đáp án đúng nhưng không chọn → xanh nhạt
                if (correct && !selected) return 'bg-emerald-50 border-emerald-300 text-emerald-800';
                
                // Chọn sai → đỏ
                if (!correct && selected) return 'bg-rose-50 border-rose-500 text-rose-900';
                
                // Không chọn, không đúng → mờ
                return 'border-slate-100 text-slate-400 opacity-50';
            }
            
            return selected
                ? 'bg-slate-900 border-slate-900 text-white'
                : 'border-slate-200 text-slate-700 hover:border-slate-400 hover:bg-slate-50';
        },
        // Class cho vòng tròn nhãn A/B/C/D
        getLetterClass(qi, originalIndex) {
            const selected = this.isSelected(qi, originalIndex);
            if (this.takerState.showExplanation) {
                const correct = this.isCorrectAnswer(qi, originalIndex);
                if (correct && selected)  return 'bg-emerald-500 border-emerald-500 text-white';
                if (correct && !selected) return 'bg-emerald-100 border-emerald-400 text-emerald-700';
                if (!correct && selected) return 'bg-rose-500 border-rose-500 text-white';
                return 'border-slate-200 text-slate-300';
            }
            return selected
                ? 'bg-slate-900 border-slate-900 text-white'
                : 'border-slate-300 text-slate-400';
        },

        // Lưu đáp án (dùng originalIndex làm giá trị)
        selectAnswer(qi, originalIndex) {
            if (this.takerState.showExplanation) return;
            const q   = this.questions[qi];
            const qId = q._id;
            if (q.type === 'multiple') {
                const cur = (this.takerState.answers[qId] || []).map(String);
                const key = String(originalIndex);
                const idx = cur.indexOf(key);
                this.takerState.answers[qId] = idx === -1
                    ? [...cur, key]
                    : cur.filter(x => x !== key);
            } else {
                // Lưu dạng string để so sánh nhất quán
                this.takerState.answers[qId] = String(originalIndex);
            }
        },

        // ── Submit ────────────────────────────────────────────────────────
        confirmSubmit() {
            const answered = Object.keys(this.takerState.answers).length;
            const total    = this.questions.length;
            if (answered < total) {
                if (!confirm(`Bạn còn ${total - answered} câu chưa trả lời. Vẫn nộp bài?`)) return;
            }
            this.submitExam();
        },

        submitExam() {
            clearInterval(this.takerTimer);
            this.takerState.isSubmitting = true;

            let correct = 0, totalPoints = 0, earnedPoints = 0;
            this.questions.forEach(q => {
                const ans      = this.takerState.answers[q._id];
                const points   = q.points ?? 1;
                totalPoints   += points;

                // Chuẩn hoá correctAnswers thành string sorted
                const cSet = q.correctAnswers.map(String).sort().join(',');

                // Chuẩn hoá submitted — bỏ qua nếu undefined/null
                let gSet = '';
                if (ans !== undefined && ans !== null) {
                    gSet = (Array.isArray(ans) ? ans : [ans])
                        .map(String)
                        .filter(v => v !== 'undefined')
                        .sort()
                        .join(',');
                }

                if (cSet === gSet) {
                    correct++;
                    earnedPoints += points;
                }
            });

            const score   = totalPoints > 0 ? Math.round((earnedPoints / totalPoints) * 100) : 0;
            const elapsed = this.takerState.startTime
                ? Math.round((Date.now() - this.takerState.startTime) / 1000)
                : 0;

            this.takerState.correct  = correct;
            this.takerState.score    = score;
            this.takerState.finished = true;

            if (document.fullscreenElement) document.exitFullscreen?.();

            fetch(`{{ route('exams.taker.submit', ['id' => ':id']) }}`.replace(':id', this.exam.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    name:               this.takerState.name,
                    access_key:         this.entryForm.accessKey,
                    answers:            this.takerState.answers,
                    time_taken_seconds: elapsed,
                }),
            })
            .then(async res => {
                if (!res.ok) {
                    const err = await res.json().catch(() => null);
                    throw new Error(err?.message || 'Lưu kết quả thất bại');
                }
                return res.json();
            })
            .then(data => {
                if (data.attempt) {
                    this.takerState.score   = data.attempt.score;
                    this.takerState.correct = data.attempt.correct;
                }
                this.showToast('Kết quả đã được lưu!');
                this.takerState.isSubmitting = false;
            })
            .catch(err => {
                this.showToast(err.message, 'error');
                this.takerState.isSubmitting = false;
            });
        },

        // ── Security ──────────────────────────────────────────────────────
        securityViolation(type) {
            if (!this.showTaker || this.takerState.finished || this.takerState.showExplanation) return;
            const sec = this.exam?.security;

            if (type === 'tab' && sec?.noTab) {
                this.takerState.warnings++;
                const max = sec.maxTabWarnings ?? 3;
                if (this.takerState.warnings >= max) {
                    this.showToast(`Vượt ${max} lần cảnh báo! Nộp bài tự động.`, 'error');
                    setTimeout(() => this.submitExam(), 1500);
                } else {
                    this.showToast(`Cảnh báo ${this.takerState.warnings}/${max}: Đừng rời trang thi!`, 'error');
                }
            }
            if (type === 'copy'       && sec?.noCopy)      this.showToast('Copy bị chặn trong bài thi!', 'error');
            if (type === 'rightclick' && sec?.noRightClick) this.showToast('Chuột phải bị chặn!', 'error');
        },

        // ── Helpers ───────────────────────────────────────────────────────
        shuffleArray(arr) {
            const a = [...arr];
            for (let i = a.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [a[i], a[j]] = [a[j], a[i]];
            }
            return a;
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
    };
}

document.addEventListener('alpine:initialized', () => lucide.createIcons());
</script>

</body>
</html>