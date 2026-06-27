<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // ── Plans ─────────────────────────────────────────────────────────
        // QUAN TRỌNG: dùng Plan::updateOrCreate() thay vì Plan::insert()
        // để model cast 'features' => 'array' tự động json_encode khi lưu
        // → tránh double-encode và đảm bảo DB luôn lưu đúng JSON
        
        $plans = [
            [
                'name'            => 'Miễn phí',
                'slug'            => 'free',
                'key'             => 'free',
                'description'     => 'Gói cơ bản cho người mới bắt đầu',
                'price'           => 0,
                'duration_days'   => 0,
                'token_limit'     => 10000,
                'knowledge_limit' => 5,
                'download_limit'  => 3,
                // ← truyền PHP array, KHÔNG json_encode() thủ công
                'features'        => ['5 bài kiến thức', '3 tải xuống/tháng', 'FlashCards cơ bản'],
                'color'           => '#94a3b8',
                'is_featured'     => false,
                'is_active'       => true,
            ],
            [
                'name'            => 'Pro',
                'slug'            => 'pro',
                'key'             => 'pro',
                'description'     => 'Dành cho học viên học tập nghiêm túc',
                'price'           => 99000,
                'duration_days'   => 30,
                'token_limit'     => 100000,
                'knowledge_limit' => 50,
                'download_limit'  => 50,
                'features'        => ['50 bài kiến thức', '50 tải xuống/tháng', 'FlashCards nâng cao', 'Ôn tập AI'],
                'color'           => '#6366f1',
                'is_featured'     => true,
                'is_active'       => true,
            ],
            [
                'name'            => 'Premium',
                'slug'            => 'premium',
                'key'             => 'premium',
                'description'     => 'Không giới hạn, toàn bộ tính năng',
                'price'           => 199000,
                'duration_days'   => 30,
                'token_limit'     => 0,
                'knowledge_limit' => 0,
                'download_limit'  => 0,
                'features'        => ['Không giới hạn', 'Ưu tiên hỗ trợ', 'Tất cả tính năng Pro', 'Export PDF/Excel'],
                'color'           => '#8b5cf6',
                'is_featured'     => false,
                'is_active'       => true,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // ── Fix dữ liệu cũ đã lưu sai (nếu có) ──────────────────────────
        // Nếu DB đang có bản ghi với features bị double-encode như:
        // "[\"5 b\\u00e0i ki\\u1ebfn th\\u1ee9c\",...]"
        // thì chạy đoạn này một lần để fix
        Plan::all()->each(function (Plan $plan) {
            $raw = $plan->getRawOriginal('features');
            if (!is_string($raw)) return;

            // Thử decode lần 1
            $decoded = json_decode($raw, true);

            // Nếu decode ra string (double-encoded), decode thêm lần nữa
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            // Nếu vẫn không ra array thì bỏ qua
            if (!is_array($decoded)) return;

            // Lưu lại đúng — model cast sẽ json_encode một lần
            $plan->features = $decoded;
            $plan->saveQuietly();
        });
        

        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            $usersWithoutPlan = User::whereDoesntHave('subscriptions')->get();
            foreach ($usersWithoutPlan as $user) {
                Subscription::updateOrCreate(
                    ['user_id' => $user->id], // Đảm bảo mỗi user chỉ có 1 bản ghi mặc định
                    [
                        'plan_id'        => $freePlan->id,
                        'status'         => 'active',
                        'starts_at'      => now(),
                        'payment_method' => 'admin',
                    ]
                );
            }
        }

        
        // ── Sample subscriptions ──────────────────────────────────────────
        $sampleSubs = [
            ['email' => 'khoa@gmail.com', 'slug' => 'premium', 'status' => 'active',    'starts' => '2025-05-01', 'ends' => '2025-06-01', 'method' => 'sepay', 'txn' => 'TXN-8821A'],
            ['email' => 'lan@gmail.com',  'slug' => 'pro',     'status' => 'active',    'starts' => '2025-05-15', 'ends' => '2025-06-15', 'method' => 'bank',  'txn' => 'TXN-7743B'],
            ['email' => 'hung@gmail.com', 'slug' => 'premium', 'status' => 'active',    'starts' => '2025-05-10', 'ends' => '2025-06-03', 'method' => 'sepay', 'txn' => 'TXN-6612C'],
            ['email' => 'ngan@gmail.com', 'slug' => 'pro',     'status' => 'expired',   'starts' => '2025-04-01', 'ends' => '2025-05-01', 'method' => 'sepay', 'txn' => 'TXN-5501D'],
            ['email' => 'bao@gmail.com',  'slug' => 'pro',     'status' => 'cancelled', 'starts' => '2025-04-20', 'ends' => '2025-05-20', 'method' => 'admin', 'txn' => null],
            ['email' => 'hoa@gmail.com',  'slug' => 'premium', 'status' => 'active',    'starts' => '2025-05-05', 'ends' => '2025-06-04', 'method' => 'bank',  'txn' => 'TXN-4490E'],
            ['email' => 'tung@gmail.com', 'slug' => 'pro',     'status' => 'active',    'starts' => '2025-05-20', 'ends' => '2025-06-20', 'method' => 'sepay', 'txn' => 'TXN-3381F'],
        ];

        foreach ($sampleSubs as $s) {
            $user = User::where('email', $s['email'])->first();
            $plan = Plan::where('slug', $s['slug'])->first();
            if (! $user || ! $plan) continue;

            Subscription::updateOrCreate(
                ['user_id' => $user->id, 'plan_id' => $plan->id],
                [
                    'status'         => $s['status'],
                    'starts_at'      => $s['starts'],
                    'ends_at'        => $s['ends'],
                    'payment_method' => $s['method'],
                    'transaction_id' => $s['txn'],
                ]
            );
        }
    }
}