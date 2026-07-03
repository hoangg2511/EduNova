<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentsController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    // ─── Page ─────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        return view('admin.documents.index');
    }

    // ─── API: pending list ─────────────────────────────────────────────────────

    public function pending(): JsonResponse
    {
        $docs = Document::with(['uploader', 'types', 'tags'])
            ->pending()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Document $d) => $this->formatDoc($d));

        return response()->json(['data' => $docs]);
    }

    // ─── API: all docs ─────────────────────────────────────────────────────────

    public function data(Request $request): JsonResponse
    {
        $docs = Document::with(['uploader', 'types', 'tags'])
            ->search($request->input('search'))
            ->ofType($request->input('type'))
            ->ofStatus($request->input('status'))
            ->ofSubject($request->input('subject'))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Document $d) => $this->formatDoc($d));

        // Danh sách subject (tag names) cho dropdown filter
        $subjects = DB::table('tags')
            ->join('document_tag', 'tags.id', '=', 'document_tag.tag_id')
            ->distinct()
            ->orderBy('tags.name')
            ->pluck('tags.name');

        return response()->json([
            'data'     => $docs,
            'kpis'     => $this->buildKpis(),
            'subjects' => $subjects,
        ]);
    }

    // ─── API: stats ────────────────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $total = Document::count();

        // Phân loại theo type (qua pivot document_type)
        $typeStats = DB::table('types')
            ->join('document_type', 'types.id', '=', 'document_type.type_id')
            ->select('types.name as type', DB::raw('count(distinct document_type.document_id) as count'))
            ->groupBy('types.name')
            ->get()
            ->map(fn ($row) => [
                'type'  => $row->type,
                'count' => (int) $row->count,
                'pct'   => $total ? round($row->count / $total * 100) : 0,
                'color' => match ($row->type) {
                    'pdf'  => '#ef4444',
                    'docx' => '#3b82f6',
                    'pptx' => '#f59e0b',
                    'xlsx' => '#10b981',
                    default => '#64748b',
                },
            ]);

        // Phân loại theo subject (tag)
        $rawSubjects = DB::table('tags')
            ->join('document_tag', 'tags.id', '=', 'document_tag.tag_id')
            ->select('tags.name as subject', DB::raw('count(distinct document_tag.document_id) as count'))
            ->groupBy('tags.name')
            ->orderByDesc('count')
            ->get();

        $maxCount = $rawSubjects->max('count') ?: 1;
        $subjectStats = $rawSubjects->map(fn ($row) => [
            'subject' => $row->subject,
            'count'   => (int) $row->count,
            'pct'     => round($row->count / $maxCount * 100),
        ]);

        // Top 5 tải nhiều nhất
        $topDocs = Document::with(['uploader', 'types', 'tags'])
            ->approved()
            ->orderByDesc('downloads')
            ->limit(5)
            ->get()
            ->map(fn (Document $d) => $this->formatDoc($d));

        // Tình trạng xét duyệt
        $counts = Document::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $approvalStats = [
            ['status' => 'approved', 'label' => 'Đã duyệt',  'count' => (int) ($counts['approved'] ?? 0), 'pct' => $total ? round(($counts['approved'] ?? 0) / $total * 100) : 0, 'color' => '#10b981'],
            ['status' => 'pending',  'label' => 'Chờ duyệt', 'count' => (int) ($counts['pending']  ?? 0), 'pct' => $total ? round(($counts['pending']  ?? 0) / $total * 100) : 0, 'color' => '#f59e0b'],
            ['status' => 'rejected', 'label' => 'Từ chối',   'count' => (int) ($counts['rejected'] ?? 0), 'pct' => $total ? round(($counts['rejected'] ?? 0) / $total * 100) : 0, 'color' => '#ef4444'],
        ];

        return response()->json(compact('typeStats', 'subjectStats', 'topDocs', 'approvalStats'));
    }

    // ─── API: approve ──────────────────────────────────────────────────────────

    public function approve(Request $request, Document $document): JsonResponse
    {
        if (! $document->isPending()) {
            return response()->json(['message' => 'Tài liệu không ở trạng thái chờ duyệt'], 422);
        }

        $document->approve($request->user()->id);
        
        $this->walletService->rewardDocumentApproved(
            $document->uploader,
            $document,
            $document->name
        );

        // Fresh + eager load để formatDoc đúng
        $document->load(['uploader', 'types', 'tags']);

        return response()->json([
            'message' => 'Đã phê duyệt: ' . $document->name,   // ← name, không phải title
            'doc'     => $this->formatDoc($document),
        ]);
    }

    // ─── API: reject ───────────────────────────────────────────────────────────

    public function reject(Request $request, Document $document): JsonResponse
    {
        if (! $document->isPending()) {
            return response()->json(['message' => 'Tài liệu không ở trạng thái chờ duyệt'], 422);
        }

        // Blade gửi key 'rejection_reason' (khớp $fillable)
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $document->reject($request->user()->id, $request->input('rejection_reason'));

        $document->load(['uploader', 'types', 'tags']);

        return response()->json([
            'message' => 'Đã từ chối: ' . $document->name,
            'doc'     => $this->formatDoc($document),
        ]);
    }

    // ─── API: delete ───────────────────────────────────────────────────────────

    public function destroy(Document $document): JsonResponse
    {
        if ($document->url && Storage::exists($document->url)) {
            Storage::delete($document->url);
        }

        $document->delete();

        return response()->json(['message' => 'Đã xóa tài liệu']);
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    /**
     * Chuyển Document sang array gửi cho Alpine.
     * Tất cả key phải khớp với những gì blade dùng:
     *   doc.name, doc.author, doc.subject, doc.type, doc.upload_date,
     *   doc.icon, doc.color, doc.url, doc.size, doc.downloads, doc.rate,
     *   doc.status, doc.rejection_reason, doc.reviewed_at
     */
    private function formatDoc(Document $d): array
    {
        // types và tags phải được eager load trước khi gọi hàm này
        $type    = $d->types->first()?->name;   // vd: 'pdf'
        $subject = $d->tags->first()?->name;    // vd: 'Toán học'

        return [
            'id'               => $d->id,
            'name'             => $d->name,                         // cột name
            'description'      => $d->description,
            'url'              => $d->url,                          // cột url ($fillable)
            'author'           => $d->uploader?->name ?? 'Ẩn danh',// uploader relationship
            'subject'          => $subject,                         // tags pivot
            'type'             => $type,                            // types pivot
            'size'             => $d->size,                         // cột size ($fillable)
            'downloads'        => (int) ($d->downloads ?? 0),       // cột downloads ($fillable)
            'rate'             => (float) ($d->rate ?? 0),          // cột rate ($fillable)
            'status'           => $d->status,
            'rejection_reason' => $d->rejection_reason,             // cột rejection_reason ($fillable)
            'reviewed_at'      => $this->safeFormatDate($d->reviewed_at, 'd/m/Y H:i'),
            'upload_date'      => $this->safeFormatDate($d->created_at, 'd/m/Y'),  // blade dùng doc.upload_date
            'icon'             => match ($type) {                   // thay thế getIconAttribute()
                'pdf'  => 'file-text',
                'docx' => 'file',
                'pptx' => 'presentation',
                'xlsx' => 'table-2',
                default => 'file',
            },
            'color'            => match ($type) {                   // thay thế getColorAttribute()
                'pdf'  => '#ef4444',
                'docx' => '#3b82f6',
                'pptx' => '#f59e0b',
                'xlsx' => '#10b981',
                default => '#64748b',
            },
        ];
    }

    /**
     * Safely format a date-like value (DateTime or string). Returns null if empty.
     */
    private function safeFormatDate($value, string $format): ?string
    {
        if (empty($value)) return null;

        // If it's a DateTime instance (Carbon), use format directly
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        // Otherwise try to parse as string timestamp
        $ts = strtotime((string) $value);
        return $ts ? date($format, $ts) : null;
    }

    private function buildKpis(): array
    {
        $total   = Document::count();
        $pending = Document::pending()->count();
        $totalDl = (int) Document::sum('downloads');

        // size lưu dạng string "4.2 MB" nên parse để tính tổng
        $totalBytes = Document::pluck('size')
            ->sum(fn ($s) => $this->parseSizeBytes($s));

        $newThisWeek = Document::whereDate('created_at', '>=', now()->subWeek())->sum('downloads');

        return [
            ['key' => 'total',    'icon' => 'files',      'color' => '#6366f1', 'label' => 'Tổng tài liệu', 'value' => number_format($total),                                              'sub' => 'Trong kho lưu trữ'],
            ['key' => 'pending',  'icon' => 'clock',      'color' => '#f59e0b', 'label' => 'Chờ duyệt',      'value' => (string) $pending,                                                  'sub' => 'Cần xét duyệt hôm nay'],
            ['key' => 'download', 'icon' => 'download',   'color' => '#10b981', 'label' => 'Tổng lượt tải', 'value' => $totalDl >= 1000 ? round($totalDl / 1000, 1) . 'K' : (string)$totalDl, 'sub' => '+' . $newThisWeek . ' tuần này'],
            ['key' => 'size',     'icon' => 'hard-drive', 'color' => '#8b5cf6', 'label' => 'Dung lượng',     'value' => $this->formatBytes($totalBytes),                                    'sub' => 'Storage tổng cộng'],
        ];
    }

    private function parseSizeBytes(?string $size): int
    {
        if (empty($size)) return 0;

        preg_match('/([\d.,]+)\s*(B|KB|MB|GB|TB)?/i', trim($size), $m);
        if (empty($m[1])) return 0;

        $val  = (float) str_replace(',', '.', $m[1]);
        $unit = strtoupper($m[2] ?? 'B');

        return match ($unit) {
            'TB' => (int) round($val * 1024 ** 4),
            'GB' => (int) round($val * 1024 ** 3),
            'MB' => (int) round($val * 1024 ** 2),
            'KB' => (int) round($val * 1024),
            default => (int) round($val),
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) return round($bytes / 1_073_741_824, 1) . ' GB';
        if ($bytes >= 1_048_576)     return round($bytes / 1_048_576, 1)     . ' MB';
        if ($bytes >= 1_024)         return round($bytes / 1_024, 1)          . ' KB';
        return $bytes . ' B';
    }
}