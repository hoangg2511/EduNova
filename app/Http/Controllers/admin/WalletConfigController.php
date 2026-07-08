<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WalletConfigController extends Controller
{
    /**
     * Hiển thị trang giao diện quản lý config.
     */
    public function index()
    {
        return view('admin.wallet.config');
    }

    /**
     * Trả về danh sách config + KPI cho giao diện (Alpine fetch).
     */
    public function data(): JsonResponse
    {
        $configs = WalletConfig::query()->orderBy('key')->get();

        $kpis = [
            [
                'key'   => 'total',
                'label' => 'Tổng config',
                'value' => $configs->count(),
                'sub'   => 'Toàn bộ tham số hệ thống',
                'icon'  => 'settings-2',
                'color' => '#6366f1',
            ],
            [
                'key'   => 'active',
                'label' => 'Đang bật',
                'value' => $configs->where('is_active', true)->count(),
                'sub'   => 'Đang áp dụng cho hệ thống',
                'icon'  => 'check-circle',
                'color' => '#10b981',
            ],
            [
                'key'   => 'earning',
                'label' => 'Nhóm kiếm coin',
                'value' => $configs->filter(fn ($c) => str_starts_with($c->key, 'earning_rate.'))->count(),
                'sub'   => 'Quy tắc cộng coin',
                'icon'  => 'coins',
                'color' => '#f59e0b',
            ],
            [
                'key'   => 'conversion',
                'label' => 'Nhóm quy đổi',
                'value' => $configs->filter(fn ($c) => str_starts_with($c->key, 'conversion.'))->count(),
                'sub'   => 'Coin ↔ Token / Lượt tải',
                'icon'  => 'arrow-left-right',
                'color' => '#ef4444',
            ],
        ];

        return response()->json([
            'data' => $configs,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Tạo config mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key'         => 'required|string|max:255|unique:wallet_configs,key',
            'value'       => 'required|integer',
            'description' => 'nullable|string|max:500',
        ]);

        $config = WalletConfig::create([
            'key'         => $validated['key'],
            'value'       => $validated['value'],
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        $this->forgetCache($config->key);

        return response()->json([
            'message' => 'Đã tạo config mới',
            'data'    => $config,
        ], 201);
    }

    /**
     * Cập nhật config (giá trị / mô tả / trạng thái).
     */
    public function update(Request $request, WalletConfig $walletConfig): JsonResponse
    {
        $validated = $request->validate([
            'value'       => 'sometimes|integer',
            'description' => 'sometimes|nullable|string|max:500',
            'is_active'   => 'sometimes|boolean',
        ]);

        $walletConfig->update($validated);
        $this->forgetCache($walletConfig->key);

        return response()->json([
            'message' => 'Đã cập nhật config',
            'data'    => $walletConfig->fresh(),
        ]);
    }

    /**
     * Xóa config.
     */
    public function destroy(WalletConfig $walletConfig): JsonResponse
    {
        $key = $walletConfig->key;
        $walletConfig->delete();
        $this->forgetCache($key);

        return response()->json([
            'message' => 'Đã xóa config',
        ]);
    }

    private function forgetCache(string $key): void
    {
        Cache::forget('wallet_config:' . $key);
    }
}