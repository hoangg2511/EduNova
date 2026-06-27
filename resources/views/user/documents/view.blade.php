<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->name }} - EduNova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    @php
        $rawUrl   = $document->view_url ?? '';
        $filePath = $document->url ?? '';
        $ext      = strtolower(pathinfo(parse_url($filePath, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = strtolower(pathinfo(strtok($rawUrl, '?'), PATHINFO_EXTENSION));
        }
    @endphp

    <style>
        html, body { margin:0; padding:0; height:100%; overflow:hidden; background:#0f172a; }
        * { box-sizing:border-box; }
        #pdf-canvas-container { overflow-y:auto; overflow-x:hidden; }
        .page-canvas { display:block; margin:12px auto; box-shadow:0 4px 24px rgba(0,0,0,0.5); border-radius:4px; }
        #loading-overlay { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#0f172a; z-index:20; }
        .spinner { width:40px; height:40px; border:3px solid #334155; border-top-color:#38bdf8; border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body style="display:flex;flex-direction:column;height:100vh;">

    {{-- ── TOP BAR ── --}}
    <div style="height:52px;flex-shrink:0;background:#1e293b;border-bottom:1px solid #334155;display:flex;align-items:center;justify-content:space-between;padding:0 16px;">

        {{-- Left --}}
        <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
            <a href="{{ url()->previous() }}"
               style="padding:8px;border-radius:8px;color:#94a3b8;text-decoration:none;display:flex;flex-shrink:0;transition:background .15s;"
               onmouseover="this.style.background='#334155';this.style.color='#fff'"
               onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            </a>
            <div style="min-width:0;">
                <p style="color:#fff;font-weight:700;font-size:14px;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;">
                    {{ $document->name }}
                </p>
                <p style="color:#64748b;font-size:11px;margin:0;">{{ $document->author ?? 'EduNova' }} · Chế độ xem</p>
            </div>
        </div>

        {{-- Center badge --}}
        <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#1e3a2f;border-radius:999px;flex-shrink:0;">
            <i data-lucide="eye" style="width:12px;height:12px;color:#34d399;"></i>
            <span style="font-size:11px;font-weight:700;color:#34d399;">Chỉ xem</span>
        </div>

        {{-- Right controls --}}
        <div style="display:flex;align-items:center;gap:4px;flex:1;justify-content:flex-end;">
            {{-- Page indicator (PDF only) --}}
            @if($ext === 'pdf')
            <span id="page-info" style="font-size:11px;color:#94a3b8;background:#1e293b;border:1px solid #334155;padding:4px 10px;border-radius:999px;margin-right:4px;">
                trang <span id="cur-page">1</span> / <span id="total-pages">—</span>
            </span>
            {{-- Zoom --}}
            <button onclick="zoomOut()" title="Thu nhỏ"
                    style="padding:7px;border-radius:8px;background:transparent;border:none;cursor:pointer;color:#94a3b8;"
                    onmouseover="this.style.background='#334155';this.style.color='#fff'"
                    onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                <i data-lucide="zoom-out" style="width:15px;height:15px;"></i>
            </button>
            <span id="zoom-label" style="font-size:11px;color:#64748b;min-width:36px;text-align:center;">100%</span>
            <button onclick="zoomIn()" title="Phóng to"
                    style="padding:7px;border-radius:8px;background:transparent;border:none;cursor:pointer;color:#94a3b8;"
                    onmouseover="this.style.background='#334155';this.style.color='#fff'"
                    onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                <i data-lucide="zoom-in" style="width:15px;height:15px;"></i>
            </button>
            @endif
            {{-- File type badge --}}
            <span style="font-size:10px;font-weight:700;padding:4px 10px;background:#0f172a;border:1px solid #334155;color:#94a3b8;border-radius:6px;margin-left:4px;">
                {{ strtoupper($ext) ?: 'FILE' }}
            </span>
            {{-- Fullscreen --}}
            <button onclick="toggleFullscreen()" title="Toàn màn hình"
                    style="padding:7px;border-radius:8px;background:transparent;border:none;cursor:pointer;color:#94a3b8;"
                    onmouseover="this.style.background='#334155';this.style.color='#fff'"
                    onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                <i data-lucide="maximize-2" style="width:15px;height:15px;"></i>
            </button>
        </div>
    </div>

    {{-- ── VIEWER AREA ── --}}
    <div id="viewer-container" style="flex:1;position:relative;overflow:hidden;">

        @if(empty($rawUrl))
            {{-- Không có URL --}}
            <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#64748b;gap:16px;">
                <div style="width:72px;height:72px;background:#1e293b;border-radius:20px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="alert-circle" style="width:36px;height:36px;color:#ef4444;"></i>
                </div>
                <p style="color:#fff;font-weight:700;font-size:18px;margin:0;">Không thể tải tài liệu</p>
                <p style="font-size:13px;margin:0;">Signed URL không hợp lệ hoặc đã hết hạn.</p>
                <a href="{{ url()->previous() }}"
                   style="margin-top:8px;padding:8px 20px;background:#334155;color:#fff;border-radius:10px;text-decoration:none;font-size:13px;">
                    ← Quay lại
                </a>
            </div>

        @elseif($ext === 'pdf')
            {{-- Loading overlay --}}
            <div id="loading-overlay">
                <div class="spinner" style="margin-bottom:16px;"></div>
                <p style="color:#94a3b8;font-size:13px;margin:0;">Đang tải PDF...</p>
            </div>

            {{-- PDF.js canvas container --}}
            <div id="pdf-canvas-container"
                 style="width:100%;height:100%;background:#0f172a;padding:8px 0;"
                 oncontextmenu="return false;">
            </div>

        @elseif(in_array($ext, ['doc','docx','ppt','pptx','xls','xlsx']))
            <iframe src="https://docs.google.com/viewer?url={{ urlencode($rawUrl) }}&embedded=true"
                    style="width:100%;height:100%;border:none;"></iframe>

        @elseif(in_array($ext, ['mp4','webm','ogg']))
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#000;">
                <video controls controlsList="nodownload"
                       style="max-width:100%;max-height:100%;"
                       oncontextmenu="return false;">
                    <source src="{{ $rawUrl }}" type="video/{{ $ext }}">
                </video>
            </div>

        @else
            <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#64748b;gap:12px;">
                <div style="width:72px;height:72px;background:#1e293b;border-radius:20px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="file-question" style="width:36px;height:36px;"></i>
                </div>
                <p style="color:#fff;font-weight:700;font-size:16px;margin:0;">Không hỗ trợ xem trực tiếp</p>
                <p style="font-size:13px;margin:0;">Định dạng <strong style="color:#fff;">{{ strtoupper($ext) ?: 'UNKNOWN' }}</strong> chưa được hỗ trợ preview.</p>
            </div>
        @endif
    </div>

    {{-- ── BOTTOM BAR ── --}}
    <div style="height:36px;flex-shrink:0;background:#1e293b;border-top:1px solid #1e293b;display:flex;align-items:center;justify-content:center;">
        <span style="font-size:11px;color:#475569;display:flex;align-items:center;gap:6px;">
            <i data-lucide="shield-check" style="width:12px;height:12px;color:#334155;"></i>
            Tài liệu được bảo vệ bởi EduNova · Không sao chép, không tải về
        </span>
    </div>

</body>

@if($ext === 'pdf')
{{-- PDF.js CDN --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const PDF_URL   = @json($rawUrl);
    const container = document.getElementById('pdf-canvas-container');
    const loading   = document.getElementById('loading-overlay');

    let pdfDoc      = null;
    let scale       = 1.4;
    let rendering   = false;

    async function renderAllPages(pdf) {
        container.innerHTML = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page    = await pdf.getPage(i);
            const vp      = page.getViewport({ scale });
            const canvas  = document.createElement('canvas');
            canvas.className = 'page-canvas';
            canvas.width  = vp.width;
            canvas.height = vp.height;
            container.appendChild(canvas);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;

            // Update page indicator trên scroll
            if (i === 1) {
                document.getElementById('cur-page').textContent  = '1';
                document.getElementById('total-pages').textContent = pdf.numPages;
            }
        }
    }

    // Scroll → update current page indicator
    container.addEventListener('scroll', () => {
        const canvases = container.querySelectorAll('.page-canvas');
        let closest = 1;
        canvases.forEach((c, i) => {
            const rect = c.getBoundingClientRect();
            if (rect.top <= window.innerHeight / 2) closest = i + 1;
        });
        document.getElementById('cur-page').textContent = closest;
    });

    async function loadPdf() {
        try {
            pdfDoc = await pdfjsLib.getDocument({
                url: PDF_URL,
                withCredentials: false,
            }).promise;

            loading.style.display = 'none';
            await renderAllPages(pdfDoc);
        } catch (err) {
            console.error('PDF load error:', err);
            loading.innerHTML = `
                <div style="width:56px;height:56px;background:#1e293b;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <p style="color:#fff;font-weight:700;margin:0 0 6px;">Không thể tải PDF</p>
                <p style="color:#64748b;font-size:12px;margin:0;text-align:center;max-width:280px;">${err.message}</p>`;
        }
    }

    loadPdf();

    // Zoom
    async function zoomIn()  { scale = Math.min(scale + 0.2, 3.0); updateZoomLabel(); await renderAllPages(pdfDoc); }
    async function zoomOut() { scale = Math.max(scale - 0.2, 0.5); updateZoomLabel(); await renderAllPages(pdfDoc); }
    function updateZoomLabel() {
        document.getElementById('zoom-label').textContent = Math.round(scale / 1.4 * 100) + '%';
    }
</script>
@endif

<script>
    lucide.createIcons();

    function toggleFullscreen() {
        const el = document.getElementById('viewer-container');
        if (!document.fullscreenElement) el.requestFullscreen?.();
        else document.exitFullscreen?.();
    }

    document.addEventListener('contextmenu', e => e.preventDefault());
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && ['s','p','u'].includes(e.key.toLowerCase()))
            e.preventDefault();
    });
</script>
</html>