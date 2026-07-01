@extends('layouts.app')

@section('title', 'Tin tức & Sự kiện - EduNova')

@push('styles')
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.news-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:12px; }
.news-header-left h1 { font-family:'Space Grotesk',sans-serif; font-size:26px; font-weight:800; color:#0f172a; margin-bottom:3px; }
.news-header-left p { font-size:13.5px; color:#64748b; }

.filter-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
.tabs-scroll { display:flex; gap:4px; overflow-x:auto; scrollbar-width:none; }
.tabs-scroll::-webkit-scrollbar { display:none; }
.tab-btn { padding:7px 16px; border-radius:20px; font-size:13px; font-weight:500; cursor:pointer; border:1.5px solid transparent; background:#f1f5f9; color:#64748b; white-space:nowrap; transition:all .18s; }
.tab-btn:hover:not(.active) { background:#e2e8f0; color:#334155; }
.tab-btn.active { background:#6366f1; color:#fff; border-color:#6366f1; }
.sort-select { padding:7px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; color:#475569; background:#fff; cursor:pointer; outline:none; }

.layout-wrap { display:grid; grid-template-columns:1fr 272px; gap:22px; align-items:start; }
@media(max-width:768px){ .layout-wrap { grid-template-columns:1fr; } .news-sidebar { display:none; } }

.news-list { display:flex; flex-direction:column; gap:14px; }

.h-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; display:grid; grid-template-columns:180px 1fr; cursor:pointer; transition:transform .22s,box-shadow .22s; min-height:140px; text-decoration:none; }
.h-card:hover { transform:translateY(-2px); box-shadow:0 12px 32px -6px rgba(99,102,241,.13); }
.h-card-thumb { display:flex; align-items:center; justify-content:center; font-size:52px; position:relative; flex-shrink:0; }
.thumb-edu   { background:linear-gradient(135deg,#ede9fe,#ddd6fe); }
.thumb-tech  { background:linear-gradient(135deg,#dbeafe,#bfdbfe); }
.thumb-event { background:linear-gradient(135deg,#dcfce7,#bbf7d0); }
.thumb-notice{ background:linear-gradient(135deg,#fef3c7,#fde68a); }
.thumb-health{ background:linear-gradient(135deg,#ffe4e6,#fecaca); }
.h-cat-badge { position:absolute; top:10px; left:10px; font-size:10px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.05em; }
.badge-edu    { background:#ede9fe; color:#6d28d9; }
.badge-tech   { background:#dbeafe; color:#1e40af; }
.badge-event  { background:#dcfce7; color:#15803d; }
.badge-notice { background:#fef3c7; color:#92400e; }
.badge-health { background:#ffe4e6; color:#9f1239; }
.h-card-body { padding:18px 20px; display:flex; flex-direction:column; justify-content:center; gap:6px; }
.h-card-title { font-family:'Space Grotesk',sans-serif; font-size:15.5px; font-weight:700; color:#0f172a; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.h-card-excerpt { font-size:13px; color:#64748b; line-height:1.65; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.h-card-footer { display:flex; align-items:center; justify-content:space-between; margin-top:6px; }
.h-card-meta { display:flex; align-items:center; gap:10px; font-size:12px; color:#94a3b8; flex-wrap:wrap; }
.h-card-meta .author-row { display:flex; align-items:center; gap:5px; color:#475569; font-weight:500; }
.author-dot { width:20px; height:20px; border-radius:50%; background:#ede9fe; color:#6d28d9; font-size:9px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.sep { width:3px; height:3px; border-radius:50%; background:#cbd5e1; flex-shrink:0; }
.h-card-actions { display:flex; gap:6px; }
.action-btn { width:30px; height:30px; border-radius:9px; background:#f8fafc; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#94a3b8; transition:all .18s; }
.action-btn:hover { background:#ede9fe; color:#6366f1; border-color:#c4b5fd; }
.action-btn.bookmarked { color:#6366f1; background:#ede9fe; border-color:#c4b5fd; }

.featured-card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; display:grid; grid-template-columns:260px 1fr; cursor:pointer; margin-bottom:18px; transition:transform .25s,box-shadow .25s; min-height:200px; text-decoration:none; }
.featured-card:hover { transform:translateY(-3px); box-shadow:0 16px 40px -8px rgba(99,102,241,.14); }
.feat-visual { background:linear-gradient(135deg,#c7d2fe,#a5b4fc,#818cf8); display:flex; align-items:center; justify-content:center; font-size:72px; position:relative; }
.feat-badge-top { position:absolute; top:14px; left:14px; background:rgba(255,255,255,.92); color:#6366f1; font-size:10.5px; font-weight:700; padding:4px 11px; border-radius:20px; letter-spacing:.06em; text-transform:uppercase; }
.feat-body { padding:26px 24px; display:flex; flex-direction:column; justify-content:center; gap:8px; }
.feat-cat { font-size:11px; font-weight:700; color:#6366f1; letter-spacing:.07em; text-transform:uppercase; }
.feat-title { font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:700; color:#0f172a; line-height:1.35; }
.feat-desc { font-size:13.5px; color:#64748b; line-height:1.7; }
.feat-meta { display:flex; align-items:center; gap:10px; font-size:12px; color:#94a3b8; flex-wrap:wrap; }
.feat-author { display:flex; align-items:center; gap:6px; color:#475569; font-weight:500; }
.read-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#6366f1; color:#fff; border-radius:11px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:background .18s; margin-top:4px; align-self:flex-start; }
.read-btn:hover { background:#4f46e5; }

@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.sk { background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size:200% 100%; animation:shimmer 1.5s infinite; border-radius:6px; }
.skel-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; display:grid; grid-template-columns:180px 1fr; min-height:140px; }
.skel-body { padding:18px 20px; display:flex; flex-direction:column; gap:10px; justify-content:center; }

#scroll-sentinel { height:60px; display:flex; align-items:center; justify-content:center; margin-top:4px; }
.loader-dots { display:flex; gap:6px; align-items:center; }
.loader-dot { width:8px; height:8px; border-radius:50%; background:#c4b5fd; animation:bd .6s infinite alternate; }
.loader-dot:nth-child(2) { animation-delay:.15s; }
.loader-dot:nth-child(3) { animation-delay:.3s; }
@keyframes bd { from{transform:translateY(0);opacity:.5} to{transform:translateY(-6px);opacity:1} }
.end-msg { text-align:center; font-size:13px; color:#94a3b8; display:flex; align-items:center; justify-content:center; gap:8px; padding:16px; }

.panel-box { background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:18px 16px; margin-bottom:16px; }
.panel-title { font-family:'Space Grotesk',sans-serif; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:14px; display:flex; align-items:center; gap:7px; }
.trending-item { display:flex; gap:10px; padding:10px 0; border-bottom:1px solid #f8fafc; cursor:pointer; transition:opacity .18s; text-decoration:none; }
.trending-item:hover { opacity:.7; }
.trending-item:last-child { border-bottom:none; padding-bottom:0; }
.trend-num { font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:800; color:#e2e8f0; min-width:28px; line-height:1; padding-top:2px; }
.trend-title { font-size:13px; font-weight:600; color:#334155; line-height:1.4; margin-bottom:3px; }
.trend-meta { font-size:11px; color:#94a3b8; display:flex; align-items:center; gap:4px; }
.tags-wrap { display:flex; flex-wrap:wrap; gap:7px; }
.tag-pill { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:500; background:#f1f5f9; color:#475569; border:1.5px solid transparent; cursor:pointer; transition:all .18s; }
.tag-pill:hover,.tag-pill.active { background:#ede9fe; color:#6366f1; border-color:#c4b5fd; }
.cta-box { background:linear-gradient(135deg,#ede9fe,#ddd6fe); border:1px solid #c4b5fd; border-radius:18px; padding:20px 16px; }
.cta-eyebrow { font-size:10.5px; font-weight:700; color:#7c3aed; text-transform:uppercase; letter-spacing:.07em; margin-bottom:7px; }
.cta-title { font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:700; color:#3730a3; margin-bottom:7px; line-height:1.4; }
.cta-desc { font-size:12.5px; color:#4c1d95; line-height:1.65; margin-bottom:14px; }
.subscribe-btn { width:100%; padding:9px; border-radius:11px; background:#6366f1; color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:background .18s; }
.subscribe-btn:hover { background:#4f46e5; }

.news-toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px); z-index:999; background:#1e293b; color:#fff; padding:11px 20px; border-radius:14px; font-size:13px; font-weight:500; display:flex; align-items:center; gap:8px; box-shadow:0 8px 24px rgba(0,0,0,.2); transition:transform .3s cubic-bezier(.175,.885,.32,1.275); pointer-events:none; }
.news-toast.show { transform:translateX(-50%) translateY(0); }
/* Thumbnail có ảnh thật → không dùng gradient background */
.h-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* Badge vẫn hiển thị trên ảnh */
.h-card-thumb {
    position: relative;
}
</style>
@endpush

@section('content')

<div class="news-header">
    <div class="news-header-left">
        <h1>Tin tức & Sự kiện</h1>
        <p>Cập nhật những thông tin giáo dục mới nhất từ EduNova</p>
    </div>
</div>

<div class="filter-bar">
    <div class="tabs-scroll" id="categoryTabs">
        <button class="tab-btn active" data-cat="all">Tất cả</button>
        <button class="tab-btn" data-cat="edu">📚 Giáo dục</button>
        <button class="tab-btn" data-cat="tech">💻 Công nghệ</button>
        <button class="tab-btn" data-cat="event">🏆 Sự kiện</button>
        <button class="tab-btn" data-cat="notice">📢 Thông báo</button>
        <button class="tab-btn" data-cat="health">❤️ Sức khoẻ</button>
    </div>
    <select class="sort-select" id="sortSelect">
        <option value="newest">Mới nhất</option>
        <option value="popular">Phổ biến nhất</option>
    </select>
</div>

{{-- FEATURED --}}
@if($featured)
<a href="{{ route('user.news.show', $featured->slug) }}" class="featured-card">
    <div class="feat-visual" style="{{ $featured->thumbnail_url ? 'padding:0;overflow:hidden' : '' }}">
        <span class="feat-badge-top">⭐ Nổi bật</span>
        @if($featured->thumbnail_url)
            <img src="{{ $featured->thumbnail_url }}"
                 alt="{{ $featured->title }}"
                 style="width:100%;height:100%;object-fit:cover;display:block">
        @else
            {{ $featured->emoji }}
        @endif
    </div>
    <div class="feat-body">
        <div class="feat-cat">{{ $featured->category_label }}</div>
        <div class="feat-title">{{ $featured->title }}</div>
        <div class="feat-desc">{{ $featured->excerpt }}</div>
        <div class="feat-meta">
            <div class="feat-author">
                <div class="author-dot">{{ $featured->author_initials }}</div>
                <span>{{ $featured->author_name }}</span>
            </div>
            <span class="sep"></span>
            <span>{{ $featured->formatted_date }}</span>
            <span class="sep"></span>
            <span>{{ $featured->read_time }} phút · {{ $featured->formatted_views }} lượt xem</span>
        </div>
        <button class="read-btn" type="button">
            Đọc bài <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
        </button>
    </div>
</a>
@endif

<div class="layout-wrap">
    <div>
        <div class="news-list" id="newsList"></div>
        <div id="scroll-sentinel">
            <div class="loader-dots" id="loaderDots">
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
            </div>
        </div>
    </div>

    {{-- SIDEBAR --}}
    <div class="news-sidebar">
        <div class="panel-box">
            <div class="panel-title"><i data-lucide="trending-up"></i> Đang hot</div>
            @foreach($trending as $i => $t)
            <a href="{{ route('user.news.show', $t->slug) }}" class="trending-item">
                <div class="trend-num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
                <div>
                    <div class="trend-title">{{ $t->title }}</div>
                    <div class="trend-meta">
                        <i data-lucide="eye" style="width:11px;height:11px"></i>
                        {{ $t->formatted_views }} · {{ $t->published_at?->diffForHumans() }}
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="panel-box">
            <div class="panel-title"><i data-lucide="tag"></i> Chủ đề</div>
            <div class="tags-wrap">
                <span class="tag-pill" onclick="filterByTag(this,'edu')">AI giáo dục</span>
                <span class="tag-pill" onclick="filterByTag(this,'notice')">Học bổng</span>
                <span class="tag-pill" onclick="filterByTag(this,'tech')">Lập trình</span>
                <span class="tag-pill" onclick="filterByTag(this,'edu')">STEM</span>
                <span class="tag-pill" onclick="filterByTag(this,'notice')">Thi cử</span>
                <span class="tag-pill" onclick="filterByTag(this,'tech')">Trực tuyến</span>
                <span class="tag-pill" onclick="filterByTag(this,'health')">Sức khoẻ tâm lý</span>
            </div>
        </div>

        <div class="cta-box">
            <div class="cta-eyebrow">Dành cho bạn</div>
            <div class="cta-title">Nhận bản tin giáo dục mỗi tuần</div>
            <div class="cta-desc">Tổng hợp tin tức và tài liệu học tập gửi thẳng tới hộp thư của bạn.</div>
            <button class="subscribe-btn" onclick="showToastMsg('Đăng ký thành công! 🎉')">Đăng ký ngay →</button>
        </div>
    </div>
</div>

<div class="news-toast" id="newsToast">
    <i data-lucide="check-circle" style="width:15px;height:15px;color:#4ade80"></i>
    <span id="newsToastMsg"></span>
</div>

@endsection

@push('scripts')
<script>
const FEED_URL      = "{{ route('user.news.feed') }}";
const BOOKMARK_BASE = "{{ url('/user/news') }}";
const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content || '';
const USER_ID       = {{ $userId }};

const THUMB = { edu:'thumb-edu', tech:'thumb-tech', event:'thumb-event', notice:'thumb-notice', health:'thumb-health' };
const BADGE = {
    edu:    { cls:'badge-edu',    label:'Giáo dục' },
    tech:   { cls:'badge-tech',   label:'Công nghệ' },
    event:  { cls:'badge-event',  label:'Sự kiện' },
    notice: { cls:'badge-notice', label:'Thông báo' },
    health: { cls:'badge-health', label:'Sức khoẻ' },
};

// ── State ──────────────────────────────
let page      = 1;
let loading   = false;
let hasMore   = true;
let activeCat = 'all';
let sort      = 'newest';

// ── Render card ────────────────────────
function card(a) {
    const b  = BADGE[a.category] || { cls:'', label:'' };
    const bm = a.bookmarked;

    // ── Thumbnail: ảnh thật hoặc fallback emoji ──────────────────────────
    const thumbHtml = a.thumbnail_url
        ? `<img src="${a.thumbnail_url}"
                alt="${a.title}"
                class="w-full h-full object-cover"
                onerror="this.parentElement.innerHTML='<span style=\\'font-size:52px\\'>${a.emoji}</span>'">`
        : `<span style="font-size:52px">${a.emoji}</span>`;

    return `
    <a href="${a.detailUrl}" class="h-card">
        <div class="h-card-thumb ${a.thumbnail_url ? '' : (THUMB[a.category] || '')}"
             style="${a.thumbnail_url ? 'padding:0;overflow:hidden' : ''}">
            ${thumbHtml}
            <span class="h-cat-badge ${b.cls}">${b.label}</span>
        </div>
        <div class="h-card-body">
            <div class="h-card-title">${a.title}</div>
            <div class="h-card-excerpt">${a.excerpt}</div>
            <div class="h-card-footer">
                <div class="h-card-meta">
                    <div class="author-row">
                        <div class="author-dot">${a.initials}</div>
                        <span>${a.author}</span>
                    </div>
                    <span class="sep"></span>
                    <span>${a.date}</span>
                    <span class="sep"></span>
                    <span style="display:flex;align-items:center;gap:3px">
                        <i data-lucide="clock" style="width:11px;height:11px"></i> ${a.readTime} phút
                    </span>
                    <span class="sep"></span>
                    <span style="display:flex;align-items:center;gap:3px">
                        <i data-lucide="eye" style="width:11px;height:11px"></i> ${a.views}
                    </span>
                </div>
                <div class="h-card-actions" onclick="event.preventDefault();event.stopPropagation()">
                    <button class="action-btn" onclick="shareArt('${a.detailUrl}','${a.title}')" title="Chia sẻ">
                        <i data-lucide="share-2" style="width:13px;height:13px"></i>
                    </button>
                    ${USER_ID ? `
                    <button class="action-btn ${bm?'bookmarked':''}" id="bm-${a.id}"
                            onclick="toggleBm(${a.id},this)" title="Lưu bài">
                        <i data-lucide="bookmark" style="width:13px;height:13px"></i>
                    </button>` : ''}
                </div>
            </div>
        </div>
    </a>`;
}

function skeleton(n) {
    return Array.from({length:n}, () => `
        <div class="skel-card">
            <div class="skel-thumb sk" style="height:100%"></div>
            <div class="skel-body">
                <div class="sk" style="height:14px;width:90%"></div>
                <div class="sk" style="height:14px;width:70%"></div>
                <div class="sk" style="height:12px;width:100%;margin-top:10px"></div>
                <div class="sk" style="height:12px;width:60%"></div>
                <div class="sk" style="height:11px;width:40%;margin-top:12px"></div>
            </div>
        </div>`).join('');
}

// ── Load feed from API ─────────────────
async function loadMore() {
    if (loading || !hasMore) return;
    loading = true;

    const list = document.getElementById('newsList');
    if (page === 1) list.innerHTML = skeleton(5);
    else document.getElementById('loaderDots').style.display = 'flex';

    try {
        const res  = await fetch(`${FEED_URL}?category=${activeCat}&sort=${sort}&page=${page}`);
        const json = await res.json();

        if (page === 1) {
            list.innerHTML = json.data.length
                ? json.data.map(card).join('')
                : `<div style="text-align:center;padding:40px;color:#94a3b8;">Không có bài viết nào.</div>`;
        } else {
            json.data.forEach((a, i) => {
                const wrap = document.createElement('div');
                wrap.innerHTML = card(a).trim();
                const el = wrap.firstChild;
                el.style.cssText = 'opacity:0;transform:translateY(14px);transition:opacity .3s ease,transform .3s ease';
                list.appendChild(el);
                setTimeout(() => { el.style.opacity='1'; el.style.transform='translateY(0)'; }, i*70);
            });
        }

        hasMore = json.hasMore;
        lucide.createIcons();
        document.getElementById('loaderDots').style.display = 'none';

        if (!hasMore) {
            document.getElementById('scroll-sentinel').innerHTML =
                `<div class="end-msg">
                    <i data-lucide="check-circle" style="width:15px;height:15px;color:#a78bfa"></i>
                    Đã hiển thị toàn bộ ${json.total} bài viết
                </div>`;
            lucide.createIcons();
        }

        page++;
    } catch(e) {
        console.error('Feed error:', e);
        document.getElementById('loaderDots').style.display = 'none';
        showToastMsg('Lỗi tải dữ liệu. Vui lòng thử lại.');
    }

    loading = false;
}

function reset() {
    page = 1; hasMore = true; loading = false;
    document.getElementById('newsList').innerHTML = '';
    const s = document.getElementById('scroll-sentinel');
    s.innerHTML = `<div class="loader-dots" id="loaderDots">
        <div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div>
    </div>`;
    loadMore();
}

// ── Infinite scroll ────────────────────
const io = new IntersectionObserver(e => { if (e[0].isIntersecting) loadMore(); }, { rootMargin:'200px' });
io.observe(document.getElementById('scroll-sentinel'));

// ── Tabs ───────────────────────────────
document.getElementById('categoryTabs').addEventListener('click', e => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeCat = btn.dataset.cat;
    reset();
});

// ── Sort ───────────────────────────────
document.getElementById('sortSelect').addEventListener('change', e => { sort = e.target.value; reset(); });

// ── Tag filter ─────────────────────────
function filterByTag(el, cat) {
    document.querySelectorAll('.tag-pill').forEach(t => t.classList.remove('active'));
    el.classList.toggle('active');
    activeCat = el.classList.contains('active') ? cat : 'all';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === activeCat));
    reset();
}

// ── Bookmark ───────────────────────────
async function toggleBm(id, btn) {
    if (!USER_ID) { showToastMsg('Vui lòng đăng nhập để lưu bài!'); return; }
    try {
        const res  = await fetch(`${BOOKMARK_BASE}/${id}/bookmark`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        btn.classList.toggle('bookmarked', json.bookmarked);
        showToastMsg(json.message);
        lucide.createIcons();
    } catch { showToastMsg('Có lỗi xảy ra!'); }
}

// ── Share ──────────────────────────────
function shareArt(url, title) {
    if (navigator.share) navigator.share({ title, url });
    else navigator.clipboard?.writeText(url).then(() => showToastMsg('Đã sao chép link!'));
}

// ── Toast ──────────────────────────────
function showToastMsg(msg) {
    const t = document.getElementById('newsToast');
    document.getElementById('newsToastMsg').textContent = msg;
    t.classList.add('show');
    clearTimeout(window._tt);
    window._tt = setTimeout(() => t.classList.remove('show'), 2600);
    lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush