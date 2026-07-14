<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    // ─── Page ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $kpis = $this->buildKpis();
        return view('admin.users.index', compact('kpis'));
    }

    // ─── API: list ───────────────────────────────────────────────────────────

    /**
     * GET /admin/users/data
     * Query params: search, role, plan, status, page, per_page
     */
    public function data(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 8);

        $users = User::query()
            ->search($request->input('search'))
            ->ofRole($request->input('role'))
            ->ofStatus($request->input('status'))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // 'plan' không phải cột trên bảng users (suy ra từ Subscription đang
        // active), nên phải lọc SAU khi đã format từng user.
        $planFilter = $request->input('plan');
        $items = $users->getCollection()
            ->map(fn (User $u) => $this->formatUser($u))
            ->when($planFilter, fn ($c) => $c->filter(fn ($row) => $row['plan'] === $planFilter)->values());

        return response()->json([
            'data'         => $items,
            'total'        => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
            'kpis'         => $this->buildKpis(),
        ]);
    }

    // ─── API: store ──────────────────────────────────────────────────────────

    /**
     * POST /admin/users
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(['admin', 'user'])],
            // dùng để tạo Subscription ban đầu, KHÔNG lưu vào bảng users
            'plan'     => ['required', Rule::in(['free', 'pro', 'premium'])],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'status'   => 'active',
        ]);

        if ($data['plan'] !== 'free') {
            $plan = \App\Models\Plan::where('slug', $data['plan'])->first();
            if ($plan) {
                $user->subscriptions()->create([
                    'plan_id'   => $plan->id,
                    'status'    => 'active',
                    'starts_at' => now(),
                    'ends_at'   => now()->addDays($plan->duration_days ?: 30),
                ]);
            }
        }

        return response()->json([
            'message' => 'Đã thêm người dùng!',
            'user'    => $this->formatUser($user->fresh()),
        ], 201);
    }

    // ─── API: update ─────────────────────────────────────────────────────────

    /**
     * PUT /admin/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:100',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['sometimes', Rule::in(['admin', 'user'])],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * GET /admin/users/{user}
     *
     * QUAN TRỌNG: gói hiển thị và trạng thái/ngày hết hạn phải lấy từ CÙNG
     * một Subscription (activeSubscription()) — tránh tình trạng "Gói: Free"
     * nhưng "Sắp hết hạn 02/08/2026" (2 nguồn dữ liệu khác nhau, mâu thuẫn).
     */
    public function show(User $user): JsonResponse
    {
        $subscription = $user->activeSubscription(); // null nếu không có gói trả phí đang active
        $plan         = $subscription?->plan ?? \App\Models\Plan::where('slug', 'free')->first();

        return response()->json([
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'status'   => $user->status,
                'joinDate' => $user->created_at->format('d/m/Y'),
                'plan'     => [
                    'slug'           => $plan->slug ?? 'free',
                    'name'           => $plan->name ?? 'Miễn phí',
                    'formattedPrice' => $plan?->formattedPrice() ?? 'Miễn phí',
                ],
                'subscription' => $subscription ? [
                    'status'         => $subscription->status,
                    'startsAt'       => optional($subscription->starts_at)->format('d/m/Y'),
                    'endsAt'         => optional($subscription->ends_at)->format('d/m/Y'),
                    'isExpiringSoon' => $subscription->isExpiringSoon(),
                ] : null,
            ],
        ]);
    }

    // ─── API: destroy ─────────────────────────────────────────────────────────

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'Đã xóa người dùng']);
    }

    // ─── API: toggle status ───────────────────────────────────────────────────

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update([
            'status' => $user->status === 'active' ? 'banned' : 'active',
        ]);

        $label = $user->status === 'active' ? 'Đã mở khóa tài khoản' : 'Đã khóa tài khoản';

        return response()->json([
            'message' => $label,
            'status'  => $user->status,
        ]);
    }

    // ─── API: bulk ban ────────────────────────────────────────────────────────

    public function bulkBan(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:users,id']);

        User::whereIn('id', $request->ids)->update(['status' => 'banned']);

        return response()->json(['message' => 'Đã khóa các tài khoản']);
    }

    // ─── API: bulk delete ─────────────────────────────────────────────────────

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:users,id']);

        User::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Đã xóa']);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Dùng cho danh sách (bảng) — trả 'plan' dạng string (slug) để khớp với
     * JS phía trước (u.plan.charAt(...), so sánh 'free'/'pro'/'premium').
     * Luôn lấy từ Subscription đang active, KHÔNG bao giờ đọc $u->plan
     * (cột này không tồn tại trên bảng users).
     */
    private function formatUser(User $u): array
    {
        $plan = $u->currentPlan(); // model đã tự fallback về gói Free

        return [
            'id'       => $u->id,
            'name'     => $u->name,
            'email'    => $u->email,
            'role'     => $u->role,
            'plan'     => $plan->slug ?? 'free',
            'status'   => $u->status,
            'joinDate' => $u->created_at->format('d/m/Y'),
        ];
    }

    private function buildKpis(): array
    {
        $total   = User::count();
        $active  = User::where('status', 'active')->count();
        $banned  = User::where('status', 'banned')->count();
        $newToday = User::whereDate('created_at', today())->count();
        $newYesterday = User::whereDate('created_at', today()->subDay())->count();

        return [
            ['key' => 'total',  'icon' => 'users',      'color' => '#6366f1', 'label' => 'Tổng người dùng', 'value' => number_format($total),  'sub' => '+' . User::whereBetween('created_at', [now()->startOfWeek(), now()])->count() . ' tuần này'],
            ['key' => 'active', 'icon' => 'user-check', 'color' => '#10b981', 'label' => 'Đang hoạt động',  'value' => number_format($active), 'sub' => $total ? round($active / $total * 100, 1) . '% tổng số' : '0%'],
            ['key' => 'new',    'icon' => 'user-plus',  'color' => '#f59e0b', 'label' => 'Mới hôm nay',     'value' => $newToday,              'sub' => 'so với ' . $newYesterday . ' hôm qua'],
            ['key' => 'banned', 'icon' => 'user-x',     'color' => '#ef4444', 'label' => 'Bị khóa',          'value' => $banned,                'sub' => $total ? round($banned / $total * 100, 1) . '% tổng số' : '0%'],
        ];
    }
}