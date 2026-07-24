@extends('layouts.app')

@section('content')
<div x-data="knowledgeShow()" x-init="init()" class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 sm:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto">

        {{-- Header --}}
        <div class="mb-8">
            <nav class="flex items-center gap-2 mb-4 text-sm">
                <a href="{{ route('user.knowledge') }}" class="text-blue-600 hover:text-blue-700 font-medium">Lộ trình học</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600" x-text="treeData.ten_chuyen_de || '{{ $knowledge->title }}'"></span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1">
                    <template x-if="!isEditMode">
                        <div>
                            <h1 class="text-4xl font-bold text-gray-900" x-text="treeData.ten_chuyen_de"></h1>
                            <p class="text-gray-600 mt-2" x-text="treeData.mo_ta || 'Chưa có mô tả'"></p>
                        </div>
                    </template>
                    <template x-if="isEditMode">
                        <div class="space-y-2">
                            <input type="text" x-model="treeData.ten_chuyen_de"
                                class="text-2xl font-bold text-gray-900 w-full border-b-2 border-blue-400 focus:outline-none focus:border-blue-600 bg-transparent pb-1">
                            <textarea x-model="treeData.mo_ta" rows="2"
                                class="w-full text-gray-600 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"></textarea>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium capitalize">
                        {{ $knowledge->format }}
                    </span>
                    <span class="px-3 py-1 {{ $knowledge->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} rounded-full text-sm font-medium">
                        {{ $knowledge->status === 'published' ? 'Đã xuất bản' : 'Bản nháp' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-6 text-sm text-gray-600">
                <div><span class="font-medium">Tạo lúc:</span> {{ $knowledge->created_at->format('d/m/Y H:i') }}</div>
                @if($knowledge->published_at)
                    <div><span class="font-medium">Xuất bản lúc:</span> {{ $knowledge->published_at->format('d/m/Y H:i') }}</div>
                @endif
                <div><span class="font-medium">Lượt xem:</span> {{ $knowledge->view_count }}</div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <h2 class="text-2xl font-bold text-gray-900">Lộ trình học tập</h2>
            <div class="flex gap-2 flex-wrap items-center">
                <button x-show="!isEditMode" @click="toggleEditMode()"
                    class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                    <i data-lucide="pencil" class="w-4 h-4"></i> Chỉnh sửa
                </button>
                <p x-show="isEditMode" class="text-xs text-slate-500 flex items-center gap-1.5 px-1">
                    <i data-lucide="move" class="w-3.5 h-3.5"></i> Kéo thả để sắp xếp lại thứ tự
                </p>
                <button x-show="isEditMode" @click="addSection()"
                    class="flex items-center gap-2 px-4 py-2 border border-blue-300 text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition">
                    <i data-lucide="plus" class="w-4 h-4"></i> Thêm chủ đề lớn
                </button>
                <button x-show="isEditMode" @click="saveEdits()" :disabled="saving"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition">
                    <i :data-lucide="saving ? 'loader' : 'check'" :class="saving ? 'animate-spin' : ''" class="w-4 h-4"></i>
                    <span x-text="saving ? 'Đang lưu...' : 'Lưu thay đổi'"></span>
                </button>
                <button x-show="isEditMode" @click="cancelEdit()"
                    class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-600 hover:bg-gray-50 transition">
                    Hủy
                </button>
            </div>
        </div>

        {{-- Knowledge Tree --}}
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <template x-if="!treeData.cac_chu_de_lon || treeData.cac_chu_de_lon.length === 0">
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">Chưa có dữ liệu lộ trình</p>
                </div>
            </template>

            <div class="space-y-5" x-show="treeData.cac_chu_de_lon && treeData.cac_chu_de_lon.length > 0">
                <template x-for="(lon, sIdx) in (treeData.cac_chu_de_lon || [])" :key="sIdx">
                    <div
                        :draggable="true"
                        @dragstart="sectionDragStart($event, sIdx)"
                        @dragover.prevent="sectionDragOver($event, sIdx)"
                        @drop.prevent="sectionDrop($event, sIdx)"
                        @dragend="sectionDragEnd()"
                        class="rounded-2xl overflow-hidden shadow-md transition-all border-[3px] cursor-grab"
                        :style="`border-color:${color(sIdx).border};background:${color(sIdx).light}`"
                        :class="{
                            'opacity-40': draggingSection === sIdx,
                            'ring-2 ring-blue-400 ring-offset-2': dragOverSection === sIdx && draggingSection !== null && draggingSection !== sIdx
                        }">

                        {{-- Section header --}}
                        <div class="px-6 py-4" :style="`background:${color(sIdx).header}`">
                            <template x-if="!isEditMode">
                                <div>
                                    <h4 class="text-white font-black text-lg flex items-center gap-2">
                                        <i data-lucide="grip-vertical" class="w-4 h-4 opacity-60"></i>
                                        <span x-text="lon.ten"></span>
                                    </h4>
                                    <p x-show="lon.mo_ta" class="text-white/80 text-xs mt-1 ml-6" x-text="lon.mo_ta"></p>
                                </div>
                            </template>
                            <template x-if="isEditMode">
                                <div class="flex gap-2 items-start">
                                    <i data-lucide="grip-vertical" class="w-4 h-4 text-white/60 mt-2 shrink-0"></i>
                                    <div class="flex-1 space-y-1.5">
                                        <input type="text" x-model="lon.ten"
                                            class="w-full bg-white/15 border border-white/40 rounded-lg px-2.5 py-1.5 text-white font-black text-[1.05rem]">
                                        <textarea x-model="lon.mo_ta" rows="1" placeholder="Mô tả chủ đề..."
                                            class="w-full bg-white/10 border border-white/30 rounded-md px-2.5 py-1.5 text-white text-sm resize-y"></textarea>
                                    </div>
                                    <button @click.stop="removeSection(sIdx)" title="Xoá chủ đề lớn"
                                        class="bg-white/20 hover:bg-white/30 rounded-lg p-2 text-white shrink-0 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Items grid --}}
                        <div class="grid gap-3.5 p-4" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
                            <template x-for="(item, iIdx) in (lon.cac_chu_de_con || [])" :key="iIdx">
                                <div
                                    :draggable="true"
                                    @dragstart.stop="itemDragStart($event, sIdx, iIdx, lon, item)"
                                    @dragover.prevent.stop="itemDragOver($event, sIdx, iIdx)"
                                    @drop.prevent.stop="itemDrop($event, sIdx, iIdx)"
                                    @dragend.stop="itemDragEnd()"
                                    class="bg-white rounded-[10px] p-3.5 shadow-sm transition-all border-2 cursor-grab"
                                    :style="`border-color:${color(sIdx).border}`"
                                    :class="{
                                        'opacity-40': draggingItem && draggingItem.s === sIdx && draggingItem.i === iIdx,
                                        'ring-2 ring-blue-400 ring-offset-1': dragOverItem && dragOverItem.s === sIdx && dragOverItem.i === iIdx
                                    }">

                                    <template x-if="!isEditMode">
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm mb-2 flex items-center gap-1.5">
                                                <i data-lucide="grip-vertical" class="w-3 h-3 text-slate-300"></i>
                                                <span x-text="item.ten"></span>
                                            </div>
                                            <p x-show="item.noi_dung" class="text-xs text-slate-600 leading-relaxed mb-2" x-text="item.noi_dung"></p>
                                            <div x-show="item.cong_thuc" class="bg-slate-900 rounded-lg p-2 mb-2">
                                                <code class="text-xs text-yellow-300 font-mono" x-text="item.cong_thuc"></code>
                                            </div>
                                            <div x-show="item.vi_du" class="bg-slate-50 border-l-[3px] rounded p-2 text-xs text-slate-600"
                                                :style="`border-color:${color(sIdx).header}`">
                                                <strong>VD:</strong> <span x-text="(item.vi_du || '').slice(0,100)"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="isEditMode">
                                        <div>
                                            <div class="flex justify-between gap-1.5 mb-2">
                                                <input type="text" x-model="item.ten"
                                                    class="flex-1 font-bold text-sm border border-slate-200 rounded-md px-2 py-1">
                                                <button @click.stop="removeItem(sIdx, iIdx)" title="Xoá mục"
                                                    class="bg-rose-100 hover:bg-rose-200 rounded-md px-2 py-1 text-rose-600 transition">
                                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                            <textarea x-model="item.noi_dung" rows="2" placeholder="Nội dung..."
                                                class="w-full text-[0.82rem] border border-slate-200 rounded-md px-2 py-1.5 mb-1.5 resize-y"></textarea>
                                            <input type="text" x-model="item.cong_thuc" placeholder="Công thức (nếu có)"
                                                class="w-full font-mono text-[0.78rem] border border-slate-200 rounded-md px-2 py-1.5 mb-1.5">
                                            <textarea x-model="item.vi_du" rows="2" placeholder="Ví dụ..."
                                                class="w-full text-[0.8rem] border border-slate-200 rounded-md px-2 py-1.5 resize-y"></textarea>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <button x-show="isEditMode" @click.stop="addItem(sIdx)"
                            class="mx-4 mb-4 px-3.5 py-2 border-2 border-dashed rounded-lg text-sm font-semibold transition hover:bg-white/50"
                            :style="`border-color:${color(sIdx).border};color:${color(sIdx).header}`">
                            <i data-lucide="plus" class="w-3.5 h-3.5 inline-block -mt-0.5"></i> Thêm mục con
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex gap-4 justify-center sm:justify-start">
            @if($knowledge->status === 'draft')
                <button @click="publishRoadmap()"
                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    Xuất bản
                </button>
            @endif
            <a href="{{ route('user.knowledge') }}"
                class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition">
                Quay lại
            </a>
        </div>

    </div>

    {{-- Confirm modal (thay cho confirm() của trình duyệt) --}}
    <div x-show="confirmModal.open" x-transition class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/50" @click="resolveConfirm(false)"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                    :class="confirmModal.danger ? 'bg-rose-100' : 'bg-blue-100'">
                    <i :data-lucide="confirmModal.danger ? 'alert-triangle' : 'help-circle'"
                       class="w-5 h-5" :class="confirmModal.danger ? 'text-rose-500' : 'text-blue-500'"></i>
                </div>
                <div class="flex-1 pt-0.5">
                    <h3 class="font-bold text-gray-900" x-text="confirmModal.title"></h3>
                    <p class="text-sm text-gray-500 mt-1" x-text="confirmModal.message"></p>
                </div>
            </div>
            <div class="flex gap-2.5">
                <button @click="resolveConfirm(false)"
                    class="flex-1 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Hủy
                </button>
                <button @click="resolveConfirm(true)"
                    class="flex-1 py-2 rounded-lg text-sm font-medium text-white transition"
                    :class="confirmModal.danger ? 'bg-rose-500 hover:bg-rose-600' : 'bg-blue-600 hover:bg-blue-700'"
                    x-text="confirmModal.confirmText">
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

