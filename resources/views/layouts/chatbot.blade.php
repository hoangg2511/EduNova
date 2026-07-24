{{-- resources/views/layouts/chatbot.blade.php --}}

{{-- FAB Button --}}
<button
    @click="chatOpen = !chatOpen; $nextTick(() => lucide.createIcons())"
    :class="chatOpen ? 'scale-0 opacity-0 pointer-events-none' : 'scale-100 opacity-100'"
    class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-2xl bg-slate-900 text-white shadow-2xl
           flex items-center justify-center transition-all duration-300 hover:scale-110 hover:bg-slate-700 group"
    title="Mở trợ lý AI">
    <i data-lucide="bot" class="w-6 h-6 group-hover:rotate-12 transition-transform duration-200"></i>
</button>

{{-- Chat Panel --}}
<div
    x-show="chatOpen"
    x-cloak
    x-init="$watch('$el.style.display', v => { if (v !== 'none') $nextTick(() => lucide.createIcons()) })"
    x-transition:enter="transition-transform duration-300 ease-out"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition-transform duration-200 ease-in"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="fixed inset-y-0 right-0 z-40 flex w-full max-w-[420px] flex-col border-l border-slate-200 bg-white shadow-2xl">

    <div class="flex flex-col h-full bg-white" x-data="chatbot()">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 py-4 bg-slate-900 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0">
                <i data-lucide="bot" class="w-5 h-5 text-slate-900"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-white text-sm leading-tight">Trợ lý EduNova AI</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs text-slate-400">Sẵn sàng hỗ trợ</span>
                    </div>
                    {{-- Token badge --}}
                    <button @click="$store.wallet.openModal('token')"
                        class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                        x-show="tokenLimit !== null" title="Hết token? Bấm để đổi coin lấy thêm">
                        <i data-lucide="zap" class="w-3 h-3 text-yellow-400"></i>
                        <span class="text-[10px] font-bold"
                            :class="tokenLimit <= 500 ? 'text-rose-400' : 'text-yellow-400'"
                            x-text="tokenLimit >= 1000 
                                ? (tokenLimit / 1000).toFixed(1) + 'K' 
                                : tokenLimit + ' token'">
                        </span>
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button @click="clearChat()"
                    class="p-2 rounded-lg hover:bg-white/10 transition-colors text-white"
                    title="Xóa hội thoại">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
                <button @click="$dispatch('close-chat')"
                    class="p-2 rounded-lg hover:bg-white/10 transition-colors text-white"
                    title="Đóng">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div id="chatMessages"
            class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-slate-50">

            {{-- Chào mừng --}}
            <div class="flex gap-3 items-end">
                <div class="w-7 h-7 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0 mb-1">
                    <i data-lucide="bot" class="w-4 h-4 text-slate-900"></i>
                </div>
                <div class="max-w-[78%]">
                    <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                        <p class="text-sm text-slate-700 leading-relaxed">
                            Xin chào! Tôi là trợ lý AI của EduNova. Hỏi tôi bất cứ điều gì về học tập nhé! 🎓<br>
                            <span style="font-size:0.75rem;color:#94a3b8;">💡 Mẹo: Kéo thả các chủ đề từ lộ trình học vào đây!</span>
                        </p>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">EduNova AI</p>
                </div>
            </div>

            {{-- Hint chips --}}
            <div class="flex flex-wrap gap-2 pl-10">
                <template x-for="hint in hints" :key="hint">
                    <button @click="sendHint(hint)"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-full text-xs text-slate-600
                               hover:border-slate-900 hover:text-slate-900 transition-all"
                        x-text="hint">
                    </button>
                </template>
            </div>

            {{-- Tin nhắn --}}
            <template x-for="(msg, i) in messages" :key="i">
                <div>
                    <div x-show="msg.role === 'assistant'" class="flex gap-3 items-end">
                        <div class="w-7 h-7 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0 mb-1">
                            <i data-lucide="bot" class="w-4 h-4 text-slate-900"></i>
                        </div>
                        <div class="max-w-[78%]">
                            <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                                <p class="text-sm text-slate-700 leading-relaxed" x-html="formatMsg(msg.content)"></p>
                            </div>

                            {{-- Card tài liệu gợi ý — chỉ hiện khi msg có documents --}}
                            <template x-if="msg.documents && msg.documents.length">
                                <div class="mt-2 space-y-1.5">
                                    <template x-for="doc in msg.documents" :key="doc.id">
                                        <a :href="doc.url" target="_blank" rel="noopener"
                                            class="flex items-center gap-3 p-2.5 bg-white border border-slate-200 rounded-xl hover:border-slate-400 hover:shadow-sm transition-all">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                                :style="`background:${doc.color}15`">
                                                <i :data-lucide="doc.icon" class="w-4 h-4" :style="`color:${doc.color}`"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-bold text-slate-800 truncate" x-text="doc.name"></p>
                                                <p class="text-[11px] text-slate-400 truncate"
                                                    x-text="doc.description || (doc.type ? doc.type.toUpperCase() : '')"></p>
                                            </div>
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            <p class="text-[10px] text-slate-400 mt-1 ml-1">EduNova AI</p>
                        </div>
                    </div>
                    <div x-show="msg.role === 'user'" class="flex gap-3 items-end justify-end">
                        <div class="max-w-[78%]">
                            <div class="bg-slate-900 rounded-2xl rounded-br-md px-4 py-3">
                                <p class="text-sm text-white leading-relaxed" x-text="msg.content"></p>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 text-right mr-1">Bạn</p>
                        </div>
                        <div class="w-7 h-7 rounded-xl bg-slate-200 flex items-center justify-center shrink-0 mb-1">
                            <i data-lucide="user" class="w-4 h-4 text-slate-600"></i>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Typing --}}
            <div x-show="loading" class="flex gap-3 items-end">
                <div class="w-7 h-7 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0 mb-1">
                    <i data-lucide="bot" class="w-4 h-4 text-slate-900"></i>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                    <div class="flex gap-1 items-center h-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Drop Zone Indicator --}}
        <div id="dropZoneIndicator"
            class="hidden absolute inset-0 z-50 flex items-center justify-center rounded-2xl border-2 border-dashed border-indigo-500 bg-indigo-100/70 pointer-events-none">
            <div class="text-center px-4 py-3">
                <p class="text-sm font-semibold text-indigo-700">📌 Thả nội dung tại đây</p>
            </div>
        </div>

        {{-- ✅ Banner hết token — đổi coin lấy token ngay tại đây --}}
        <div x-show="tokenLimit <= 0" x-cloak
            class="mx-4 mb-2 px-4 py-3 rounded-2xl bg-rose-50 border border-rose-200 flex items-center gap-3 shrink-0">
            <i data-lucide="battery-warning" class="w-5 h-5 text-rose-500 shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-rose-700">Bạn đã hết token chat</p>
                <p class="text-[11px] text-rose-500">Dùng coin để đổi thêm token và tiếp tục trò chuyện.</p>
            </div>
            <button @click="$store.wallet.openModal('token')"
                class="shrink-0 px-3 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition-all">
                Đổi coin
            </button>
        </div>

        {{-- Tag selector --}}
        <div class="px-4 pt-3 pb-0 border-t border-slate-200 bg-slate-50 shrink-0">
            <div class="flex items-center gap-2">
                <div class="flex gap-1.5 flex-wrap">
                    <template x-for="tag in availableTags" :key="tag.key">
                        <button
                            @click="selectedTag = (selectedTag?.key === tag.key) ? null : tag"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all"
                            :class="selectedTag?.key === tag.key
                                ? 'bg-slate-900 text-white border-slate-900'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400 hover:text-slate-900'">
                            <i :data-lucide="tag.icon" class="w-3 h-3"></i>
                            <span x-text="tag.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
        {{-- Input --}}
        <div id="inputContainer"
            class="px-4 pb-4 pt-3 bg-slate-50 shrink-0"
            @dragover.prevent="showDropZone = true"
            @dragleave.prevent="showDropZone = false"
            @drop.prevent="handleDrop($event); showDropZone = false"
            x-data="{ showDropZone: false }">

            {{-- Selected tag pill bên trong input wrapper --}}
            <div x-show="selectedTag"
                class="flex items-center gap-1.5 mb-2">
                <div class="flex items-center gap-1.5 px-2.5 py-1 bg-slate-900 text-white rounded-full text-[11px] font-bold">
                    <i :data-lucide="selectedTag?.icon" class="w-3 h-3"></i>
                    <span x-text="selectedTag?.label"></span>
                    <button @click="selectedTag = null"
                        class="ml-0.5 hover:text-slate-300 transition-colors">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2 items-end">
                <textarea
                    x-model="input"
                    @keydown.enter="if ($event.shiftKey) return; $event.preventDefault(); send();"
                    :disabled="loading"
                    :placeholder="selectedTag  
                        ? (selectedTag.key === 'recommend_document' 
                            ? 'Mô tả chủ đề tài liệu bạn cần (VD: đạo hàm, ngữ pháp tiếng Anh...)' 
                            : `Mô tả để ${selectedTag.label.toLowerCase()}...`)
                        : 'Nhập câu hỏi... (Enter để gửi)'"
                    rows="1"
                    class="flex-1 px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800
                        focus:outline-none focus:ring-2 focus:ring-slate-900 resize-none
                        disabled:opacity-50 transition-all"
                    style="max-height:120px;"
                    @input="autoResize($event.target)">
                </textarea>
                <button @click="send()"
                    :disabled="loading || !input.trim()"
                    class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center
                        hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed
                        transition-all active:scale-95 shrink-0">
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 text-center">Shift+Enter xuống dòng · Kéo thả chủ đề từ lộ trình</p>
        </div>

            </div>
        </div>

