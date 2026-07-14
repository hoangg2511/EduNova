<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AIAgentService;
use App\Services\SupabaseService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentsController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private AIAgentService $aiAgent,
        private SupabaseService $supabase, // ✅ MỚI
    ) {
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
    // (giữ nguyên toàn bộ, không đổi)
    public function stats(): JsonResponse
    {
        $total = Document::count();

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

        $topDocs = Document::with(['uploader', 'types', 'tags'])
            ->approved()
            ->orderByDesc('downloads')
            ->limit(5)
            ->get()
            ->map(fn (Document $d) => $this->formatDoc($d));

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

    // ─── API: approve / reject / aiReview ───────────────────────────────────────
    // (giữ nguyên toàn bộ, không đổi)
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

        $document->load(['uploader', 'types', 'tags']);

        return response()->json([
            'message' => 'Đã phê duyệt: ' . $document->name,
            'doc'     => $this->formatDoc($document),
        ]);
    }

    public function reject(Request $request, Document $document): JsonResponse
    {
        if (! $document->isPending()) {
            return response()->json(['message' => 'Tài liệu không ở trạng thái chờ duyệt'], 422);
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:500']);

        $document->reject($request->user()->id, $request->input('rejection_reason'));

        $document->load(['uploader', 'types', 'tags']);

        return response()->json([
            'message' => 'Đã từ chối: ' . $document->name,
            'doc'     => $this->formatDoc($document),
        ]);
    }

    public function aiReview(Document $document): JsonResponse
    {
        if (! $document->isPending()) {
            return response()->json(['message' => 'Tài liệu không ở trạng thái chờ duyệt'], 422);
        }

        $document->load(['uploader', 'types', 'tags']);

        $decision = $this->askAiToReview($document);

        if (! $decision) {
            return response()->json([
                'message' => 'AI không phản hồi hợp lệ, vui lòng xét duyệt thủ công.',
            ], 422);
        }

        if ($decision['decision'] === 'approve') {
            $document->approve(auth()->id());
            $this->walletService->rewardDocumentApproved(
                $document->uploader,
                $document,
                $document->name
            );
        } else {
            $document->reject(auth()->id(), $decision['reason'] ?? 'AI phát hiện dấu hiệu không phù hợp.');
        }

        $document->load(['uploader', 'types', 'tags']);

        $label = $decision['decision'] === 'approve' ? 'phê duyệt' : 'từ chối';

        return response()->json([
            'message'       => "AI đã tự động {$label}: {$document->name}",
            'ai_decision'   => $decision['decision'],
            'ai_reason'     => $decision['reason'] ?? null,
            'ai_confidence' => $decision['confidence'] ?? null,
            'doc'           => $this->formatDoc($document),
        ]);
    }

    private function askAiToReview(Document $document): ?array
    {
        $type    = $document->types->first()?->name ?? 'Không rõ';
        $subject = $document->tags->first()?->name ?? 'Không rõ';

        $system = <<<PROMPT
Bạn là trợ lý kiểm duyệt tài liệu học tập cho nền tảng giáo dục EduNova.
Dựa vào thông tin (metadata) của tài liệu được cung cấp, hãy quyết định
tài liệu nên được PHÊ DUYỆT (approve) hay TỪ CHỐI (reject).

Từ chối nếu: tiêu đề/mô tả không liên quan đến học tập, mô tả quá sơ sài
hoặc rỗng, nghi ngờ vi phạm bản quyền, tên file/mô tả chứa nội dung
không phù hợp, hoặc rõ ràng là spam/quảng cáo.
Nếu không có dấu hiệu vi phạm rõ ràng, hãy phê duyệt.

CHỈ trả lời bằng đúng 1 object JSON, không thêm bất kỳ chữ nào khác,
không markdown, không giải thích ngoài JSON. Định dạng bắt buộc:
{"decision": "approve" hoặc "reject", "reason": "lý do ngắn gọn bằng tiếng Việt (tối đa 200 ký tự)", "confidence": số nguyên 0-100}
PROMPT;

        $prompt = "Tên tài liệu: {$document->name}\n"
            . "Mô tả: " . ($document->description ?: 'Không có mô tả') . "\n"
            . "Loại file: {$type}\n"
            . "Môn học: {$subject}\n"
            . "Tác giả: " . ($document->uploader?->name ?? 'Ẩn danh');

        $raw = $this->aiAgent->requestOllama($prompt, $system);

        return $this->parseAiDecision($raw);
    }

    private function parseAiDecision(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        if (! preg_match('/\{.*\}/s', $raw, $m)) {
            Log::warning('AI review: không tìm thấy JSON trong phản hồi', ['raw' => $raw]);
            return null;
        }

        $data = json_decode($m[0], true);

        if (! is_array($data) || empty($data['decision'])) {
            Log::warning('AI review: JSON không hợp lệ', ['raw' => $raw]);
            return null;
        }

        $decision = strtolower(trim((string) $data['decision']));

        if (! in_array($decision, ['approve', 'reject'], true)) {
            return null;
        }

        return [
            'decision'   => $decision,
            'reason'     => isset($data['reason']) ? mb_substr((string) $data['reason'], 0, 500) : null,
            'confidence' => isset($data['confidence']) ? (int) $data['confidence'] : null,
        ];
    }

    // ─── API: MỚI — lấy file thật qua SupabaseService ───────────────────────────

    /**
     * GET /admin/documents/{document}/file
     *
     * Không trả trực tiếp path lưu trong DB (doc.url) cho frontend — path đó
     * chỉ là đường dẫn tương đối bên trong bucket Supabase, có thể không truy
     * cập công khai được (bucket private) hoặc lộ cấu trúc lưu trữ nội bộ.
     *
     * Thay vào đó, route này gọi SupabaseService để tạo signed URL tạm thời
     * (hết hạn sau vài phút) rồi redirect (302) trình duyệt sang đó — admin
     * chỉ cần mở đúng route này, không cần biết bucket/path thật.
     */
    public function viewFile(Document $document)
    {
        if (empty($document->url)) {
            abort(404, 'Tài liệu này không có file đính kèm.');
        }

        try {
            $signedUrl = $this->supabase->getSignedUrl('documents', $document->url, 300); // hết hạn sau 5 phút
        } catch (\Throwable $e) {
            Log::error('DocumentsController::viewFile - Lỗi khi tạo signed URL', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);
            abort(500, 'Không thể tải file từ kho lưu trữ.');
        }

        // getSignedUrl() trả về chuỗi lỗi thô (response body) nếu Supabase từ chối request,
        // nên phải kiểm tra chắc chắn đó là URL hợp lệ trước khi redirect.
        if (! $signedUrl || ! str_starts_with($signedUrl, 'http')) {
            Log::error('DocumentsController::viewFile - Supabase không trả về URL hợp lệ', [
                'document_id' => $document->id,
                'response'    => $signedUrl,
            ]);
            abort(502, 'Kho lưu trữ tài liệu hiện không phản hồi. Vui lòng thử lại sau.');
        }

        return redirect()->away($signedUrl);
    }

    /**
     * POST /admin/documents/{document}/view
     * Tăng số lượt xem (khác với downloads — đây là số lần admin mở preview).
     */
    public function incrementView(Document $document): JsonResponse
    {
        $document->increment('views');

        return response()->json([
            'message' => 'Đã ghi nhận lượt xem',
            'views'   => $document->views,
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

    private function formatDoc(Document $d): array
    {
        $type    = $d->types->first()?->name;
        $subject = $d->tags->first()?->name;

        return [
            'id'               => $d->id,
            'name'             => $d->name,
            'description'      => $d->description,
            // ✅ Trỏ về route nội bộ thay vì raw path trong DB —
            //    route này sẽ tự gọi SupabaseService để lấy signed URL thật khi được mở.
            'url'              => $d->url ? route('admin.documents.viewFile', $d->id) : null,
            'author'           => $d->uploader?->name ?? 'Ẩn danh',
            'subject'          => $subject,
            'type'             => $type,
            'size'             => $d->size,
            'downloads'        => (int) ($d->downloads ?? 0),
            'views'            => (int) ($d->views ?? 0), // ✅ MỚI
            'rate'             => (float) ($d->rate ?? 0),
            'status'           => $d->status,
            'rejection_reason' => $d->rejection_reason,
            'reviewed_at'      => $this->safeFormatDate($d->reviewed_at, 'd/m/Y H:i'),
            'upload_date'      => $this->safeFormatDate($d->created_at, 'd/m/Y'),
            'icon'             => match ($type) {
                'pdf'  => 'file-text',
                'docx' => 'file',
                'pptx' => 'presentation',
                'xlsx' => 'table-2',
                default => 'file',
            },
            'color'            => match ($type) {
                'pdf'  => '#ef4444',
                'docx' => '#3b82f6',
                'pptx' => '#f59e0b',
                'xlsx' => '#10b981',
                default => '#64748b',
            },
        ];
    }

    private function safeFormatDate($value, string $format): ?string
    {
        if (empty($value)) return null;

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        $ts = strtotime((string) $value);
        return $ts ? date($format, $ts) : null;
    }

    private function buildKpis(): array
    {
        $total   = Document::count();
        $pending = Document::pending()->count();
        $totalDl = (int) Document::sum('downloads');

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

    public function toggleVisibility(Document $document): JsonResponse
    {
        if (! $document->isApproved() && ! $document->isHidden()) {
            return response()->json([
                'message' => 'Chỉ có thể ẩn/hiện tài liệu đã được phê duyệt.',
            ], 422);
        }

        $document->isHidden() ? $document->unhide() : $document->hide();

        $document->load(['uploader', 'types', 'tags']);

        $label = $document->isHidden() ? 'ẩn' : 'hiển thị';

        Log::info('Admin toggled document visibility (status)', [
            'document_id' => $document->id,
            'status'      => $document->status,
            'admin_id'    => auth()->id(),
        ]);

        return response()->json([
            'message' => "Đã chuyển tài liệu sang trạng thái {$label}: {$document->name}",
            'doc'     => $this->formatDoc($document),
        ]);
    }
}