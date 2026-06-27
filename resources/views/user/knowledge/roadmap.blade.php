@extends('layouts.app')
@section('title', 'Tạo lộ trình - EduNova')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-white py-12">
    <div class="max-w-4xl mx-auto px-4 space-y-6">

        {{-- ── BƯỚC 1: FORM NHẬP CHỦ ĐỀ ── --}}
        <div id="initialForm" class="space-y-6">
            <div class="text-center space-y-3 mb-8">
                <h1 class="text-4xl font-black text-slate-900">Bạn muốn học gì?</h1>
                <p class="text-slate-600 max-w-lg mx-auto">Nhập chủ đề để AI tạo cây kiến thức học tập cho bạn</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 space-y-6">
                {{-- Topic --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-900">Chủ đề học tập</label>
                    <input type="text" id="topicInput"
                        placeholder="VD: React, Machine Learning, Ngữ pháp TOEIC..."
                        class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900 text-base">
                </div>

                {{-- Format --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-900">Chọn định dạng</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="format" value="course" class="w-4 h-4">
                            <div><div class="font-bold text-slate-900">📚 Khóa học</div><div class="text-xs text-slate-600">Chi tiết từng buổi học</div></div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="format" value="guide" class="w-4 h-4">
                            <div><div class="font-bold text-slate-900">📖 Hướng dẫn</div><div class="text-xs text-slate-600">Tóm tắt hành động từng bước</div></div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border-2 border-slate-900 rounded-xl cursor-pointer bg-slate-50">
                            <input type="radio" name="format" value="roadmap" checked class="w-4 h-4">
                            <div><div class="font-bold text-slate-900">🗺️ Lộ trình</div><div class="text-xs text-slate-600">Sơ đồ cây chủ đề học tập</div></div>
                        </label>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="button" id="generateBtn"
                    class="w-full py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5"></i> Tạo lộ trình
                </button>
            </div>
        </div>

        {{-- ── LOADING ── --}}
        <div id="loadingSection" class="hidden">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-16 flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-4 border-slate-900 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-slate-600 font-semibold">AI đang tạo cây kiến thức cho bạn...</p>
            </div>
        </div>

        {{-- ── BƯỚC 2: CÂY KIẾN THỨC ── --}}
        <div id="knowledgeTreeSection" class="hidden space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 id="treeTitle" class="text-3xl font-black text-slate-900"></h2>
                    <p id="treeDesc" class="text-slate-600 text-sm mt-1"></p>
                </div>
                <button id="restartBtn" type="button"
                    class="text-sm text-slate-600 hover:text-slate-900 flex items-center gap-1 border border-slate-300 rounded-lg px-3 py-2">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Bắt đầu lại
                </button>
            </div>

            {{-- Tree Container --}}
            <div id="treeContainer" class="space-y-6">
                {{-- Rendered by JS --}}
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="button" id="saveRoadmapBtn"
                    class="flex-1 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition-all shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i> Lưu lộ trình
                </button>
                <button type="button" id="editRoadmapBtn"
                    class="flex-1 py-3 border border-slate-300 text-slate-700 rounded-xl font-semibold hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="edit" class="w-5 h-5"></i> Chỉnh sửa
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    // ── Elements ──
    const topicInput          = document.getElementById('topicInput');
    const generateBtn         = document.getElementById('generateBtn');
    const initialForm         = document.getElementById('initialForm');
    const loadingSection      = document.getElementById('loadingSection');
    const knowledgeTreeSection= document.getElementById('knowledgeTreeSection');
    const treeContainer       = document.getElementById('treeContainer');
    const treeTitle           = document.getElementById('treeTitle');
    const treeDesc            = document.getElementById('treeDesc');
    const saveRoadmapBtn      = document.getElementById('saveRoadmapBtn');
    const restartBtn          = document.getElementById('restartBtn');

    let currentTopic        = '';
    let currentKnowledgeTree = null;

    // ── Generate ──
    generateBtn.addEventListener('click', async () => {
    currentTopic = topicInput.value.trim();
    if (!currentTopic) {
        showToast('Vui lòng nhập chủ đề', 'error');
        topicInput.focus();
        return;
    }

    showLoading(true);
    console.log('DEBUG: Đang gửi yêu cầu tạo kiến thức với chủ đề:', currentTopic);

    try {
        const response = await fetch('/api/knowledge/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                       'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ topic: currentTopic })
        });

        // Log trạng thái HTTP nhận được
        console.log('DEBUG: HTTP Status:', response.status, response.statusText);

        // 1. Kiểm tra lỗi HTTP (404, 500, 422...)
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error('DEBUG: Server trả về lỗi HTTP:', errorData);
            throw new Error(errorData.message || `Lỗi server: ${response.status} ${response.statusText}`);
        }

        // 2. Chuyển đổi JSON
        const data = await response.json();
        console.log('DEBUG: Dữ liệu nhận được từ server:', data);

        // 3. Kiểm tra logic nghiệp vụ
        if (!data.success) {
            console.warn('DEBUG: Server trả về success: false. Message:', data.message);
            throw new Error(data.message || 'Lỗi không xác định từ server');
        }

        currentKnowledgeTree = data.knowledge_tree;
        showTree(currentKnowledgeTree);
        console.log('DEBUG: Cây kiến thức đã được cập nhật thành công');

    } catch (error) {
        console.error('DEBUG: Catch block - Lỗi xảy ra:', error);
        showToast('Lỗi: ' + (error.message || error), 'error');
        showLoading(false);
    }
});

    // Enter key
    topicInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') generateBtn.click();
    });

    // ── Restart ──
    restartBtn.addEventListener('click', () => {
        knowledgeTreeSection.classList.add('hidden');
        initialForm.classList.remove('hidden');
        topicInput.value = '';
        topicInput.focus();
        currentKnowledgeTree = null;
        treeContainer.innerHTML = '';
    });

    // ── Save ──
    saveRoadmapBtn.addEventListener('click', async () => {
        try {
            saveRoadmapBtn.disabled = true;
            saveRoadmapBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Đang lưu...';
            lucide.createIcons();

            const format = document.querySelector('input[name="format"]:checked').value;

            const response = await fetch('{{ route("user.knowledge.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    topic: currentTopic,
                    format: format,
                    knowledge_tree: currentKnowledgeTree
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Lỗi khi lưu');
            }

            window.location.href = '/user/knowledge/' + data.knowledge_id;

        } catch (error) {
            console.error(error);
            showToast('Lỗi: ' + (error.message || error), 'error');
        } finally {
            saveRoadmapBtn.disabled = false;
            saveRoadmapBtn.innerHTML = '<i data-lucide="save" class="w-5 h-5"></i> Lưu lộ trình';
            lucide.createIcons();
        }
    });

    // ── UI helpers ──
    function showLoading(on) {
        initialForm.classList.toggle('hidden', on);
        loadingSection.classList.toggle('hidden', !on);
        knowledgeTreeSection.classList.add('hidden');
    }

    function showTree(tree) {
        loadingSection.classList.add('hidden');
        knowledgeTreeSection.classList.remove('hidden');

        treeTitle.textContent = tree.ten_chuyen_de || '';
        treeDesc.textContent  = tree.mo_ta || '';

        renderTree(tree);
        lucide.createIcons();
    }

    // ── Render cây 2 cấp ──
    function renderTree(tree) {
        const chuDeLon = tree.cac_chu_de_lon || [];
        treeContainer.innerHTML = '';

        // Node gốc
        const rootEl = document.createElement('div');
        rootEl.className = 'flex justify-center mb-2';
        rootEl.innerHTML = `
            <div class="bg-yellow-400 border-4 border-yellow-600 rounded-2xl px-8 py-4 text-center shadow-lg max-w-xs">
                <h3 class="font-black text-lg text-slate-900">${escapeHtml(tree.ten_chuyen_de)}</h3>
                ${tree.mo_ta ? `<p class="text-xs text-slate-700 mt-1">${escapeHtml(tree.mo_ta)}</p>` : ''}
            </div>
        `;
        treeContainer.appendChild(rootEl);

        // Từng chủ đề lớn
        chuDeLon.forEach((lon, idx) => {
            const colors = [
                { header: 'bg-blue-600',   card: 'bg-blue-50 border-blue-200' },
                { header: 'bg-emerald-600', card: 'bg-emerald-50 border-emerald-200' },
                { header: 'bg-violet-600',  card: 'bg-violet-50 border-violet-200' },
                { header: 'bg-orange-500',  card: 'bg-orange-50 border-orange-200' },
                { header: 'bg-rose-600',    card: 'bg-rose-50 border-rose-200' },
            ];
            const c = colors[idx % colors.length];

            const section = document.createElement('div');
            section.className = 'rounded-2xl border-2 ' + c.card + ' overflow-hidden shadow-md';

            // Header chủ đề lớn
            section.innerHTML = `
                <div class="${c.header} px-6 py-4">
                    <h4 class="font-black text-white text-lg">${escapeHtml(lon.ten)}</h4>
                    ${lon.mo_ta ? `<p class="text-white/80 text-xs mt-1">${escapeHtml(lon.mo_ta)}</p>` : ''}
                </div>
            `;

            // Grid chủ đề con
            const con = lon.cac_chu_de_con || [];
            if (con.length > 0) {
                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4';

                con.forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md transition-all space-y-2';
                    card.innerHTML = `
                        <h5 class="font-bold text-slate-900 text-sm">${escapeHtml(item.ten)}</h5>
                        ${item.noi_dung ? `<p class="text-xs text-slate-600 leading-relaxed">${escapeHtml(item.noi_dung)}</p>` : ''}
                        ${item.cong_thuc ? `
                            <div class="bg-slate-900 rounded-lg px-3 py-2">
                                <code class="text-xs text-yellow-300 font-mono">${escapeHtml(item.cong_thuc)}</code>
                            </div>` : ''}
                        ${item.vi_du ? `
                            <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Ví dụ</span>
                                <p class="text-xs text-slate-700 italic mt-1">${escapeHtml(item.vi_du)}</p>
                            </div>` : ''}
                    `;
                    grid.appendChild(card);
                });

                section.appendChild(grid);
            }

            treeContainer.appendChild(section);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
@endsection