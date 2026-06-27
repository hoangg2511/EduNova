@extends('layouts.app')

@section('title', $article->title . ' - EduNova')

@push('styles')
<style>
.article-wrap {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 28px;
    align-items: start;
    max-width: 1100px;
}
@media(max-width: 768px) {
    .article-wrap { grid-template-columns: 1fr; }
    .article-sidebar { display: none; }
}

/* ── Breadcrumb ── */
.breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: #94a3b8; margin-bottom: 20px; flex-wrap: wrap;
}
.breadcrumb a { color: #64748b; text-decoration: none; }
.breadcrumb a:hover { color: #6366f1; }
.breadcrumb-sep { color: #cbd5e1; }

/* ── Hero ── */
.article-hero {
    width: 100%; border-radius: 20px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    min-height: 220px; font-size: 96px; margin-bottom: 28px;
    position: relative;
}
.hero-edu    { background: linear-gradient(135deg,#ede9fe,#ddd6fe); }
.hero-tech   { background: linear-gradient(135deg,#dbeafe,#bfdbfe); }
.hero-event  { background: linear-gradient(135deg,#dcfce7,#bbf7d0); }
.hero-notice { background: linear-gradient(135deg,#fef3c7,#fde68a); }
.hero-health { background: linear-gradient(135deg,#ffe4e6,#fecaca); }

.hero-cat-badge {
    position: absolute; top: 16px; left: 16px;
    font-size: 11px; font-weight: 700; padding: 4px 12px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: .05em;
}
.badge-edu    { background:#ede9fe; color:#6d28d9; }
.badge-tech   { background:#dbeafe; color:#1e40af; }
.badge-event  { background:#dcfce7; color:#15803d; }
.badge-notice { background:#fef3c7; color:#92400e; }
.badge-health { background:#ffe4e6; color:#9f1239; }

/* ── Article header ── */
.article-header { margin-bottom: 24px; }
.article-category {
    font-size: 11px; font-weight: 700; color: #6366f1;
    letter-spacing: .07em; text-transform: uppercase; margin-bottom: 10px;
}
.article-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 28px; font-weight: 800; color: #0f172a;
    line-height: 1.3; margin-bottom: 16px;
}
.article-meta {
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: #94a3b8; flex-wrap: wrap;
    padding-bottom: 18px; border-bottom: 1px solid #f1f5f9;
}
.meta-author {
    display: flex; align-items: center; gap: 8px;
    color: #334155; font-weight: 600;
}
.author-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: #ede9fe; color: #6d28d9;
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.meta-sep { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }
.meta-item { display: flex; align-items: center; gap: 4px; }

/* ── Article actions (top) ── */
.article-actions-bar {
    display: flex; align-items: center; gap: 8px; margin-bottom: 28px;
}
.action-pill {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;
    cursor: pointer; border: 1.5px solid #e2e8f0; background: #fff;
    color: #475569; transition: all .18s;
}
.action-pill:hover { background: #f8fafc; border-color: #cbd5e1; }
.action-pill.bookmarked { background: #ede9fe; border-color: #c4b5fd; color: #6366f1; }
.action-pill i { width: 15px; height: 15px; }

/* ── Article body ── */
.article-body {
    font-size: 15px; line-height: 1.8; color: #334155;
}
.article-body h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 20px; font-weight: 700; color: #0f172a;
    margin: 32px 0 12px; padding-bottom: 8px;
    border-bottom: 2px solid #f1f5f9;
}
.article-body h3 {
    font-size: 17px; font-weight: 700; color: #1e293b; margin: 24px 0 10px;
}
.article-body p { margin-bottom: 18px; }
.article-body ul, .article-body ol {
    padding-left: 22px; margin-bottom: 18px; display: flex; flex-direction: column; gap: 6px;
}
.article-body li { line-height: 1.7; }
.article-body blockquote {
    border-left: 4px solid #a5b4fc; padding: 14px 20px;
    background: #f8f7ff; border-radius: 0 12px 12px 0;
    font-style: italic; color: #4338ca; margin: 24px 0;
    font-size: 15.5px; line-height: 1.7;
}
.article-body strong { color: #0f172a; }

/* ── Tags ── */
.article-tags { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 32px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
.tag-label { font-size: 12px; font-weight: 600; color: #64748b; }
.tag-chip { padding: 5px 12px; background: #f1f5f9; color: #475569; border-radius: 20px; font-size: 12px; }

/* ── Related ── */
.related-section { margin-top: 40px; }
.related-title { font-family: 'Space Grotesk', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
@media(max-width: 640px) { .related-grid { grid-template-columns: 1fr; } }
.related-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
    overflow: hidden; text-decoration: none; transition: transform .2s, box-shadow .2s;
}
.related-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -4px rgba(99,102,241,.12); }
.related-thumb { height: 80px; display: flex; align-items: center; justify-content: center; font-size: 36px; }
.related-body { padding: 12px 14px; }
.related-title-text { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 6px; }
.related-meta { font-size: 11px; color: #94a3b8; }

/* ── Sidebar ── */
.sticky-sidebar { position: sticky; top: 20px; }
.sidebar-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 18px 16px; margin-bottom: 16px; }
.sidebar-section-title { font-family: 'Space Grotesk', sans-serif; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }
.progress-wrap { margin-bottom: 12px; }
.progress-label { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.progress-bar { height: 6px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #818cf8, #6366f1); border-radius: 99px; transition: width .3s; }
.toc-list { display: flex; flex-direction: column; gap: 6px; list-style: none; padding: 0; }
.toc-item a { font-size: 13px; color: #64748b; text-decoration: none; line-height: 1.5; display: block; padding: 4px 0; border-left: 2px solid #f1f5f9; padding-left: 10px; transition: all .18s; }
.toc-item a:hover, .toc-item a.active { color: #6366f1; border-left-color: #6366f1; }

/* ── Toast ── */
.news-toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px); z-index:999; background:#1e293b; color:#fff; padding:11px 20px; border-radius:14px; font-size:13px; font-weight:500; display:flex; align-items:center; gap:8px; box-shadow:0 8px 24px rgba(0,0,0,.2); transition:transform .3s cubic-bezier(.175,.885,.32,1.275); pointer-events:none; }
.news-toast.show { transform:translateX(-50%) translateY(0); }
</style>
@endpush

@section('content')

@php
    $catHero = [
        'edu'    => 'hero-edu',
        'tech'   => 'hero-tech',
        'event'  => 'hero-event',
        'notice' => 'hero-notice',
        'health' => 'hero-health',
    ];
    $catBadge = [
        'edu'    => 'badge-edu',
        'tech'   => 'badge-tech',
        'event'  => 'badge-event',
        'notice' => 'badge-notice',
        'health' => 'badge-health',
    ];
    $relThumb = [
        'edu'    => 'background:linear-gradient(135deg,#ede9fe,#ddd6fe)',
        'tech'   => 'background:linear-gradient(135deg,#dbeafe,#bfdbfe)',
        'event'  => 'background:linear-gradient(135deg,#dcfce7,#bbf7d0)',
        'notice' => 'background:linear-gradient(135deg,#fef3c7,#fde68a)',
        'health' => 'background:linear-gradient(135deg,#ffe4e6,#fecaca)',
    ];
@endphp

{{-- Breadcrumb --}}
<div class="breadcrumb">
    <a href="{{ route('user.news.index') }}">Tin tức</a>
    <span class="breadcrumb-sep">›</span>
    <a href="{{ route('user.news.index') }}?category={{ $article->category }}">{{ $article->category_label }}</a>
    <span class="breadcrumb-sep">›</span>
    <span style="color:#0f172a;font-weight:500;">{{ Str::limit($article->title, 50) }}</span>
</div>

<div class="article-wrap">

    {{-- ── MAIN CONTENT ── --}}
    <article>

        {{-- Hero --}}
        <div class="article-hero {{ $catHero[$article->category] ?? 'hero-edu' }}">
            <span class="hero-cat-badge {{ $catBadge[$article->category] ?? 'badge-edu' }}">
                {{ $article->category_label }}
            </span>
            {{ $article->emoji }}
        </div>

        {{-- Header --}}
        <div class="article-header">
            <div class="article-category">{{ $article->category_label }}</div>
            <h1 class="article-title">{{ $article->title }}</h1>
            <div class="article-meta">
                <div class="meta-author">
                    <div class="author-avatar">{{ $article->author_initials }}</div>
                    <span>{{ $article->author_name }}</span>
                </div>
                <span class="meta-sep"></span>
                <span class="meta-item">
                    <i data-lucide="calendar" style="width:13px;height:13px"></i>
                    {{ $article->formatted_date }}
                </span>
                <span class="meta-sep"></span>
                <span class="meta-item">
                    <i data-lucide="clock" style="width:13px;height:13px"></i>
                    {{ $article->read_time }} phút đọc
                </span>
                <span class="meta-sep"></span>
                <span class="meta-item">
                    <i data-lucide="eye" style="width:13px;height:13px"></i>
                    {{ $article->formatted_views }} lượt xem
                </span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="article-actions-bar">
            @auth
            <button class="action-pill {{ $isBookmarked ? 'bookmarked' : '' }}"
                    id="bookmark-btn"
                    onclick="toggleBookmark({{ $article->id }})">
                <i data-lucide="bookmark"></i>
                <span id="bm-label">{{ $isBookmarked ? 'Đã lưu' : 'Lưu bài' }}</span>
            </button>
            @endauth
            <button class="action-pill" onclick="shareArticle()">
                <i data-lucide="share-2"></i>
                Chia sẻ
            </button>
            <button class="action-pill" onclick="copyLink()">
                <i data-lucide="link"></i>
                Sao chép link
            </button>
            <a href="{{ route('user.news.index') }}" class="action-pill" style="margin-left:auto;text-decoration:none;">
                <i data-lucide="arrow-left"></i>
                Quay lại
            </a>
        </div>

        {{-- Body --}}
        <div class="article-body" id="article-body">
            {!! $article->content !!}
        </div>

        {{-- Tags --}}
        <div class="article-tags">
            <span class="tag-label">Tags:</span>
            <span class="tag-chip">{{ $article->category_label }}</span>
            <span class="tag-chip">Giáo dục</span>
            <span class="tag-chip">EduNova</span>
        </div>

        {{-- Related --}}
        @if($related->isNotEmpty())
        <div class="related-section">
            <div class="related-title">
                <i data-lucide="layers" style="width:18px;height:18px;color:#6366f1"></i>
                Bài viết liên quan
            </div>
            <div class="related-grid">
                @foreach($related as $r)
                <a href="{{ route('user.news.show', $r->slug) }}" class="related-card">
                    <div class="related-thumb" style="{{ $relThumb[$r->category] ?? '' }}">
                        {{ $r->emoji }}
                    </div>
                    <div class="related-body">
                        <div class="related-title-text">{{ $r->title }}</div>
                        <div class="related-meta">
                            {{ $r->author_name }} · {{ $r->read_time }} phút ·
                            <i data-lucide="eye" style="width:10px;height:10px;display:inline"></i>
                            {{ $r->formatted_views }}
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </article>

    {{-- ── SIDEBAR ── --}}
    <aside class="article-sidebar">
        <div class="sticky-sidebar">

            {{-- Reading progress --}}
            <div class="sidebar-card">
                <div class="sidebar-section-title">
                    <i data-lucide="bar-chart-2" style="width:15px;height:15px;color:#6366f1"></i>
                    Tiến độ đọc
                </div>
                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Đã đọc</span>
                        <span id="progress-pct">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width:0%"></div>
                    </div>
                </div>
                <p style="font-size:12px;color:#94a3b8;">
                    Ước tính {{ $article->read_time }} phút đọc
                </p>
            </div>

            {{-- Table of contents --}}
            <div class="sidebar-card" id="toc-card" style="display:none;">
                <div class="sidebar-section-title">
                    <i data-lucide="list" style="width:15px;height:15px;color:#6366f1"></i>
                    Mục lục
                </div>
                <ul class="toc-list" id="toc-list"></ul>
            </div>

            {{-- Author info --}}
            <div class="sidebar-card">
                <div class="sidebar-section-title">
                    <i data-lucide="user" style="width:15px;height:15px;color:#6366f1"></i>
                    Tác giả
                </div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div class="author-avatar" style="width:44px;height:44px;font-size:16px;">
                        {{ $article->author_initials }}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#0f172a;">{{ $article->author_name }}</div>
                        <div style="font-size:12px;color:#94a3b8;">Biên tập viên EduNova</div>
                    </div>
                </div>
                <p style="font-size:12.5px;color:#64748b;line-height:1.6;">
                    Chuyên gia giáo dục với nhiều năm kinh nghiệm nghiên cứu và giảng dạy tại Việt Nam.
                </p>
            </div>

            {{-- Back to list --}}
            <a href="{{ route('user.news.index') }}"
               style="display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;font-size:13px;font-weight:600;color:#475569;text-decoration:none;transition:background .18s;"
               onmouseover="this.style.background='#f1f5f9'"
               onmouseout="this.style.background='#f8fafc'">
                <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
                Về danh sách tin tức
            </a>
        </div>
    </aside>

</div>

{{-- Toast --}}
<div class="news-toast" id="newsToast">
    <i data-lucide="check-circle" style="width:15px;height:15px;color:#4ade80"></i>
    <span id="newsToastMsg"></span>
</div>

@endsection

@push('scripts')
<script>
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
const ARTICLE_ID = {{ $article->id }};

// ── Reading progress ──────────────────
function updateProgress() {
    const body  = document.getElementById('article-body');
    const fill  = document.getElementById('progress-fill');
    const pct   = document.getElementById('progress-pct');
    if (!body) return;

    const rect   = body.getBoundingClientRect();
    const total  = body.offsetHeight;
    const scrolled = Math.max(0, -rect.top + window.innerHeight * 0.2);
    const percent  = Math.min(100, Math.round((scrolled / total) * 100));

    fill.style.width = percent + '%';
    pct.textContent  = percent + '%';
}
window.addEventListener('scroll', updateProgress, { passive: true });

// ── Table of Contents ─────────────────
(function buildToc() {
    const headings = document.querySelectorAll('.article-body h2, .article-body h3');
    if (headings.length < 2) return;

    const list = document.getElementById('toc-list');
    headings.forEach((h, i) => {
        h.id = 'heading-' + i;
        const li = document.createElement('li');
        li.className = 'toc-item';
        const a = document.createElement('a');
        a.href = '#heading-' + i;
        a.textContent = h.textContent;
        if (h.tagName === 'H3') a.style.paddingLeft = '20px';
        a.addEventListener('click', e => {
            e.preventDefault();
            h.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        li.appendChild(a);
        list.appendChild(li);
    });

    document.getElementById('toc-card').style.display = 'block';

    // Highlight active heading on scroll
    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            const id = entry.target.id;
            const link = list.querySelector(`a[href="#${id}"]`);
            if (link) link.classList.toggle('active', entry.isIntersecting);
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    headings.forEach(h => io.observe(h));
})();

// ── Bookmark ──────────────────────────
async function toggleBookmark(id) {
    const btn   = document.getElementById('bookmark-btn');
    const label = document.getElementById('bm-label');
    try {
        const res  = await fetch(`/news/${id}/bookmark`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        btn.classList.toggle('bookmarked', json.bookmarked);
        label.textContent = json.bookmarked ? 'Đã lưu' : 'Lưu bài';
        showToast(json.message);
        lucide.createIcons();
    } catch { showToast('Có lỗi xảy ra!'); }
}

// ── Share & Copy ──────────────────────
function shareArticle() {
    if (navigator.share) {
        navigator.share({ title: document.title, url: location.href });
    } else {
        copyLink();
    }
}
function copyLink() {
    navigator.clipboard?.writeText(location.href)
        .then(() => showToast('Đã sao chép link!'))
        .catch(() => showToast('Không thể sao chép'));
}

// ── Toast ─────────────────────────────
function showToast(msg) {
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