{{-- Script chatbot — chỉ load 1 lần nhờ @once --}}
@once
@push('scripts')
<script>
    document.addEventListener('close-chat', () => {
        const wrapper = document.querySelector('[x-data]');
        if (wrapper && wrapper._x_dataStack) {
            wrapper._x_dataStack[0].chatOpen = false;
            setTimeout(() => lucide.createIcons(), 50);
        }
    });

    document.addEventListener('alpine:initialized', () => lucide.createIcons());

    function chatbot() {
        return {
            input: '',
            loading: false,
            messages: [],
            tokenLimit: {{ $tokenLimit ?? 0 }},
            hints: ['Tạo lộ trình học Python', 'Giải thích Flexbox CSS', 'Mẹo học TOEIC'],
            selectedTag: null,
            availableTags: [
                { key: 'create_exam',       label: 'Tạo bài thi',        icon: 'file-text' },
                { key: 'create_schedule',   label: 'Tạo lịch cá nhân',   icon: 'calendar'  },
                { key: 'create_flashcard',  label: 'Tạo flash card',     icon: 'layers'    },
                { key: 'recommend_document',label: 'Gợi ý tài liệu',     icon: 'search'    }, // MỚI
            ],

            init() {
                this.$nextTick(() => {
                    lucide.createIcons();
                    this.$watch('selectedTag', () => this.$nextTick(() => lucide.createIcons()));
                    // Load chat history from server (only active messages)
                    fetch('/api/chatbot/history', { credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.success && Array.isArray(data.messages)) {
                                this.messages = data.messages.map(m => ({
                                    role: m.role,
                                    content: m.content,
                                    ...(Array.isArray(m.documents) && m.documents.length ? { documents: m.documents } : {}),
                                }));    

                                this.$nextTick(() => { this.scrollBottom(); lucide.createIcons(); });
                            }
                        }).catch(err => {
                            console.warn('Không thể tải lịch sử chat:', err);
                        });

                    // Gắn sự kiện drag trên panel để hiển thị drop indicator và cho phép thả cả section lớn
                    const panel = this.$el;
                    const dropIndicator = document.getElementById('dropZoneIndicator');
                    if (panel) {
                        panel.addEventListener('dragenter', (ev) => { ev.preventDefault(); if (dropIndicator) dropIndicator.style.display = 'flex'; });
                        panel.addEventListener('dragover', (ev) => { ev.preventDefault(); if (dropIndicator) dropIndicator.style.display = 'flex'; });
                        panel.addEventListener('dragleave', (ev) => { if (dropIndicator) dropIndicator.style.display = 'none'; });
                        panel.addEventListener('drop', (ev) => { ev.preventDefault(); if (dropIndicator) dropIndicator.style.display = 'none'; this.handleDrop(ev); });
                    }
                });

                // ✅ Khi mua thêm token thành công ở bất kỳ đâu (topbar, modal toàn cục...)
                // đồng bộ lại tokenLimit cục bộ của chatbot ngay lập tức.
                window.addEventListener('wallet:purchased', (e) => {
                    if (e.detail?.token_limit !== undefined) {
                        this.tokenLimit = e.detail.token_limit;
                    }
                });
            },

            async clearChat() {
                if (!this.messages.length) return;
                if (!confirm('Xóa toàn bộ hội thoại?')) return;

                try {
                    const resp = await fetch('/api/chatbot/clear', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({}),
                    });

                    if (!resp.ok) throw new Error('Lỗi server');
                    const data = await resp.json();
                    if (data && data.success) {
                        this.messages = [];
                        this.$nextTick(() => lucide.createIcons());
                    } else {
                        throw new Error(data?.message || 'Không thể xóa');
                    }
                } catch (err) {
                    showToast('Xóa lịch sử thất bại: ' + (err.message || err), 'error');
                }
            },

            sendHint(hint) { this.input = hint; this.send(); },

            handleDrop(e) {
                try {
                    const data = e.dataTransfer.getData('application/json');
                    if (data) {
                        const cardData = JSON.parse(data);
                        const message = this.formatDroppedContent(cardData);
                        this.input = message;
                        this.$nextTick(() => {
                            const ta = this.$el.querySelector('textarea');
                            if (ta) this.autoResize(ta);
                        });
                    }
                } catch (err) {
                    console.error('Lỗi drop:', err);
                }
            },

            formatDroppedContent(cardData) {
                // Level 1: Entire Roadmap (Toàn bộ)
                if (cardData && cardData.level === 1 && Array.isArray(cardData.sections)) {
                    let message = ` LỘ TRÌNH HỌC: ${cardData.title}\n`;
                    message += `${cardData.description || ''}\n\n`;
                    message += `CÁC CHỦ ĐỀ CHÍNH:\n`;
                    cardData.sections.forEach((sec, i) => {
                        message += `${i + 1}. ${sec.title}\n`;
                        if (sec.description) message += `   ${sec.description}\n`;
                        if (Array.isArray(sec.items) && sec.items.length) {
                            const sample = sec.items.slice(0, 3).map(it => it.title).join(', ');
                            message += `   Ví dụ: ${sample}${sec.items.length > 3 ? ', ...' : ''}\n`;
                        }
                    });
                    message += `\n Yêu cầu: Tóm tắt lộ trình này thành kế hoạch học tập 4-6 tuần, chia rõ từng tuần và mục tiêu cụ thể.`;
                    return message.trim();
                }

                // Level 2: Section with Items (Các mục lớn)
                if (cardData && cardData.level === 2 && Array.isArray(cardData.items)) {
                    let message = `📌 CHỦĐỀ: ${cardData.title}\n`;
                    if (cardData.description) message += `${cardData.description}\n`;
                    message += `\nCÁC MỤC CON:\n`;
                    cardData.items.forEach((it, i) => {
                        message += `${i + 1}. ${it.title}`;
                        if (it.content) message += ` — ${it.content.replace(/\n/g, ' ').slice(0, 150)}`;
                        message += `\n`;
                    });
                    message += `\nYêu cầu: Tóm tắt các mục trên hoặc tạo hướng dẫn chi tiết để học.`;
                    return message.trim();
                }

                // Level 3: Single Item (Nội dung con)
                if (cardData && cardData.level === 3) {
                    let message = ` NỘI DUNG: ${cardData.title}\n`;
                    if (cardData.section) message += `Chủ đề: ${cardData.section}\n`;
                    if (cardData.subsection) message += `Phân nhánh: ${cardData.subsection}\n`;
                    message += `\n`;
                    if (cardData.content) message += `${cardData.content}\n`;
                    if (cardData.formula) message += `\n CÔNG THỨC:\n${cardData.formula}\n`;
                    if (cardData.example) message += `\n VÍ DỤ:\n${cardData.example}\n`;
                    message += `\n Yêu cầu: Giải thích, mở rộng hoặc cung cấp thêm ví dụ cho nội dung này.`;
                    return message.trim();
                }

                // Legacy: Array with items (section from old format)
                if (cardData && Array.isArray(cardData.items)) {
                    let message = ` CHỦĐỀ: ${cardData.title}\n`;
                    if (cardData.description) message += `${cardData.description}\n`;
                    message += `\nCÁC MỤC CON:\n`;
                    cardData.items.forEach((it, i) => {
                        message += `${i + 1}. ${it.title}${it.content ? ' — ' + it.content.replace(/\n/g, ' ').slice(0, 150) : ''}\n`;
                    });
                    message += `\nYêu cầu: Tóm tắt hoặc tạo hướng dẫn.`;
                    return message.trim();
                }

                // Fallback: Single card
                let message = `${cardData.title || ''}\n`;
                if (cardData.topic) message += `(Chủ đề: ${cardData.topic})\n`;
                if (cardData.content) message += `\n${cardData.content}\n`;
                if (cardData.formula) message += `\nCông thức: ${cardData.formula}\n`;
                if (cardData.example) message += `\nVí dụ: ${cardData.example}\n`;
                return message.trim();
            },

           // Thay toàn bộ hàm send() trong chatbot.blade.php

            async send() {
                const text = this.input.trim();
                if (!text || this.loading) return;

                const activeTag = this.selectedTag?.key ?? null;

                // ── Kiểm tra token trên FE trước (chỉ với chat thường) ──────────
                if (!activeTag && this.tokenLimit <= 0) {
                    showToast('Bạn đã hết token chat. Hãy đổi coin để nạp thêm.', 'warning');
                    return;
                }

                this.messages.push({ role: 'user', content: text });
                this.input   = '';
                this.loading = true;
                // ⚠ KHÔNG reset selectedTag ở đây nữa — chỉ reset sau khi biết AI đã "done" hay vẫn "ask"
                // (xem xử lý ở cuối hàm, dựa trên header X-Chat-Tag-Status)

                this.$nextTick(() => { this.scrollBottom(); lucide.createIcons(); });
                const ta = this.$el.querySelector('textarea');
                if (ta) ta.style.height = 'auto';

                const history        = this.messages.slice(-10);
                const assistantIndex = this.messages.push({ role: 'assistant', content: '' }) - 1;

                if (activeTag) {
                    const tagLabels = {
                        create_exam:      'Đang tạo bài thi',
                        create_schedule:  'Đang lập lịch học',
                        create_flashcard: 'Đang tạo flash card',
                    };
                    this.messages[assistantIndex].content = tagLabels[activeTag] + '...';
                    this.$nextTick(() => this.scrollBottom());
                }

                const tokenBefore = this.tokenLimit;

                try {
                    const response = await fetch('/api/chatbot/stream', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ message: text, history, tag: activeTag }),
                    });

                    if (!response.ok) {
                        const errorBody = await response.text();
                        throw new Error(errorBody || 'Lỗi server');
                    }

                    // ← Đọc trạng thái tag ngay khi có header, trước khi đọc hết stream
                    const tagStatus = response.headers.get('X-Chat-Tag-Status'); // 'ask' | 'done' | 'error' | null

                    const reader = response.body?.getReader();
                    if (!reader) throw new Error('Không hỗ trợ streaming.');

                    this.messages[assistantIndex].content = '';

                    const decoder = new TextDecoder();
                    let   done      = false;
                    let   fullReply = '';

                    while (!done) {
                        const { value, done: readerDone } = await reader.read();
                        if (readerDone) { done = true; break; }
                        const chunk = decoder.decode(value, { stream: true });
                        if (chunk) {
                            this.messages[assistantIndex].content += chunk;
                            fullReply += chunk;
                            this.$nextTick(() => this.scrollBottom());
                        }
                    }
                    const docsMatch = fullReply.match(/<!--DOCS_JSON:([\s\S]*?)-->/);
                    if (docsMatch) {
                        try {
                            const parsed = JSON.parse(docsMatch[1]);
                            if (Array.isArray(parsed.documents) && parsed.documents.length) {
                                this.messages[assistantIndex].documents = parsed.documents;
                            }
                        } catch (e) {
                            console.warn('Không parse được DOCS_JSON:', e);
                        }
                        // Xoá marker khỏi nội dung hiển thị
                        const cleaned = fullReply.replace(docsMatch[0], '').trim();
                        this.messages[assistantIndex].content = cleaned;
                        fullReply = cleaned;
                    }
                    if (!activeTag) {
                        const used = Math.ceil((text.length + fullReply.length) / 4);
                        this.tokenLimit = Math.max(0, this.tokenLimit - used);
                    }

                    // ── Quyết định có giữ tag hay reset, dựa trên tagStatus ──────────
                    if (activeTag) {
                        if (tagStatus === 'ask') {
                            // AI vẫn cần thêm thông tin — GIỮ nguyên selectedTag để người dùng trả lời tiếp
                            showToast('AI cần thêm thông tin, hãy trả lời câu hỏi ở trên nhé.', 'warning');
                        } else {
                            // Đã tạo xong (hoặc lỗi DB) — kết thúc phiên tag này
                            this.selectedTag = null;
                            if (activeTag === 'create_flashcard' && tagStatus === 'done') {
                                window.dispatchEvent(new CustomEvent('chatbot:flashcard-created'));
                            }
                        }
                    }

                } catch (err) {
                    if (err.message?.includes('hết token')) {
                        this.tokenLimit = 0;
                    }
                    const msg = err instanceof Error ? err.message : 'Lỗi khi nhận phản hồi.';
                    this.messages[assistantIndex].content = '⚠️ ' + msg;
                    // Lỗi mạng/server: không reset tag, để người dùng có thể bấm gửi lại mà không phải chọn lại tag
                } finally {
                    this.loading = false;
                    this.$nextTick(() => { this.scrollBottom(); lucide.createIcons(); });
                }
            },

            scrollBottom() {
                const el = document.getElementById('chatMessages');
                if (el) el.scrollTop = el.scrollHeight;
            },

            autoResize(el) {
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 120) + 'px';
            },

            formatMsg(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/`(.*?)`/g, '<code class="bg-slate-100 px-1 rounded text-xs font-mono">$1</code>')
                    .replace(/\n/g, '<br>');
            },
        };
    }
</script>
@endpush
@endonce