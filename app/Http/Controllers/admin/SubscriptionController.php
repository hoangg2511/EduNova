<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    private const AVATAR_COLORS = [
        '#6366f1','#10b981','#f59e0b','#ef4444',
        '#8b5cf6','#06b6d4','#ec4899','#84cc16',
    ];

    public function index(): \Illuminate\View\View
    {
        return view('admin.subscriptions.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'plans'         => $this->formatPlans(),
            'subscriptions' => $this->formatSubscriptions(),
            'kpis'          => $this->buildKpis(),
            'revenueChart'  => $this->buildRevenueChart(),
            'churnStats'    => $this->buildChurnStats(),
        ]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'slug'            => 'required|string|max:50|unique:plans,slug',
            'description'     => 'nullable|string|max:500',
            'price'           => 'required|integer|min:0',
            'duration_days'   => 'required|integer|min:0',
            'token_limit'     => 'required|integer|min:0',
            'knowledge_limit' => 'required|integer|min:0',
            'download_limit'  => 'required|integer|min:0',
            'features'        => 'array',
            'features.*'      => 'string|max:200',
            'color'           => 'required|string|max:20',
            'is_featured'     => 'boolean',
            'is_active'       => 'boolean',
        ]);

        // Filter empty features — lưu array thuần, model cast tự json_encode
        $data['features'] = array_values(array_filter($data['features'] ?? [], fn($f) => trim($f)));

        $plan = Plan::create($data);

        return response()->json([
            'message' => 'Đã tạo gói ' . $plan->name,
            'plan'    => $this->formatPlan($plan->loadCount('activeSubscriptions')),
        ], 201);
    }

    public function updatePlan(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'sometimes|required|string|max:100',
            'description'     => 'nullable|string|max:500',
            'price'           => 'sometimes|required|integer|min:0',
            'duration_days'   => 'sometimes|required|integer|min:0',
            'token_limit'     => 'sometimes|required|integer|min:0',
            'knowledge_limit' => 'sometimes|required|integer|min:0',
            'download_limit'  => 'sometimes|required|integer|min:0',
            'features'        => 'array',
            'features.*'      => 'string|max:200',
            'color'           => 'sometimes|required|string|max:20',
            'is_featured'     => 'boolean',
            'is_active'       => 'boolean',
            'apply_now'       => 'boolean', // ← mới: có đồng bộ ngay cho học viên đang active không
        ]);

        // apply_now chỉ là cờ hành động một lần, không phải cột của bảng plans
        $applyNow = (bool) ($data['apply_now'] ?? false);
        unset($data['apply_now']);

        if (isset($data['features'])) {
            $data['features'] = array_values(array_filter($data['features'], fn($f) => trim($f)));
        }

        $plan->update($data);
        $plan->refresh();

        $affected = 0;
        if ($applyNow) {
            $affected = $this->syncActiveSubscribersToPlan($plan);
        }

        return response()->json([
            'message' => $applyNow
                ? "Đã cập nhật gói {$plan->name} và áp dụng ngay cho {$affected} học viên đang dùng"
                : "Đã cập nhật gói {$plan->name}",
            'plan'    => $this->formatPlan($plan->loadCount('activeSubscriptions')),
        ]);
    }

    /**
     * Đồng bộ lại hạn mức (token/knowledge/download/duration) trong UserLog
     * cho tất cả user đang có subscription 'active' với plan này,
     * để thay đổi có hiệu lực ngay thay vì chờ lần gia hạn kế tiếp.
     */
    private function syncActiveSubscribersToPlan(Plan $plan): int
    {
        $activeSubs = $plan->activeSubscriptions()->get();

        foreach ($activeSubs as $sub) {
            UserLog::updateOrCreate(
                ['user_id' => $sub->user_id],
                [
                    'token_limit'     => $plan->token_limit,
                    'knowledge_limit' => $plan->knowledge_limit,
                    'download_limit'  => $plan->download_limit,
                    'duration_days'   => $plan->duration_days,
                ]
            );
        }

        return $activeSubs->count();
    }

    public function togglePlanActive(Plan $plan): JsonResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return response()->json([
            'message'   => $plan->is_active ? "Đã bật gói {$plan->name}" : "Đã tắt gói {$plan->name}",
            'is_active' => $plan->is_active,
        ]);
    }

    public function grant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_email'     => 'required|email|exists:users,email',
            'plan_slug'      => 'required|string|exists:plans,slug',
            'starts_at'      => 'required|date',
            'ends_at'        => 'nullable|date|after:starts_at',
            'payment_method' => ['required', Rule::in(['sepay', 'bank', 'admin'])],
            'transaction_id' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $data['user_email'])->firstOrFail();
        $plan = Plan::where('slug', $data['plan_slug'])->firstOrFail();

        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $sub = Subscription::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'status'         => 'active',
            'starts_at'      => $data['starts_at'],
            'ends_at'        => $data['ends_at'] ?? null,
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
        ]);

        $sub->load(['user', 'plan']);

        return response()->json([
            'message'      => "Đã cấp gói {$plan->name} cho {$user->name}",
            'subscription' => $this->formatSub($sub),
            'plans'        => $this->formatPlans(),
        ]);
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        $subscription->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Đã hủy subscription',
            'status'  => 'cancelled',
            'plans'   => $this->formatPlans(),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function formatPlans(): array
    {
        return Plan::withCount(['activeSubscriptions as subscriber_count'])
            ->orderBy('price')
            ->get()
            ->map(fn (Plan $p) => $this->formatPlan($p))
            ->toArray();
    }

    /**
     * FIX CHÍNH: features luôn được decode thành array trước khi trả về.
     *
     * Vấn đề: DB lưu JSON string, model cast 'array' đôi khi trả về string
     * nếu dữ liệu được insert bằng raw SQL hoặc json_encode() thủ công trong seeder.
     * Alpine nhận string rồi iterate từng ký tự → hiển thị sai.
     */
    private function formatPlan(Plan $p): array
    {
        // Decode features an toàn: dù DB lưu string hay model cast ra array đều xử lý được
        $features = $p->getRawOriginal('features') ?? $p->features;

        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        if (!is_array($features)) {
            $features = [];
        }

        // Lọc bỏ phần tử rỗng
        $features = array_values(array_filter($features, fn($f) => is_string($f) && trim($f) !== ''));

        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'slug'             => $p->slug,
            'description'      => $p->description,
            'price'            => (int) $p->price,
            'duration_days'    => (int) $p->duration_days,
            'token_limit'      => (int) $p->token_limit,
            'knowledge_limit'  => (int) $p->knowledge_limit,
            'download_limit'   => (int) $p->download_limit,
            'features'         => $features,                 // ← luôn là PHP array → JSON array
            'color'            => $p->color ?? '#94a3b8',
            'is_featured'      => (bool) $p->is_featured,
            'is_active'        => (bool) $p->is_active,
            'subscriber_count' => (int) ($p->subscriber_count ?? 0),
        ];
    }

    private function formatSubscriptions(): array
    {
        return Subscription::with(['user', 'plan'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Subscription $s) => $this->formatSub($s))
            ->toArray();
    }

    private function formatSub(Subscription $s): array
    {
        $color = $s->user->color
            ?? self::AVATAR_COLORS[$s->user_id % count(self::AVATAR_COLORS)];

        return [
            'id'             => $s->id,
            'user_name'      => $s->user->name,
            'user_email'     => $s->user->email,
            'color'          => $color,
            'plan_slug'      => $s->plan->slug,
            'status'         => $s->status,
            'starts_at'      => $s->starts_at?->format('d/m/Y'),
            'ends_at'        => $s->ends_at?->format('d/m/Y'),
            'ends_at_raw'    => $s->ends_at?->toDateString(),
            'payment_method' => $s->payment_method,
            'transaction_id' => $s->transaction_id,
        ];
    }

    private function buildKpis(): array
    {
        $totalRevenue = Plan::withCount(['activeSubscriptions as subscriber_count'])
            ->get()
            ->sum(fn ($p) => $p->price * $p->subscriber_count);

        $activeSubs   = Subscription::where('status', 'active')->count();
        $newThisMonth = Subscription::where('status', 'active')
            ->whereMonth('starts_at', now()->month)
            ->count();

        $recentCancelled = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        $churnBase = $activeSubs + $recentCancelled;
        $churnRate = $churnBase > 0 ? round($recentCancelled / $churnBase * 100, 1) : 0;

        return [
            ['key' => 'revenue',     'icon' => 'trending-up',  'color' => '#6366f1', 'label' => 'Doanh thu tháng này',   'value' => $this->formatRevenue($totalRevenue),  'delta' => 15,   'spark' => [40,50,45,65,70,80,90]],
            ['key' => 'active_subs', 'icon' => 'users',        'color' => '#10b981', 'label' => 'Subscription active',   'value' => number_format($activeSubs),           'delta' => 8,    'spark' => [60,65,70,68,75,80,85]],
            ['key' => 'new_subs',    'icon' => 'user-plus',    'color' => '#f59e0b', 'label' => 'Mới tháng này',         'value' => (string) $newThisMonth,               'delta' => 12,   'spark' => [30,45,40,55,50,65,70]],
            ['key' => 'churn',       'icon' => 'user-minus',   'color' => '#ef4444', 'label' => 'Churn rate',            'value' => $churnRate . '%',                     'delta' => -0.8, 'spark' => [80,75,70,65,68,60,55]],
        ];
    }

    private function buildRevenueChart(): array
    {
        $rows = Subscription::select(
                DB::raw('YEAR(starts_at) as y'),
                DB::raw('MONTH(starts_at) as m'),
                DB::raw('SUM(plans.price) as total')
            )
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.starts_at', '>=', now()->subMonths(5)->startOfMonth())
            ->whereIn('subscriptions.status', ['active', 'expired'])
            ->groupBy('y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get()
            ->keyBy(fn ($r) => $r->y . '-' . $r->m);

        $result = [];
        $max    = 1;
        for ($i = 5; $i >= 0; $i--) {
            $dt       = now()->subMonths($i);
            $key      = $dt->year . '-' . $dt->month;
            $val      = (int) ($rows[$key]->total ?? 0);
            $result[] = ['month' => 'T' . $dt->month, 'raw' => $val];
            $max      = max($max, $val);
        }

        return array_map(fn ($r) => [
            'month'     => $r['month'],
            'label_val' => $this->formatRevenue($r['raw']),
            'pct'       => $max > 0 ? round($r['raw'] / $max * 100) : 0,
        ], $result);
    }

    private function buildChurnStats(): array
    {
        $renewed  = Subscription::where('status', 'active')
            ->where('starts_at', '>=', now()->subDays(30))->count();
        $expired  = Subscription::where('status', 'expired')
            ->where('updated_at', '>=', now()->subDays(30))->count();
        $renewRate = ($renewed + $expired) > 0
            ? round($renewed / ($renewed + $expired) * 100, 1) : 0;

        $cancelled = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subDays(30))->count();
        $base      = Subscription::where('status', 'active')->count() + $cancelled;
        $churnRate = $base > 0 ? round($cancelled / $base * 100, 1) : 0;

        $upgrades = Subscription::where('payment_method', 'admin')
            ->where('created_at', '>=', now()->subDays(30))->count();

        return [
            ['key' => 'renewal', 'icon' => 'refresh-cw',     'color' => '#10b981', 'label' => 'Tỉ lệ gia hạn', 'value' => $renewRate . '%',   'sub' => 'Trong 30 ngày qua'],
            ['key' => 'churn',   'icon' => 'user-minus',      'color' => '#ef4444', 'label' => 'Tỉ lệ rời bỏ',  'value' => $churnRate . '%',   'sub' => 'Trong 30 ngày qua'],
            ['key' => 'upgrade', 'icon' => 'arrow-up-circle', 'color' => '#6366f1', 'label' => 'Nâng cấp gói',  'value' => (string) $upgrades, 'sub' => 'Free → Pro/Premium'],
        ];
    }

    private function formatRevenue(int $n): string
    {
        if ($n >= 1_000_000) return number_format($n / 1_000_000, 1) . 'M ₫';
        if ($n >= 1_000)     return number_format($n / 1_000, 0) . 'K ₫';
        return $n . ' ₫';
    }
}