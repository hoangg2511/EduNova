<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NewsService
{
    private const PER_PAGE = 5;

    /**
     * Lấy danh sách bài viết đã publish, có filter & sort, phân trang.
     */
    public function getPaginated(
        string $category = 'all',
        string $sort = 'newest',
        int $page = 1
    ): LengthAwarePaginator {
        $query = NewsArticle::published();

        if ($category !== 'all') {
            $query->category($category);
        }

        match ($sort) {
            'popular' => $query->orderByDesc('views'),
            'views'   => $query->orderByDesc('views'),
            default   => $query->orderByDesc('published_at'),
        };

        return $query->paginate(self::PER_PAGE, ['*'], 'page', $page);
    }

    /**
     * Bài nổi bật (is_featured = true, mới nhất).
     */
    public function getFeatured(): ?NewsArticle
    {
        return NewsArticle::published()
            ->featured()
            ->orderByDesc('published_at')
            ->first();
    }

    /**
     * Top bài xem nhiều cho sidebar "Đang hot".
     */
    public function getTrending(int $limit = 4): Collection
    {
        return NewsArticle::published()
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'title', 'views', 'published_at', 'slug']);
    }

    /**
     * Chi tiết một bài viết theo slug + tăng view.
     */
    public function getBySlug(string $slug): NewsArticle
    {
        $article = NewsArticle::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $article->incrementViews();

        return $article;
    }

    /**
     * Bài viết liên quan (cùng category, khác id, tối đa $limit bài).
     */
    public function getRelated(NewsArticle $article, int $limit = 3): Collection
    {
        return NewsArticle::published()
            ->category($article->category)
            ->where('id', '!=', $article->id)
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * Toggle bookmark cho user hiện tại.
     * Trả về true = đã bookmark, false = đã bỏ bookmark.
     */
    public function toggleBookmark(NewsArticle $article, int $userId): bool
    {
        $exists = $article->bookmarkedBy()->where('user_id', $userId)->exists();

        if ($exists) {
            $article->bookmarkedBy()->detach($userId);
            return false;
        }

        $article->bookmarkedBy()->attach($userId);
        return true;
    }

    /**
     * Kiểm tra user đã bookmark bài viết chưa.
     */
    public function isBookmarked(NewsArticle $article, int $userId): bool
    {
        return $article->bookmarkedBy()->where('user_id', $userId)->exists();
    }

    /**
     * Danh sách bài đã bookmark của user.
     */
    public function getBookmarked(int $userId): Collection
    {
        return NewsArticle::published()
            ->whereHas('bookmarkedBy', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Map article → array JSON-safe cho frontend.
     */
    public function toArray(NewsArticle $a, int $userId = 0): array
    {
        return [
            'id'          => $a->id,
            'slug'        => $a->slug,
            'title'       => $a->title,
            'excerpt'     => $a->excerpt,
            'category'    => $a->category,
            'emoji'       => $a->emoji,
            'author'      => $a->author_name,
            'initials'    => $a->author_initials,
            'date'        => $a->formatted_date,
            'views'       => $a->formatted_views,
            'readTime'    => $a->read_time,
            'bookmarked'  => $userId ? $this->isBookmarked($a, $userId) : false,
            'detailUrl'   => route('user.news.show', $a->slug),
        ];
    }
}