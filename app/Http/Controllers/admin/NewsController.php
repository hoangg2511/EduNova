<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\NewsTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SupabaseService;
class NewsController extends Controller
{
    public function __construct(protected SupabaseService $supabase) {}

    // ── Page ──────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        return view('admin.news.index');
    }

    // ── API: article list + kpis + categories ─────────────────────────────────

    /**
     * GET /admin/news/data
     * Query: search, status, category
     */
    public function data(Request $request): JsonResponse
    {
        NewsArticle::publishDueScheduled(); // lazy fallback

        $articles = NewsArticle::with('tags')
            ->search($request->input('search'))
            ->ofStatus($request->input('status'))
            ->ofCategory($request->input('category'))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NewsArticle $a) => $this->formatArticle($a));

        $categories = NewsArticle::distinct()->orderBy('category')->pluck('category');

        return response()->json([
            'data'       => $articles,
            'kpis'       => $this->buildKpis(),
            'categories' => $categories,
            'tabs'       => $this->buildTabs(),
        ]);
    }

    // ── API: store ─────────────────────────────────────────────────────────────

    /**
     * POST /admin/news
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'category'     => 'required|string|max:100',
            'author_name'  => 'nullable|string|max:100',
            'status'       => 'required|in:draft,published,scheduled',
            'is_featured'  => 'boolean',
            'scheduled_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'string|max:50',
            'thumbnail_url'=> 'nullable|string|url|max:2048',
        ]);

        $data['slug']         = NewsArticle::generateSlug($data['title']);
        $data['read_time']    = $this->calcReadTime($data['content'] ?? '');
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['author_name']  = $data['author_name'] ?? 'Admin EduNova';

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $article = NewsArticle::create($data);
        $article->syncTagNames($tags);
        $article->load('tags');

        return response()->json([
            'message' => 'Đã đăng bài thành công!',
            'article' => $this->formatArticle($article),
            'kpis'    => $this->buildKpis(),
            'tabs'    => $this->buildTabs(),
        ], 201);
    }

    // ── API: update ────────────────────────────────────────────────────────────

    /**
     * PUT /admin/news/{article}
     */
    public function update(Request $request, NewsArticle $article): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'category'     => 'sometimes|required|string|max:100',
            'author_name'  => 'nullable|string|max:100',
            'status'       => 'sometimes|required|in:draft,published,scheduled',
            'is_featured'  => 'boolean',
            'scheduled_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'string|max:50',
            'thumbnail_url'=> 'nullable|string|url|max:2048',
        ]);

        if (isset($data['content'])) {
            $data['read_time'] = $this->calcReadTime($data['content']);
        }

        // Tự set published_at khi chuyển sang published lần đầu
        if (($data['status'] ?? null) === 'published' && ! $article->published_at) {
            $data['published_at'] = now();
        }

        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $article->update($data);
        if ($tags !== null) $article->syncTagNames($tags);
        $article->load('tags');

        return response()->json([
            'message' => 'Đã cập nhật bài viết!',
            'article' => $this->formatArticle($article),
            'kpis'    => $this->buildKpis(),
            'tabs'    => $this->buildTabs(),
        ]);
    }

    // ── API: upload thumbnail ──────────────────────────────────────────────────
 
    /**
     * POST /admin/news/upload-image
     *
     * Nhận file ảnh, upload lên Supabase Storage bucket "news",
     * trả về public URL để frontend lưu vào form.thumbnail_url.
     *
     * Không cần article ID — upload trước, lưu URL vào bài sau.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',                        // jpeg, png, gif, webp, bmp, svg
                'mimes:jpeg,png,webp,gif',
                'max:5120',                     // 5 MB
            ],
        ]);
 
        $file   = $request->file('image');
        $bucket = 'documents';                       // tên bucket Supabase
        $folder = 'news';
 
        // Tên file: timestamp_uniqid.ext (tránh trùng)
        $ext      = $file->getClientOriginalExtension();
        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $path     = "{$folder}/{$fileName}";
 
        $response = $this->supabase->uploadDocument(
            $bucket,
            $path,
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        );
 
        if (! $response->successful()) {
            return response()->json([
                'message' => 'Upload thất bại: ' . $response->body(),
            ], 422);
        }
 
        $publicUrl = $this->supabase->getPublicUrl($bucket, $path);
 
        return response()->json([
            'message' => 'Upload thành công!',
            'url'     => $publicUrl,
            'path'    => $path,
        ]);
    }

    // ── API: destroy ───────────────────────────────────────────────────────────

    /**
     * DELETE /admin/news/{article}
     */
    public function destroy(NewsArticle $article): JsonResponse
    {
        $article->delete();

        return response()->json([
            'message' => 'Đã xóa bài viết',
            'kpis'    => $this->buildKpis(),
            'tabs'    => $this->buildTabs(),
        ]);
    }

    // ── API: toggle pin ────────────────────────────────────────────────────────

    /**
     * PATCH /admin/news/{article}/pin
     */
    public function togglePin(NewsArticle $article): JsonResponse
    {
        $article->update(['is_featured' => ! $article->is_featured]);

        return response()->json([
            'message'     => $article->is_featured ? 'Đã ghim bài viết' : 'Đã bỏ ghim',
            'is_featured' => $article->is_featured,
        ]);
    }

    // ── API: toggle status (published ↔ draft) ─────────────────────────────────

    /**
     * PATCH /admin/news/{article}/toggle-status
     */
    public function toggleStatus(NewsArticle $article): JsonResponse
    {
        if ($article->status === 'published') {
            $article->unpublish();
            $msg = 'Đã chuyển về nháp';
        } else {
            $article->publish();
            $msg = 'Đã xuất bản bài viết';
        }

        return response()->json([
            'message' => $msg,
            'status'  => $article->status,
            'tabs'    => $this->buildTabs(),
        ]);
    }

    // ── API: increment views ───────────────────────────────────────────────────

    /**
     * POST /admin/news/{article}/view
     */
    public function incrementView(NewsArticle $article): JsonResponse
    {
        $article->incrementViews();
        return response()->json(['views' => $article->views]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function formatArticle(NewsArticle $a): array
    {
        return [
            'id'          => $a->id,
            'title'       => $a->title,
            'slug'        => $a->slug,
            'excerpt'     => $a->excerpt,
            'content'     => $a->content,
            'category'    => $a->category,
            'author'      => $a->author_name,       // blade dùng art.author
            'emoji'       => $a->emoji,
            'read_time'   => $a->read_time,
            'readTime'    => $a->read_time,          // blade dùng art.readTime
            'views'       => $a->views,
            'pinned'      => $a->is_featured,        // blade dùng art.pinned
            'is_featured' => $a->is_featured,
            'status'      => $a->status,
            'date'        => $a->formatted_date,     // accessor dd/mm/yyyy
            'scheduled_at'=> $a->scheduled_at?->format('Y-m-d\TH:i'),
            'tags'        => $a->tags->pluck('name')->toArray(),
            'thumbnail_url' => $a->thumbnail_url,
            'color'       => $this->categoryColor($a->category),
            'icon'        => $a->emoji ?? $this->categoryIcon($a->category),
        ];
    }

    private function buildKpis(): array
    {
        $total     = NewsArticle::count();
        $published = NewsArticle::where('status', 'published')->count();
        $totalViews = NewsArticle::sum('views');

        $avgRead = NewsArticle::where('status', 'published')->avg('read_time') ?? 0;

        return [
            ['key' => 'total',  'icon' => 'newspaper',  'color' => '#6366f1', 'label' => 'Tổng bài viết',  'value' => (string) $total,                                                          'sub' => 'Toàn bộ nền tảng'],
            ['key' => 'pub',    'icon' => 'send',        'color' => '#10b981', 'label' => 'Đã xuất bản',   'value' => (string) $published,                                                      'sub' => $total ? round($published / $total * 100) . '% tổng số' : '0%'],
            ['key' => 'views',  'icon' => 'eye',         'color' => '#f59e0b', 'label' => 'Tổng lượt xem', 'value' => $totalViews >= 1000 ? round($totalViews / 1000, 1) . 'K' : $totalViews,  'sub' => '+' . NewsArticle::whereDate('created_at', '>=', now()->subWeek())->sum('views') . ' tuần này'],
            ['key' => 'avg',    'icon' => 'clock',       'color' => '#8b5cf6', 'label' => 'T.g đọc TB',    'value' => round($avgRead, 1) . ' phút',                                             'sub' => 'Theo dõi hành vi'],
        ];
    }

    private function buildTabs(): array
    {
        $all       = NewsArticle::count();
        $published = NewsArticle::where('status', 'published')->count();
        $draft     = NewsArticle::where('status', 'draft')->count();
        $scheduled = NewsArticle::where('status', 'scheduled')->count();

        return [
            ['key' => 'all',       'label' => 'Tất cả',   'count' => $all],
            ['key' => 'published', 'label' => 'Đã đăng',  'count' => $published],
            ['key' => 'draft',     'label' => 'Nháp',     'count' => $draft],
            ['key' => 'scheduled', 'label' => 'Lên lịch', 'count' => $scheduled],
        ];
    }

    private function calcReadTime(string $html): int
    {
        $words = str_word_count(strip_tags($html));
        return max(1, (int) ceil($words / 200));
    }

    private function categoryColor(string $cat): string
    {
        return match ($cat) {
            'Công nghệ'  => '#6366f1',
            'Học tập'    => '#10b981',
            'Khuyến mãi' => '#f59e0b',
            'Hướng dẫn'  => '#8b5cf6',
            'Sự kiện'    => '#ec4899',
            'Thông báo'  => '#ef4444',
            default      => '#06b6d4',
        };
    }

    private function categoryIcon(string $cat): string
    {
        return match ($cat) {
            'Công nghệ'  => 'zap',
            'Học tập'    => 'book-open',
            'Khuyến mãi' => 'tag',
            'Hướng dẫn'  => 'calendar',
            'Sự kiện'    => 'video',
            'Thông báo'  => 'bell',
            default      => 'file-text',
        };
    }
}