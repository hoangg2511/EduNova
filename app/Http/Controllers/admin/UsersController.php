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
    private const COLORS = [
        '#6366f1', '#10b981', '#f59e0b', '#ef4444',
        '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16',
    ];

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
            ->ofPlan($request->input('plan'))
            ->ofStatus($request->input('status'))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data'         => $users->map(fn (User $u) => $this->formatUser($u)),
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
            'role'     => ['required', Rule::in(['admin', 'student', 'instructor'])],
            'plan'     => ['required', Rule::in(['free', 'basic', 'premium'])],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'progress' => 0,
            'status'   => 'active',
            'color'    => self::COLORS[array_rand(self::COLORS)],
        ]);

        return response()->json([
            'message' => 'Đã thêm người dùng!',
            'user'    => $this->formatUser($user),
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
            'role'  => ['sometimes', Rule::in(['admin', 'student', 'instructor'])],
            'plan'  => ['sometimes', Rule::in(['free', 'basic', 'premium'])],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Cập nhật thành công!',
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    // ─── API: destroy ─────────────────────────────────────────────────────────

    /**
     * DELETE /admin/users/{user}
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'Đã xóa người dùng']);
    }

    
    // ─── API: toggle status ───────────────────────────────────────────────────

    /**
     * PATCH /admin/users/{user}/toggle-status
     */
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

    /**
     * POST /admin/users/bulk-ban
     * Body: { ids: [1, 2, 3] }
     */
    public function bulkBan(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:users,id']);

        User::whereIn('id', $request->ids)->update(['status' => 'banned']);

        return response()->json(['message' => 'Đã khóa các tài khoản']);
    }

    // ─── API: bulk delete ─────────────────────────────────────────────────────

    /**
     * POST /admin/users/bulk-delete
     * Body: { ids: [1, 2, 3] }
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:users,id']);

        User::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Đã xóa']);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function formatUser(User $u): array
    {
        return [
            'id'       => $u->id,
            'name'     => $u->name,
            'email'    => $u->email,
            'role'     => $u->role,
            'plan'     => $u->plan,
            'progress' => $u->progress,
            'status'   => $u->status,
            'color'    => $u->color,
            'joinDate' => $u->join_date,   // accessor dd/mm/yyyy
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