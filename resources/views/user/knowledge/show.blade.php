@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4 sm:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto">

        {{-- Header --}}
        <div class="mb-8">
            <nav class="flex items-center gap-2 mb-4">
                <a href="{{ route('user.knowledge') }}" class="text-blue-600 hover:text-blue-700">Khóa học</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600">{{ $knowledge->title }}</span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">{{ $knowledge->title }}</h1>
                    @php $data = $knowledge->data; @endphp
                    <p class="text-gray-600 mt-2">
                        {{ $data['mo_ta'] ?? ($knowledge->description ?? 'Chưa có mô tả') }}
                    </p>
                </div>
                <div class="flex gap-2">
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

        {{-- Knowledge Tree --}}
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Lộ trình học tập</h2>

            @if($knowledge->data && is_array($knowledge->data))
                <div id="treeContainer" class="space-y-6"></div>

                <script>
                    const treeData = @json($knowledge->data);

                    const COLORS = [
                        { header: '#2563EB', light: '#EFF6FF', border: '#BFDBFE' }, // blue
                        { header: '#059669', light: '#ECFDF5', border: '#A7F3D0' }, // emerald
                        { header: '#7C3AED', light: '#F5F3FF', border: '#DDD6FE' }, // violet
                        { header: '#EA580C', light: '#FFF7ED', border: '#FED7AA' }, // orange
                        { header: '#DC2626', light: '#FEF2F2', border: '#FECACA' }, // red
                    ];

                    function escapeHtml(text) {
                        if (!text) return '';
                        return String(text).replace(/[&<>"']/g, m =>
                            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])
                        );
                    }

                    function renderTree(tree) {
                        const container = document.getElementById('treeContainer');
                        if (!tree || !tree.ten_chuyen_de) {
                            container.innerHTML = '<p class="text-center text-gray-500 py-12">Không có dữ liệu lộ trình</p>';
                            return;
                        }

                        const chuDeLon = tree.cac_chu_de_lon || [];

                        // === NODE 1: Root (Toàn bộ) ===
                        const rootWrapper = document.createElement('div');
                        rootWrapper.style.cssText = 'text-align:center;margin-bottom:24px;';
                        
                        const rootNode = document.createElement('div');
                        rootNode.draggable = true;
                        rootNode.style.cssText = 'display:inline-block;background:#FCD34D;border:3px solid #F59E0B;border-radius:16px;padding:20px 32px;cursor:grab;transition:all 0.2s;box-shadow:0 4px 12px rgba(0,0,0,0.1);';
                        rootNode.innerHTML = `
                            <div style="font-weight:900;font-size:1.2rem;color:#1e293b;margin-bottom:6px">${escapeHtml(tree.ten_chuyen_de)}</div>
                            ${tree.mo_ta ? `<div style="font-size:0.9rem;color:#64748b;max-width:400px">${escapeHtml(tree.mo_ta)}</div>` : ''}
                        `;

                        rootNode.addEventListener('dragstart', (e) => {
                            rootNode.style.opacity = '0.7';
                            rootNode.style.transform = 'scale(0.95)';
                            const sections = chuDeLon.map(l => ({
                                title: l.ten,
                                description: l.mo_ta || '',
                                items: (l.cac_chu_de_con || []).map(it => ({
                                    title: it.ten,
                                    content: it.noi_dung || '',
                                    formula: it.cong_thuc || '',
                                    example: it.vi_du || ''
                                }))
                            }));
                            e.dataTransfer.effectAllowed = 'copy';
                            e.dataTransfer.setData('application/json', JSON.stringify({
                                level: 1,
                                title: tree.ten_chuyen_de,
                                description: tree.mo_ta || '',
                                sections: sections
                            }));
                        });

                        rootNode.addEventListener('dragend', () => {
                            rootNode.style.opacity = '1';
                            rootNode.style.transform = 'scale(1)';
                        });

                        rootNode.addEventListener('mouseenter', () => {
                            rootNode.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
                        });

                        rootNode.addEventListener('mouseleave', () => {
                            rootNode.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                        });

                        rootWrapper.appendChild(rootNode);
                        container.appendChild(rootWrapper);

                        // === NODE 2 & 3: Sections with Items ===
                        chuDeLon.forEach((lon, idx) => {
                            const c = COLORS[idx % COLORS.length];
                            
                            // NODE 2 Container (Section)
                            const section = document.createElement('div');
                            section.draggable = true;
                            section.style.cssText = `border:3px solid ${c.border};background:${c.light};border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);cursor:grab;transition:all 0.15s;margin-bottom:20px;`;

                            // Section Header
                            const header = document.createElement('div');
                            header.style.cssText = `background:${c.header};padding:16px 24px;`;
                            header.innerHTML = `
                                <h4 style="color:#fff;font-weight:900;font-size:1.1rem;margin:0;display:flex;align-items:center;gap:8px">
                                    <span style="opacity:0.8">📌</span>
                                    ${escapeHtml(lon.ten)}
                                </h4>
                                ${lon.mo_ta ? `<p style="color:rgba(255,255,255,0.85);font-size:0.85rem;margin:6px 0 0 24px">${escapeHtml(lon.mo_ta)}</p>` : ''}
                            `;
                            section.appendChild(header);

                            // NODE 3 Items Grid
                            const con = lon.cac_chu_de_con || [];
                            if (con.length > 0) {
                                const grid = document.createElement('div');
                                grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;padding:16px;';

                                con.forEach(item => {
                                    const card = document.createElement('div');
                                    card.draggable = true;
                                    card.style.cssText = `background:#fff;border-radius:10px;border:2px solid ${c.border};padding:14px;box-shadow:0 1px 3px rgba(0,0,0,0.05);cursor:grab;transition:all 0.2s;`;

                                    card.addEventListener('dragstart', (e) => {
                                        e.stopPropagation();
                                        card.style.opacity = '0.6';
                                        card.style.transform = 'scale(0.92)';
                                        e.dataTransfer.effectAllowed = 'copy';
                                        e.dataTransfer.setData('application/json', JSON.stringify({
                                            level: 3,
                                            title: item.ten,
                                            content: item.noi_dung || '',
                                            formula: item.cong_thuc || '',
                                            example: item.vi_du || '',
                                            section: lon.ten,
                                            subsection: null
                                        }));
                                    });

                                    card.addEventListener('dragend', (e) => {
                                        card.style.opacity = '1';
                                        card.style.transform = 'scale(1)';
                                    });

                                    card.addEventListener('mouseenter', () => {
                                        card.style.borderColor = c.header;
                                        card.style.boxShadow = `0 4px 12px ${c.header}22`;
                                    });

                                    card.addEventListener('mouseleave', () => {
                                        card.style.borderColor = c.border;
                                        card.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
                                    });

                                    card.innerHTML = `
                                        <div style="font-weight:700;color:#0f172a;font-size:0.95rem;margin-bottom:8px">${escapeHtml(item.ten)}</div>
                                        ${item.noi_dung ? `<p style="font-size:0.85rem;color:#475569;line-height:1.4;margin:0 0 8px">${escapeHtml(item.noi_dung)}</p>` : ''}
                                        ${item.cong_thuc ? `<div style="background:#1e293b;border-radius:8px;padding:8px;margin-bottom:8px;"><code style="font-size:0.75rem;color:#fde047;font-family:monospace">${escapeHtml(item.cong_thuc)}</code></div>` : ''}
                                        ${item.vi_du ? `<div style="background:#f0f4f8;border-left:3px solid ${c.header};border-radius:4px;padding:8px;font-size:0.8rem;color:#475569"><strong>VD:</strong> ${escapeHtml(item.vi_du.slice(0,100))}</div>` : ''}
                                    `;

                                    grid.appendChild(card);
                                });

                                section.appendChild(grid);
                            }

                            // NODE 2 Drag Handler (entire section)
                            section.addEventListener('dragstart', (e) => {
                                if (e.target === section || e.target === header) {
                                    section.style.opacity = '0.8';
                                    section.style.transform = 'scale(0.98)';
                                    const items = con.map(it => ({
                                        title: it.ten,
                                        content: it.noi_dung || '',
                                        formula: it.cong_thuc || '',
                                        example: it.vi_du || ''
                                    }));
                                    e.dataTransfer.effectAllowed = 'copy';
                                    e.dataTransfer.setData('application/json', JSON.stringify({
                                        level: 2,
                                        title: lon.ten,
                                        description: lon.mo_ta || '',
                                        items: items
                                    }));
                                }
                            });

                            section.addEventListener('dragend', (e) => {
                                section.style.opacity = '1';
                                section.style.transform = 'scale(1)';
                            });

                            section.addEventListener('mouseenter', () => {
                                section.style.boxShadow = '0 6px 16px rgba(0,0,0,0.08)';
                            });

                            section.addEventListener('mouseleave', () => {
                                section.style.boxShadow = '0 2px 8px rgba(0,0,0,0.06)';
                            });

                            container.appendChild(section);
                        });
                    }

                    document.addEventListener('DOMContentLoaded', () => renderTree(treeData));
                </script>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">Chưa có dữ liệu lộ trình</p>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex gap-4 justify-center sm:justify-start">
            @if($knowledge->status === 'draft')
                <button onclick="publishRoadmap()"
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
</div>

<script>
    function publishRoadmap() {
        if (confirm('Bạn có chắc chắn muốn xuất bản lộ trình này?')) {
            showToast('Tính năng này sẽ được triển khai sớm', 'info');
        }
    }
</script>
@endsection