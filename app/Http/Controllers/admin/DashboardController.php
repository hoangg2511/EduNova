<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Subscription;
use App\Models\Upload;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        Log::info('Admin accessed dashboard', ['admin_id' => auth()->id()]);

        // ── Người dùng ─────────────────────────────────────────────
        $totalUsers       = User::count();
        $newUsersToday    = User::whereDate('created_at', Carbon::today())->count();
        $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // ── Doanh thu ──────────────────────────────────────────────
        // Không có model Payment riêng: doanh thu = giá gói (Plan.price) tại
        // thời điểm phát sinh Subscription của các gói trả phí (price > 0).
        $revenueThisMonth = Subscription::join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('plans.price', '>', 0)
            ->whereMonth('subscriptions.created_at', now()->month)
            ->whereYear('subscriptions.created_at', now()->year)
            ->sum('plans.price');

        $revenueAllTime = Subscription::join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('plans.price', '>', 0)
            ->sum('plans.price');

        // Doanh thu 7 ngày gần nhất, dùng để vẽ biểu đồ cột đơn giản
        $dailyRevenue = collect(range(6, 0))->map(function ($daysAgo) {
            $date   = Carbon::today()->subDays($daysAgo);
            $amount = Subscription::join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('plans.price', '>', 0)
                ->whereDate('subscriptions.created_at', $date)
                ->sum('plans.price');

            return [
                'label'  => $date->format('d/m'),
                'amount' => (float) $amount,
            ];
        });
        $maxDailyRevenue = max($dailyRevenue->max('amount'), 1); // tránh chia cho 0

        // ── Tài liệu ───────────────────────────────────────────────
        $totalDocuments        = Document::count();
        $pendingDocumentsCount = Document::pending()->count();
        $totalUploads          = Upload::count();

        // ── Bài thi ────────────────────────────────────────────────
        $totalExams        = Exam::count();
        $examAttemptsToday = ExamAttempt::whereDate('created_at', Carbon::today())->count();
        $totalExamAttempts = ExamAttempt::count();

        // ── Gói đăng ký ────────────────────────────────────────────
        $activeSubscriptionsCount = Subscription::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->count();

        $expiringSoonCount = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(3)])
            ->count();

        // ── Coin / Ví ──────────────────────────────────────────────
        // amount lưu có dấu (+earn/-spend), dùng scope sẵn có trên model
        $coinsIssuedToday = (int) WalletTransaction::earn()->today()->sum('amount');
        $coinsSpentToday  = (int) abs(WalletTransaction::spend()->today()->sum('amount'));

        // ── Danh sách gần đây ──────────────────────────────────────
        $recentUsers = User::latest()->take(5)->get();

        $pendingDocuments = Document::pending()
            ->with('uploader')
            ->latest()
            ->take(5)
            ->get();

        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->whereHas('plan', fn ($q) => $q->where('price', '>', 0))
            ->latest()
            ->take(5)
            ->get();

        $recentExamAttempts = ExamAttempt::with('exam.user')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalUsers',
            'newUsersToday',
            'newUsersThisWeek',
            'revenueThisMonth',
            'revenueAllTime',
            'dailyRevenue',
            'maxDailyRevenue',
            'totalDocuments',
            'pendingDocumentsCount',
            'totalUploads',
            'totalExams',
            'examAttemptsToday',
            'totalExamAttempts',
            'activeSubscriptionsCount',
            'expiringSoonCount',
            'coinsIssuedToday',
            'coinsSpentToday',
            'recentUsers',
            'pendingDocuments',
            'recentSubscriptions',
            'recentExamAttempts',
        ));
    }
}