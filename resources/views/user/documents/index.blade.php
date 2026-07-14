@extends('layouts.app')
@section('title', 'Thư viện tài liệu - EduNova')

@section('content')
<div class="space-y-6" x-data="documentLibrary()">

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Thư viện tài liệu</h1>
            <p class="text-slate-500 text-sm mt-1">Tra cứu, chia sẻ và đánh giá tài liệu học tập</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openUpload = true"
                class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 text-white rounded-xl font-semibold text-sm
                       hover:bg-slate-700 transition-all active:scale-95 shadow-lg">
                <i data-lucide="upload" class="w-4 h-4"></i> Upload tài liệu
            </button>
        </div>
    </div>

    {{-- ── STATS BAR ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">248</p>
                <p class="text-xs text-slate-500">Tài liệu</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <i data-lucide="download" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">1.2K</p>
                <p class="text-xs text-slate-500">Lượt tải</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center shrink-0">
                <i data-lucide="bookmark" class="w-5 h-5 text-yellow-600"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900" x-text="savedList.length">0</p>
                <p class="text-xs text-slate-500">Đã lưu</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                <i data-lucide="star" class="w-5 h-5 text-rose-500"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-900">4.7</p>
                <p class="text-xs text-slate-500">Đánh giá TB</p>
            </div>
        </div>
    </div>

    {{-- ── SEARCH + FILTER BAR ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-3">
        {{-- Search --}}
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
            <input type="text" x-model="searchQuery" @input="filterDocs()"
                placeholder="Tìm tài liệu theo tên, tác giả, môn học..."
                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800
                       focus:outline-none focus:ring-2 focus:ring-slate-900 transition-all">
        </div>
        {{-- Category filter --}}
        <select x-model="filterCategory" @change="filterDocs()"
            class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700
                   focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white min-w-[160px]">
            <option value="">Tất cả danh mục</option>
            <option value="math">Toán học</option>
            <option value="english">Tiếng Anh</option>
            <option value="programming">Lập trình</option>
            <option value="science">Khoa học</option>
            <option value="business">Kinh doanh</option>
        </select>
        {{-- Type filter --}}
        <select x-model="filterType" @change="filterDocs()"
            class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700
                   focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white min-w-[140px]">
            <option value="">Tất cả loại</option>
            <option value="pdf">PDF</option>
            <option value="doc">Word</option>
            <option value="ppt">PowerPoint</option>
            <option value="video">Video</option>
        </select>
        {{-- Sort --}}
        <select x-model="sortBy" @change="filterDocs()"
            class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-700
                   focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white min-w-[140px]">
            <option value="newest">Mới nhất</option>
            <option value="popular">Phổ biến nhất</option>
            <option value="rating">Đánh giá cao</option>
            <option value="az">A → Z</option>
        </select>
        {{-- View toggle --}}
        <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
            <button @click="viewMode = 'grid'"
                :class="viewMode === 'grid' ? 'bg-white shadow text-slate-900' : 'text-slate-500'"
                class="p-2 rounded-lg transition-all">
                <i data-lucide="layout-grid" class="w-4 h-4"></i>
            </button>
            <button @click="viewMode = 'list'"
                :class="viewMode === 'list' ? 'bg-white shadow text-slate-900' : 'text-slate-500'"
                class="p-2 rounded-lg transition-all">
                <i data-lucide="list" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    {{-- ── TABS ── --}}
    <div class="flex items-center gap-1 border-b border-slate-200">
        <template x-for="tab in tabs" :key="tab.key">
            <button @click="activeTab = tab.key; filterDocs()"
                :class="activeTab === tab.key
                    ? 'border-b-2 border-slate-900 text-slate-900 font-bold'
                    : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2.5 text-sm transition-all -mb-px"
                x-text="tab.label">
            </button>
        </template>
    </div>

    {{-- ── DOCUMENT GRID ── --}}
    <div x-show="filteredDocs.length > 0">
        {{-- Grid View --}}
        <div x-show="viewMode === 'grid'"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="doc in filteredDocs" :key="doc.id">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 group">
                    {{-- Thumbnail --}}
                    <div class="relative h-36 flex items-center justify-center"
                        :style="`background: ${doc.color}15`">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-md"
                            :style="`background: ${doc.color}`">
                            <i :data-lucide="doc.icon" class="w-8 h-8 text-white"></i>
                        </div>
                        {{-- Badge type --}}
                        <span class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                            :style="`background:${doc.color}20;color:${doc.color}`"
                            x-text="doc.type">
                        </span>
                        {{-- Save button --}}
                        <button @click.stop="toggleSave(doc)"
                            class="absolute top-3 right-3 w-7 h-7 rounded-full flex items-center justify-center transition-all"
                            :class="savedList.includes(doc.id)
                                ? 'bg-yellow-400 text-slate-900'
                                : 'bg-white/80 text-slate-500 opacity-0 group-hover:opacity-100'">
                            <i data-lucide="bookmark" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="p-4 space-y-2">
                        <h3 class="font-bold text-slate-900 text-sm leading-tight line-clamp-2" x-text="doc.title"></h3>
                        <p class="text-xs text-slate-500" x-text="doc.author"></p>

                        <div x-show="activeTab === 'my'" class="mt-2">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                :class="getStatusClass(doc.status)"
                                x-text="statusText(doc.status)">
                            </span>
                        </div>

                        {{-- Star rating --}}
                        <div class="flex items-center gap-1.5">
                            <div class="flex gap-0.5">
                                <template x-for="s in 5" :key="s">
                                    <i data-lucide="star"
                                        :class="s <= Math.round(doc.rating) ? 'text-yellow-400 fill-yellow-400' : 'text-slate-200 fill-slate-200'"
                                        class="w-3 h-3"></i>
                                </template>
                            </div>
                            <span class="text-xs text-slate-500" x-text="`${doc.rating} (${doc.reviews})`"></span>
                        </div>

                        {{-- Meta --}}
                        <div class="flex items-center justify-between text-xs text-slate-400 pt-1 border-t border-slate-100">
                            <span x-text="`${doc.downloads} lượt tải`"></span>
                            <span x-text="doc.size"></span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-1">
                            <button @click="openDetailModal(doc)"
                                class="flex-1 py-2 text-xs font-semibold text-slate-700 border border-slate-200
                                       rounded-lg hover:bg-slate-50 transition-all">
                                Xem chi tiết
                            </button>
                            <!-- <button @click="openView(doc)"
                                class="flex-1 py-2 text-xs font-semibold text-slate-700 border border-slate-200
                                       rounded-lg hover:bg-slate-50 transition-all">
                                Xem tài liệu
                            </button>
                            <button @click="downloadDoc(doc)"
                                class="flex-1 py-2 text-xs font-semibold text-white bg-slate-900
                                       rounded-lg hover:bg-slate-700 transition-all flex items-center justify-center gap-1">
                                <i data-lucide="download" class="w-3 h-3"></i> Tải về
                            </button> -->
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- List View --}}
        <div x-show="viewMode === 'list'" class="space-y-2">
            <template x-for="doc in filteredDocs" :key="doc.id">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4
                            hover:shadow-md transition-all group">
                    {{-- Icon --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                        :style="`background: ${doc.color}`">
                        <i :data-lucide="doc.icon" class="w-6 h-6 text-white"></i>
                    </div>
                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-slate-900 text-sm truncate" x-text="doc.title"></h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
                                :style="`background:${doc.color}20;color:${doc.color}`"
                                x-text="doc.type"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="`${doc.author} · ${doc.category}`"></p>
                        <div x-show="activeTab === 'my'" class="mt-1">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                :class="getStatusClass(doc.status)"
                                x-text="statusText(doc.status)">
                            </span>
                        </div>
                        <div class="flex items-center gap-3 mt-1.5">
                            <div class="flex gap-0.5">
                                <template x-for="s in 5" :key="s">
                                    <i data-lucide="star"
                                        :class="s <= Math.round(doc.rating) ? 'text-yellow-400 fill-yellow-400' : 'text-slate-200 fill-slate-200'"
                                        class="w-3 h-3"></i>
                                </template>
                            </div>
                            <span class="text-xs text-slate-400" x-text="`${doc.downloads} lượt tải`"></span>
                            <span class="text-xs text-slate-400" x-text="doc.size"></span>
                        </div>
                    </div>
                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="toggleSave(doc)"
                            :class="savedList.includes(doc.id) ? 'text-yellow-500' : 'text-slate-400'"
                            class="p-2 rounded-lg hover:bg-slate-100 transition-all">
                            <i data-lucide="bookmark" class="w-4 h-4"></i>
                        </button>
                        <button @click="openDetailModal(doc)"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200
                                   rounded-lg hover:bg-slate-50 transition-all">
                            Chi tiết
                        </button>
                        <!-- <button @click="openView(doc)"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200
                                   rounded-lg hover:bg-slate-50 transition-all">
                            Xem
                        </button>
                        <button @click="downloadDoc(doc)"
                            class="px-3 py-1.5 text-xs font-semibold text-white bg-slate-900
                                   rounded-lg hover:bg-slate-700 transition-all flex items-center gap-1">
                            <i data-lucide="download" class="w-3 h-3"></i> Tải
                        </button> -->
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredDocs.length === 0"
        class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="file-search" class="w-8 h-8 text-slate-400"></i>
        </div>
        <p class="font-bold text-slate-700">Không tìm thấy tài liệu</p>
        <p class="text-sm text-slate-500 mt-1">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
    </div>

    {{-- ═══════════════════════════════
         MODAL: UPLOAD TÀI LIỆU
    ═══════════════════════════════ --}}
    <div x-show="openUpload" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openUpload = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">Upload tài liệu mới</h2>
                <button @click="openUpload = false" class="p-2 rounded-xl hover:bg-slate-100 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            {{-- Drop zone --}}
            <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center
                        hover:border-slate-900 hover:bg-slate-50 transition-all cursor-pointer"
                @dragover.prevent @drop.prevent="handleFileDrop($event)"
                @click="$refs.fileInput.click()">
                <div x-show="!uploadFile">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="upload-cloud" class="w-7 h-7 text-slate-400"></i>
                    </div>
                    <p class="font-semibold text-slate-700 text-sm">Kéo thả file vào đây</p>
                    <p class="text-xs text-slate-400 mt-1">hoặc click để chọn file</p>
                    <p class="text-xs text-slate-400 mt-2">PDF, DOC, PPT, MP4 · Tối đa 50MB</p>
                </div>
                <div x-show="uploadFile" class="flex items-center gap-3 justify-center">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <i data-lucide="file-check" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold text-slate-800 text-sm" x-text="uploadFile?.name"></p>
                        <p class="text-xs text-slate-400" x-text="uploadFile ? formatFileSize(uploadFile.size) : ''"></p>
                    </div>
                    <button @click.stop="uploadFile = null" class="p-1 rounded-lg hover:bg-slate-100">
                        <i data-lucide="x" class="w-4 h-4 text-slate-400"></i>
                    </button>
                </div>
                <input type="file" x-ref="fileInput" class="hidden"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4"
                    @change="uploadFile = $event.target.files[0]">
            </div>

            {{-- Form fields --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tên tài liệu <span class="text-red-500">*</span></label>
                    <input type="text" x-model="uploadForm.title" placeholder="VD: Giáo trình Toán cao cấp A1"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Danh mục</label>
                        <select x-model="uploadForm.category"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white">
                            <option value="">Chọn danh mục</option>
                            <option value="math">Toán học</option>
                            <option value="english">Tiếng Anh</option>
                            <option value="programming">Lập trình</option>
                            <option value="science">Khoa học</option>
                            <option value="business">Kinh doanh</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tác giả</label>
                        <input type="text" x-model="uploadForm.author" placeholder="Tên tác giả"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả</label>
                    <textarea x-model="uploadForm.description" rows="3"
                        placeholder="Mô tả ngắn về nội dung tài liệu..."
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 resize-none">
                    </textarea>
                </div>
            </div>

            {{-- Upload progress --}}
            <div x-show="uploading" class="space-y-2">
                <div class="flex justify-between text-xs text-slate-600">
                    <span>Đang tải lên...</span>
                    <span x-text="`${uploadProgress}%`"></span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-slate-900 rounded-full transition-all duration-300"
                        :style="`width: ${uploadProgress}%`"></div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3">
                <button @click="openUpload = false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Hủy
                </button>
                <button @click="submitUpload()"
                    :disabled="uploading || !uploadForm.title || !uploadFile"
                    class="flex-1 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold
                           hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    <span x-show="!uploading">Upload tài liệu</span>
                    <span x-show="uploading">Đang xử lý...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════
         MODAL: CHI TIẾT TÀI LIỆU
    ═══════════════════════════════ --}}
    <div x-show="openDetail && selectedDoc" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openDetail = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="sticky top-0 bg-white border-b border-slate-100 px-8 py-5 flex items-center justify-between z-10 rounded-t-3xl">
                <h2 class="font-black text-slate-900 text-lg" x-text="selectedDoc?.title"></h2>
                <button @click="openDetail = false" class="p-2 rounded-xl hover:bg-slate-100 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-slate-500"></i>
                </button>
            </div>

            <div class="p-8 space-y-6">
                {{-- Doc preview banner --}}
                <div class="rounded-2xl h-40 flex items-center justify-center"
                    :style="`background: ${selectedDoc?.color}15`">
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto shadow-xl"
                            :style="`background: ${selectedDoc?.color}`">
                            <i :data-lucide="selectedDoc?.icon" class="w-10 h-10 text-white"></i>
                        </div>
                        <span class="mt-3 inline-block px-3 py-1 rounded-full text-xs font-bold"
                            :style="`background:${selectedDoc?.color}20;color:${selectedDoc?.color}`"
                            x-text="selectedDoc?.type"></span>
                    </div>
                </div>

                {{-- Meta info --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.downloads"></p>
                        <p class="text-xs text-slate-500">Lượt tải</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.reviews"></p>
                        <p class="text-xs text-slate-500">Đánh giá</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.rating"></p>
                        <p class="text-xs text-slate-500">Điểm TB</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-lg font-black text-slate-900" x-text="selectedDoc?.size"></p>
                        <p class="text-xs text-slate-500">Kích thước</p>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <h3 class="font-bold text-slate-900 mb-2 text-sm">Mô tả</h3>
                    <p class="text-sm text-slate-600 leading-relaxed" x-text="selectedDoc?.description"></p>
                </div>

                {{-- Tags --}}
                <div>
                    <h3 class="font-bold text-slate-900 mb-2 text-sm">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="tag in (selectedDoc?.tags || [])" :key="tag">
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-medium"
                                x-text="tag"></span>
                        </template>
                    </div>
                </div>

                {{-- RATING SECTION --}}
                <div class="border-t border-slate-100 pt-6">
                    <h3 class="font-bold text-slate-900 mb-4 text-sm">Đánh giá tài liệu</h3>

                    {{-- Star selector --}}
                    <div class="flex items-center gap-2 mb-4">
                        <p class="text-sm text-slate-600 mr-1">Điểm của bạn:</p>
                        <div class="flex gap-1">
                            <template x-for="s in 5" :key="s">
                                <button @click="userRating = s; hoverRating = s"
                                    @mouseenter="hoverRating = s"
                                    @mouseleave="hoverRating = userRating"
                                    class="transition-transform hover:scale-125 active:scale-90">
                                    <i data-lucide="star"
                                        :class="s <= (hoverRating || userRating) ? 'text-yellow-400 fill-yellow-400' : 'text-slate-200 fill-slate-200'"
                                        class="w-7 h-7 transition-colors"></i>
                                </button>
                            </template>
                        </div>
                        <span class="text-sm font-bold text-slate-700 ml-1"
                            x-text="['', 'Rất tệ', 'Tệ', 'Bình thường', 'Tốt', 'Xuất sắc'][userRating] || 'Chọn sao'">
                        </span>
                    </div>

                    {{-- Comment --}}
                    <textarea x-model="userComment" rows="3"
                        placeholder="Nhận xét về tài liệu này..."
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 resize-none mb-3">
                    </textarea>

                    <button @click="submitRating()"
                        :disabled="!userRating"
                        class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold
                               hover:bg-slate-700 disabled:opacity-40 transition-all">
                        Gửi đánh giá
                    </button>

                    {{-- Existing reviews --}}
                    <div class="mt-5 space-y-3" x-show="(selectedDoc?.reviewList || []).length > 0">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wide">Nhận xét gần đây</h4>
                        <template x-for="review in (selectedDoc?.reviewList || [])" :key="review.id">
                            <div class="bg-slate-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-300 flex items-center justify-center text-xs font-bold text-slate-700"
                                            x-text="review.user[0]"></div>
                                        <span class="text-sm font-semibold text-slate-800" x-text="review.user"></span>
                                    </div>
                                    <div class="flex gap-0.5">
                                        <template x-for="s in 5" :key="s">
                                            <i data-lucide="star"
                                                :class="s <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-slate-200 fill-slate-200'"
                                                class="w-3 h-3"></i>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-600" x-text="review.comment"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex gap-3 sticky bottom-0 bg-white pt-4 pb-2 -mx-8 px-8 border-t border-slate-100">
                    <button @click="toggleSave(selectedDoc)"
                        :class="savedList.includes(selectedDoc?.id)
                            ? 'bg-yellow-50 border-yellow-300 text-yellow-700'
                            : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                        class="flex-1 py-3 border rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4"></i>
                        <span x-text="savedList.includes(selectedDoc?.id) ? 'Đã lưu' : 'Lưu tài liệu'"></span>
                    </button>
                    <button @click="openView(selectedDoc)"
                        class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-sm font-semibold
                               hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4"></i> xem tài liệu
                    </button>
                    <button @click="downloadDoc(selectedDoc)"
                        class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-sm font-semibold
                               hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i> tải về
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast notification --}}
    <div x-show="toast.show" 
     x-transition
     class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[60] px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold text-white flex items-center gap-2"
     :class="{
         'bg-emerald-600': toast.type === 'success',
         'bg-amber-500':   toast.type === 'warning',
         'bg-rose-600':    toast.type === 'error'
     }"
     style="display:none;">
     
    <i x-show="toast.type === 'success'" data-lucide="check-circle" class="w-4 h-4"></i>
    <i x-show="toast.type === 'warning'" data-lucide="alert-triangle" class="w-4 h-4"></i>
    <i x-show="toast.type === 'error'"   data-lucide="x-circle" class="w-4 h-4"></i>
    
    <span x-text="toast.message"></span>
</div>

</div>


@php
    $mapDoc = fn($doc) => [
        'id'          => $doc->id,
        'title'       => $doc->name,
        'author'      => $doc->author ?? 'Người dùng',
        'type'        => $doc->url ? strtoupper(pathinfo($doc->url, PATHINFO_EXTENSION) ?: 'FILE') : 'UNKNOWN',
        'category'    => $doc->category ?? '',
        'size'        => $doc->size,
        'downloads'   => $doc->downloads,
        'rating'      => $doc->rate,
        'reviews'     => $doc->reviews instanceof \Illuminate\Support\Collection
                            ? $doc->reviews->count()
                            : (is_int($doc->reviews) ? $doc->reviews : 0),
        'view_url'    => $doc->view_url ?? '',
        'color'       => $doc->color,
        'icon'        => $doc->icon,
        'description' => $doc->description,
        'tags'        => $doc->tags->pluck('name') ?? [],
        'types'       => $doc->types->pluck('name') ?? [],
        'status'      => $doc->status,
        // ── MỚI: map reviewList từ DB ──────────────────────────────────────
        'reviewList'  => $doc->relationLoaded('reviews')
            ? $doc->reviews->map(fn($r) => [
                'id'      => $r->id,
                'user'    => $r->user?->name ?? 'Ẩn danh',
                'rating'  => $r->rating,
                'comment' => $r->comment ?? 'Không có nhận xét.',
              ])->values()->toArray()
            : [],
        // ── MỚI: rating của user hiện tại (nếu đã đánh giá) ───────────────
        'myRating'    => $doc->relationLoaded('reviews')
            ? ($doc->reviews->firstWhere('user_id', auth()->id())?->rating ?? 0)
            : 0,
    ];
      $libraryJson = json_encode([
        'all'   => $documents->map($mapDoc)->values(),
        'saved' => $savedDocuments->map($mapDoc)->values(),
        'my'    => $myDocuments->map($mapDoc)->values(),
    ]);
@endphp

@push('scripts')
<script>
function documentLibrary() {
    const library = {!! $libraryJson !!};
    return {
        // State
        viewMode: 'grid',
        searchQuery: '',
        filterCategory: '',
        filterType: '',
        sortBy: 'newest',
        activeTab: 'all',
        openUpload: false,
        openDetail: false,  
        selectedDoc: null,
        savedList: library.saved.map(d => d.id),
        myList: library.my,
        userRating: 0,
        hoverRating: 0,
        userComment: '',
        uploading: false,
        uploadProgress: 0,
        uploadFile: null,
        uploadForm: { title: '', category: '', author: '', description: '' },
        toast: { show: false, message: '', type: 'success' },

        tabs: [
            { key: 'all',    label: 'Tất cả' },
            { key: 'saved',  label: 'Đã lưu' },
            { key: 'my',     label: 'Của tôi' },
            { key: 'recent', label: 'Gần đây' },
        ],

        allDocs: library.all,

        filteredDocs: [],

    init() {
        // 1. Log kiểm tra dữ liệu gốc trước khi gán
        console.log('Dữ liệu gốc (allDocs):', this.allDocs);

        this.filteredDocs = [...this.allDocs];

        // 2. Log kiểm tra sau khi đã gán vào filteredDocs
        console.log('Dữ liệu đã lọc (filteredDocs):', this.filteredDocs);

        this.$nextTick(() => lucide.createIcons());
    },

        filterDocs() {
            let result;

            if (this.activeTab === 'saved') {
                result = [...library.saved];
            } else if (this.activeTab === 'my') {
                result = [...this.myList];  // myList chứa tất cả status
            } else if (this.activeTab === 'recent') {
                // "Gần đây" chỉ lấy approved
                result = [...this.allDocs].slice(0, 10);
            } else {
                // "Tất cả" — chỉ approved, lọc thêm phòng khi myList bị lẫn vào
                result = this.allDocs.filter(d => d.status === 'approved');
            }

            // Search
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(d =>
                    d.title.toLowerCase().includes(q) ||
                    d.author.toLowerCase().includes(q) ||
                    (d.tags || []).some(t => t.toLowerCase().includes(q))
                );
            }

            // Category
            if (this.filterCategory) result = result.filter(d => (d.tags || []).some(t => t === this.filterCategory));

            // Type
            if (this.filterType) result = result.filter(d => (d.types || []).some(t => t === this.filterType));

            // Sort
            if (this.sortBy === 'popular')     result.sort((a,b) => b.downloads - a.downloads);
            else if (this.sortBy === 'rating') result.sort((a,b) => b.rating - a.rating);
            else if (this.sortBy === 'az')     result.sort((a,b) => a.title.localeCompare(b.title));
            else result.sort((a,b) => b.id - a.id);

            this.filteredDocs = result;
            this.$nextTick(() => lucide.createIcons());
        },

        statusText(status) {
        const map = {
            'approved': 'Đã duyệt',
            'reject': 'Từ chối',
            'pending': 'Chờ xét duyệt'
        };
        return map[status] || 'Không xác định';
        },

        getStatusClass(status) {
            const classes = {
                'approved': 'bg-emerald-100 text-emerald-800',
                'reject': 'bg-rose-100 text-rose-800',
                'pending': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        
        async toggleSave(doc) {
            if (!doc) {
                console.warn('toggleSave: doc bị null');
                return;
            }

            console.log('toggleSave: Đang xử lý tài liệu ID:', doc.id);

            try {
                const idx = this.savedList.indexOf(doc.id);
                console.log('toggleSave: Vị trí trong savedList:', idx);

                if (idx === -1) {
                    console.log('toggleSave: Hành động -> LƯU (Save)');
                    const response = await axios.post('/user/documents/save', {
                        document_id: doc.id
                    });
                    console.log('toggleSave: API Save phản hồi:', response.data);
                    
                    this.savedList.push(doc.id);
                    this.showToast(response.data.message, 'success');
                } else {
                    console.log('toggleSave: Hành động -> XÓA (Unsave)');
                    const response = await axios.delete(`/user/documents/unsave/${doc.id}`);
                    console.log('toggleSave: API Unsave phản hồi:', response.data);
                    
                    this.savedList.splice(idx, 1);
                    this.showToast('Đã xóa khỏi danh sách', 'success');
                }

                if (this.activeTab === 'saved') {
                    console.log('toggleSave: Đang chạy lại filterDocs do activeTab là saved');
                    this.filterDocs();
                }
                
            } catch (error) {
                console.error('toggleSave: LỖI API', {
                    message: error.message,
                    response: error.response?.data,
                    status: error.response?.status
                });
                this.showToast(error || 'Có lỗi xảy ra', 'error');
            }
        },

        openDetailModal(doc) {
            this.selectedDoc  = doc;
            this.openDetail   = true;
            // Nếu user đã đánh giá trước đó → hiển thị lại rating cũ
            this.userRating   = doc.myRating || 0;
            this.hoverRating  = this.userRating;
            this.userComment  = '';
            this.$nextTick(() => lucide.createIcons());
        },
        openView(doc) {
            if (!doc || !doc.id) {
                console.warn('[UI] Tài liệu không khả dụng', { doc });
                this.showToast('Tài liệu không khả dụng.', 'error');
                return;
            }
            
            console.info(`[UI] Người dùng đang mở tài liệu ID: ${doc.id}`);
            window.open(`/user/documents/${doc.id}/view`, '_blank', 'noopener,noreferrer');
        },
        async downloadDoc(doc) {
            if (!doc || !doc.id) {
                this.showToast('Tài liệu không khả dụng để tải.', 'error');
                return;
            }

            try {
                this.showToast(`Đang chuẩn bị tải "${doc.title}"...`, 'warning');

                // 1. Gửi request tới Controller
                const response = await axios.get(`/user/documents/${doc.id}/download`, {
                    responseType: 'blob' // Rất quan trọng để nhận dữ liệu file
                });

                // 2. Lấy MIME type từ header, nếu không có thì mặc định là application/octet-stream
                const contentType = response.headers['content-type'] || 'application/octet-stream';
                
                // 3. Tạo Blob từ dữ liệu nhị phân
                const blob = new Blob([response.data], { type: contentType });
                const url = window.URL.createObjectURL(blob);
                
                // 4. Tạo thẻ a tạm thời để trigger tải xuống
                const link = document.createElement('a');
                link.href = url;
                
                // Lấy tên file từ header (nếu server gửi Content-Disposition) 
                // hoặc dùng tiêu đề của doc
                link.setAttribute('download', (doc.title || 'download').replace(/[^a-z0-9\.\-_ ]/gi, '_'));
                
                document.body.appendChild(link);
                link.click();
                
                // Dọn dẹp
                link.remove();
                window.URL.revokeObjectURL(url);

                // 5. Cập nhật UI sau khi tải thành công
                doc.downloads = (doc.downloads || 0) + 1;
                this.showToast(`Tải thành công "${doc.title}"`, 'success');

            } catch (error) {
                // 6. Xử lý phản hồi lỗi từ server
                if (error.response) {
                    if (error.response.status === 403) {
                        // Xử lý khi API trả về JSON: { "success": false, "message": "..." }
                        // Vì axios nhận về Blob, ta cần đọc nội dung lỗi
                        const reader = new FileReader();
                        reader.onload = () => {
                            const errorData = JSON.parse(reader.result);
                            this.showToast(errorData.message || 'Bạn đã hết lượt tải.', 'error');
                        };
                        reader.readAsText(error.response.data);
                    } else {
                        this.showToast('Đã có lỗi xảy ra khi tải file.', 'error');
                    }
                } else {
                    console.error(error);
                    this.showToast('Không thể kết nối tới máy chủ.', 'error');
                }
            }
        },
        async submitRating() {
            if (!this.userRating || !this.selectedDoc) return;
        
            try {
                const token = document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '';
        
                const response = await fetch(
                    `/user/documents/${this.selectedDoc.id}/reviews`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            rating:  this.userRating,
                            comment: this.userComment || null,
                        }),
                    }
                );
        
                const data = await response.json();
        
                if (!response.ok) {
                    this.showToast(data.message || 'Gửi đánh giá thất bại.', 'error');
                    return;
                }
        
                // Cập nhật reviewList ngay trên UI (không cần reload trang)
                if (!this.selectedDoc.reviewList) this.selectedDoc.reviewList = [];
        
                // Nếu user đã có review cũ → xóa khỏi list rồi thêm mới lên đầu
                this.selectedDoc.reviewList = this.selectedDoc.reviewList
                    .filter(r => r.user !== data.review.user);
                this.selectedDoc.reviewList.unshift(data.review);
        
                // Cập nhật rating tổng từ server
                this.selectedDoc.rating  = data.new_rating;
                this.selectedDoc.reviews = data.new_reviews;
                this.selectedDoc.myRating = data.review.rating;
        
                // Reset form
                this.userRating  = data.review.rating; // giữ lại sao đã chọn
                this.hoverRating = this.userRating;
                this.userComment = '';
        
                this.showToast(data.message, 'success');
                this.$nextTick(() => lucide.createIcons());
        
            } catch (error) {
                console.error('submitRating error:', error);
                this.showToast('Không thể kết nối máy chủ.', 'error');
            }
        },

        handleFileDrop(e) {
            const file = e.dataTransfer.files[0];
            if (file) this.uploadFile = file;
        },

        formatFileSize(bytes) {
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },

        submitUpload() {
            if (!this.uploadFile || !this.uploadForm.title) return;
            this.uploading = true;
            this.uploadProgress = 0;

            const formData = new FormData();
            formData.append('file', this.uploadFile);
            formData.append('name', this.uploadForm.title);
            formData.append('description', this.uploadForm.description || '');
            formData.append('category', this.uploadForm.category || '');
            formData.append('author', this.uploadForm.author || '');

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/user/documents/upload');
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    this.uploadProgress = Math.round((event.loaded / event.total) * 100);
                }
            };

        xhr.onload = () => {
            this.uploading = false;
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                const typeMap = { pdf: 'PDF', doc: 'DOC', docx: 'DOC', ppt: 'PPT', pptx: 'PPT', mp4: 'Video' };
                const ext = this.uploadFile.name.split('.').pop().toLowerCase();
                const newDoc = {
                    id: response.document?.id || Date.now(),
                    title: response.document?.name || this.uploadForm.title,
                    author: this.uploadForm.author || 'Bạn',
                    view_url: response.view_url || '',
                    type: typeMap[ext] || 'FILE',
                    category: response.document?.category || this.uploadForm.category || 'other',
                    size: this.formatFileSize(this.uploadFile.size),
                    downloads: 0,
                    rating: 0,
                    reviews: 0,
                    color: '#6366F1',
                    icon: 'file',
                    description: response.document?.description || this.uploadForm.description,
                    tags: [this.uploadForm.category || 'Tài liệu'],
                    status: 'pending',   // ← luôn pending khi mới upload
                    reviewList: [],
                    myRating: 0,
                };

                // ❌ Bỏ dòng này — không được xuất hiện ở tab "Tất cả"
                // this.allDocs.unshift(newDoc);

                // ✅ Chỉ thêm vào myList (tab "Của tôi")
                this.myList.unshift(newDoc);

                // Chuyển sang tab "Của tôi" để user thấy ngay
                this.activeTab = 'my';
                this.filterDocs();

                this.uploadProgress = 0;
                this.uploadFile = null;
                this.uploadForm = { title: '', category: '', author: '', description: '' };
                this.openUpload = false;
                this.showToast('Upload thành công! Tài liệu đang chờ xét duyệt.', 'success');
            } else {
                    let message = 'Upload thất bại. Vui lòng thử lại.';
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) message = errorData.message;
                        else if (errorData.error) message = errorData.error;
                    } catch (error) {
                        // ignore parse error
                    }
                    this.showToast(message, 'error');
                }
            };

            xhr.onerror = () => {
                this.uploading = false;
                this.uploadProgress = 0;
                this.showToast('Lỗi kết nối khi upload. Vui lòng thử lại.', 'error');
            };

            xhr.send(formData);
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}

// Patch openDetail to call method correctly
document.addEventListener('alpine:initialized', () => {
    lucide.createIcons();
});
</script>
@endpush
@endsection