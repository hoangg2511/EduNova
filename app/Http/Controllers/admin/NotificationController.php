<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    // Chỉ quản lý thông báo type = system
    private const TYPE = 'system';

    // ── Page ──────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        return view('admin.notifications.index');
    }

    // ── API: list ─────────────────────────────────────────────────────────────

    /**
     * GET /admin/notifications/data
     * Query: filter (all|unread|read), search, page, per_page
     */
    public function data(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $filter  = $request->input('filter', 'all');
        $search  = $request->input('search', '');

        $query = Notification::with('user')
            ->where('type', self::TYPE)          // ← chỉ lấy system
            ->when($filter === 'unread', fn ($q) => $q->unread())
            ->when($filter === 'read',   fn ($q) => $q->whereNotNull('read_at'))
            ->when($search, fn ($q) => $q->where(fn ($s) =>
                $s->where('title', 'like', "%{$search}%")
                  ->orWhere('body',  'like', "%{$search}%")
            ))
            ->orderByRaw('read_at IS NOT NULL ASC')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $total  = Notification::where('type', self::TYPE)->count();
        $unread = Notification::where('type', self::TYPE)->unread()->count();

        return response()->json([
            'data'         => $query->map(fn ($n) => $this->formatNotification($n)),
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'kpis'         => $this->buildKpis($total, $unread),
        ]);
    }

    // ── API: broadcast ────────────────────────────────────────────────────────

    /**
     * POST /admin/notifications/broadcast
     * Type luôn là 'system', không cần client gửi lên
     */
    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'body'        => 'required|string|max:1000',
            'target'      => ['required', Rule::in(['all', 'role', 'user'])],
            'target_role' => 'required_if:target,role|nullable|string|in:student,instructor,admin',
            'target_user' => 'required_if:target,user|nullable|email|exists:users,email',
            'data'        => 'nullable|array',
        ]);

        $userIds = match ($data['target']) {
            'all'  => User::pluck('id'),
            'role' => User::where('role', $data['target_role'])->pluck('id'),
            'user' => User::where('email', $data['target_user'])->pluck('id'),
        };

        $rows = $userIds->map(fn ($uid) => [
            'user_id'    => $uid,
            'type'       => self::TYPE,          // ← luôn là system
            'title'      => $data['title'],
            'body'       => $data['body'],
            'data'       => json_encode($data['data'] ?? []),
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Notification::insert($rows);

        return response()->json([
            'message' => "Đã gửi thông báo hệ thống đến {$userIds->count()} người dùng",
            'count'   => $userIds->count(),
        ], 201);
    }

    // ── API: mark read ────────────────────────────────────────────────────────

    public function markRead(Notification $notification): JsonResponse
    {
        abort_if($notification->type !== self::TYPE, 403);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Đã đánh dấu đã đọc',
            'read_at' => $notification->read_at?->format('d/m/Y H:i'),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $count = Notification::where('type', self::TYPE)->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "Đã đánh dấu đã đọc {$count} thông báo",
            'count'   => $count,
        ]);
    }

    // ── API: delete ───────────────────────────────────────────────────────────

    public function destroy(Notification $notification): JsonResponse
    {
        abort_if($notification->type !== self::TYPE, 403);

        $notification->delete();

        return response()->json(['message' => 'Đã xóa thông báo']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        Notification::where('type', self::TYPE)
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json(['message' => 'Đã xóa ' . count($request->ids) . ' thông báo']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formatNotification(Notification $n): array
    {
        return [
            'id'         => $n->id,
            'title'      => $n->title,
            'body'       => $n->body,
            'data'       => $n->data ?? [],
            'is_read'    => $n->isRead(),
            'read_at'    => $n->read_at?->format('d/m/Y H:i'),
            'created_at' => $n->created_at->format('d/m/Y H:i'),
            'time_ago'   => $n->created_at->diffForHumans(),
            'user'       => $n->user ? [
                'id'    => $n->user->id,
                'name'  => $n->user->name,
                'email' => $n->user->email,
                'color' => $n->user->color ?? $this->avatarColor($n->user->id),
            ] : null,
        ];
    }

    private function buildKpis(int $total, int $unread): array
    {
        $todayCount = Notification::where('type', self::TYPE)
            ->whereDate('created_at', today())->count();
        $readRate = $total > 0 ? round(($total - $unread) / $total * 100) : 0;

        return [
            ['key' => 'total',     'icon' => 'bell',         'color' => '#6366f1', 'label' => 'Tổng thông báo', 'value' => number_format($total),  'sub' => 'Thông báo hệ thống'],
            ['key' => 'unread',    'icon' => 'bell-ring',    'color' => '#f59e0b', 'label' => 'Chưa đọc',       'value' => (string) $unread,        'sub' => 'Cần xử lý'],
            ['key' => 'today',     'icon' => 'zap',          'color' => '#10b981', 'label' => 'Gửi hôm nay',    'value' => (string) $todayCount,    'sub' => today()->format('d/m/Y')],
            ['key' => 'read_rate', 'icon' => 'check-circle', 'color' => '#8b5cf6', 'label' => 'Tỉ lệ đã đọc',  'value' => $readRate . '%',         'sub' => 'Tất cả thông báo'],
        ];
    }

    private function avatarColor(int $id): string
    {
        $colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];
        return $colors[$id % count($colors)];
    }
}