<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function __construct(private readonly NewsService $newsService) {}

    /**
     * GET /news  — Trang danh sách
     */
    public function index(): \Illuminate\View\View
    {
        $featured  = $this->newsService->getFeatured();
        $trending  = $this->newsService->getTrending(4);
        $userId    = auth()->id() ?? 0;

        return view('user.news.index', compact('featured', 'trending', 'userId'));
    }

    /**
     * GET /news/feed?category=&sort=&page=  — AJAX infinite scroll
     */
    public function feed(Request $request): JsonResponse
    {
        $category = $request->input('category', 'all');
        $sort     = $request->input('sort', 'newest');
        $page     = (int) $request->input('page', 1);
        $userId   = auth()->id() ?? 0;

        $paginator = $this->newsService->getPaginated($category, $sort, $page);

        $items = $paginator->getCollection()
            ->map(fn ($a) => $this->newsService->toArray($a, $userId))
            ->values();

        return response()->json([
            'data'     => $items,
            'hasMore'  => $paginator->hasMorePages(),
            'total'    => $paginator->total(),
            'page'     => $page,
        ]);
    }

    /**
     * GET /news/{slug}  — Trang chi tiết
     */
    public function show(string $slug): \Illuminate\View\View
    {
        $article  = $this->newsService->getBySlug($slug);
        $related  = $this->newsService->getRelated($article, 3);
        $userId   = auth()->id() ?? 0;
        $isBookmarked = $userId ? $this->newsService->isBookmarked($article, $userId) : false;

        return view('user.news.show', compact('article', 'related', 'isBookmarked'));
    }

    /**
     * POST /news/{id}/bookmark  — Toggle bookmark (auth required)
     */
    public function bookmark(NewsArticle $article): JsonResponse
    {
        $bookmarked = $this->newsService->toggleBookmark($article, auth()->id());

        return response()->json([
            'bookmarked' => $bookmarked,
            'message'    => $bookmarked ? 'Đã lưu bài viết!' : 'Đã bỏ lưu bài viết',
        ]);
    }
}