<script>
function knowledgeShow() {
    return {
        knowledgeId: {{ $knowledge->id }},
        originalData: @json($knowledge->data),
        treeData: {},
        isEditMode: false,
        saving: false,

        toast: { show: false, message: '', type: 'success' },
        confirmModal: { open: false, title: '', message: '', confirmText: 'Xác nhận', danger: false },
        _confirmResolve: null,

        draggingSection: null,
        dragOverSection: null,
        draggingItem: null, // {s, i}
        dragOverItem: null, // {s, i}

        COLORS: [
            { header: '#2563EB', light: '#EFF6FF', border: '#BFDBFE' },
            { header: '#059669', light: '#ECFDF5', border: '#A7F3D0' },
            { header: '#7C3AED', light: '#F5F3FF', border: '#DDD6FE' },
            { header: '#EA580C', light: '#FFF7ED', border: '#FED7AA' },
            { header: '#DC2626', light: '#FEF2F2', border: '#FECACA' },
        ],

        init() {
            this.treeData = JSON.parse(JSON.stringify(this.originalData || {}));
            this.$nextTick(() => lucide.createIcons());
            this.$watch('isEditMode', () => this.$nextTick(() => lucide.createIcons()));
            this.$watch('treeData', () => this.$nextTick(() => lucide.createIcons()), { deep: true });
        },

        color(idx) {
            return this.COLORS[idx % this.COLORS.length];
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        askConfirm(title, message, opts = {}) {
            this.confirmModal = {
                open: true,
                title,
                message,
                confirmText: opts.confirmText || 'Xác nhận',
                danger: !!opts.danger,
            };
            return new Promise(resolve => { this._confirmResolve = resolve; });
        },

        resolveConfirm(result) {
            this.confirmModal.open = false;
            if (this._confirmResolve) {
                this._confirmResolve(result);
                this._confirmResolve = null;
            }
        },

        // ── Edit mode ──────────────────────────────────────────────────────
        toggleEditMode() {
            this.isEditMode = true;
        },

        async cancelEdit() {
            const ok = await this.askConfirm('Hủy thay đổi', 'Các chỉnh sửa chưa lưu sẽ bị mất. Bạn có chắc chắn?', { confirmText: 'Hủy thay đổi', danger: true });
            if (!ok) return;
            this.treeData = JSON.parse(JSON.stringify(this.originalData));
            this.isEditMode = false;
        },

        // ── Mutations ──────────────────────────────────────────────────────
        addSection() {
            if (!this.treeData.cac_chu_de_lon) this.treeData.cac_chu_de_lon = [];
            this.treeData.cac_chu_de_lon.push({ ten: 'Chủ đề mới', mo_ta: '', cac_chu_de_con: [] });
        },

        async removeSection(idx) {
            const ok = await this.askConfirm('Xoá chủ đề lớn', 'Xoá chủ đề này và toàn bộ mục con bên trong?', { confirmText: 'Xoá', danger: true });
            if (!ok) return;
            this.treeData.cac_chu_de_lon.splice(idx, 1);
        },

        addItem(sIdx) {
            if (!this.treeData.cac_chu_de_lon[sIdx].cac_chu_de_con) {
                this.treeData.cac_chu_de_lon[sIdx].cac_chu_de_con = [];
            }
            this.treeData.cac_chu_de_lon[sIdx].cac_chu_de_con.push({ ten: 'Mục mới', noi_dung: '', cong_thuc: '', vi_du: '' });
        },

        removeItem(sIdx, iIdx) {
            this.treeData.cac_chu_de_lon[sIdx].cac_chu_de_con.splice(iIdx, 1);
        },

        // ── Reorder: chủ đề lớn ──────────────────────────────────────────────
        sectionDragStart(e, idx) {
            if (!this.isEditMode) {
                // Chế độ xem: kéo cả chủ đề lớn thả vào chatbot như trước
                const lon = this.treeData.cac_chu_de_lon[idx];
                const items = (lon.cac_chu_de_con || []).map(it => ({
                    title: it.ten, content: it.noi_dung || '', formula: it.cong_thuc || '', example: it.vi_du || '',
                }));
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('application/json', JSON.stringify({
                    level: 2, title: lon.ten, description: lon.mo_ta || '', items,
                }));
                return;
            }
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(idx));
            this.draggingSection = idx;
        },

        sectionDragOver(e, idx) {
            if (!this.isEditMode || this.draggingSection === null) return;
            this.dragOverSection = idx;
        },

        sectionDrop(e, idx) {
            if (!this.isEditMode || this.draggingSection === null) return;
            const from = this.draggingSection;
            const to = idx;
            if (from === to) { this.sectionDragEnd(); return; }

            const arr = this.treeData.cac_chu_de_lon;
            const [moved] = arr.splice(from, 1);
            arr.splice(to, 0, moved);
            this.sectionDragEnd();
        },

        sectionDragEnd() {
            this.draggingSection = null;
            this.dragOverSection = null;
        },

        // ── Reorder: chủ đề con (chỉ trong cùng 1 chủ đề lớn) ─────────────────
        itemDragStart(e, sIdx, iIdx, lon, item) {
            if (!this.isEditMode) {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('application/json', JSON.stringify({
                    level: 3, title: item.ten, content: item.noi_dung || '',
                    formula: item.cong_thuc || '', example: item.vi_du || '',
                    section: lon.ten, subsection: null,
                }));
                return;
            }
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', `${sIdx}:${iIdx}`);
            this.draggingItem = { s: sIdx, i: iIdx };
        },

        itemDragOver(e, sIdx, iIdx) {
            if (!this.isEditMode || !this.draggingItem) return;
            if (this.draggingItem.s !== sIdx) return;
            this.dragOverItem = { s: sIdx, i: iIdx };
        },

        itemDrop(e, sIdx, iIdx) {
            if (!this.isEditMode || !this.draggingItem) return;
            if (this.draggingItem.s !== sIdx) {
                this.showToast('Chỉ có thể sắp xếp mục con trong cùng một chủ đề lớn.', 'error');
                this.itemDragEnd();
                return;
            }
            const from = this.draggingItem.i;
            const to = iIdx;
            if (from === to) { this.itemDragEnd(); return; }

            const arr = this.treeData.cac_chu_de_lon[sIdx].cac_chu_de_con;
            const [moved] = arr.splice(from, 1);
            arr.splice(to, 0, moved);
            this.itemDragEnd();
        },

        itemDragEnd() {
            this.draggingItem = null;
            this.dragOverItem = null;
        },

        // ── Save ───────────────────────────────────────────────────────────
        async saveEdits() {
            if (!this.treeData.cac_chu_de_lon || this.treeData.cac_chu_de_lon.length === 0) {
                this.showToast('Cần có ít nhất 1 chủ đề lớn.', 'error');
                return;
            }
            if (!this.treeData.ten_chuyen_de || !this.treeData.ten_chuyen_de.trim()) {
                this.showToast('Tên lộ trình không được để trống.', 'error');
                return;
            }

            this.saving = true;
            try {
                const response = await fetch(`/user/knowledge/${this.knowledgeId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        title: this.treeData.ten_chuyen_de,
                        knowledge_tree: this.treeData,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Lỗi khi lưu thay đổi.');
                }

                this.originalData = JSON.parse(JSON.stringify(this.treeData));
                this.isEditMode = false;
                this.showToast(data.message || 'Đã lưu thay đổi!', 'success');

            } catch (error) {
                console.error(error);
                this.showToast('Lỗi: ' + (error.message || error), 'error');
            } finally {
                this.saving = false;
            }
        },

        async publishRoadmap() {
            const ok = await this.askConfirm('Xuất bản lộ trình', 'Bạn có chắc chắn muốn xuất bản lộ trình này?');
            if (!ok) return;
            this.showToast('Tính năng này sẽ được triển khai sớm', 'success');
        },
    };
}
</script>
@endsection