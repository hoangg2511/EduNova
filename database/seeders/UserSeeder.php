<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const COLORS = [
        '#6366f1', '#10b981', '#f59e0b', '#ef4444',
        '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16',
    ];

    public function run(): void
    {
        // Giả định bạn có các bản ghi trong bảng plans
        $users = [
            [
                'name'     => 'Nguyễn Minh Khoa',
                'email'    => 'khoa@gmail.com',
                'role'     => 'student',
                'plan'     => 'premium',
                'status'   => 'active',
                'created_at' => '2025-01-12',
            ],
            [
                'name'     => 'Trần Thị Lan',
                'email'    => 'lan@gmail.com',
                'role'     => 'student',
                'plan'     => 'pro',
                'status'   => 'active',
                'created_at' => '2025-01-15',
            ],
            [
                'name'     => 'Lê Văn Hùng',
                'email'    => 'hung@gmail.com',
                'role'     => 'instructor',
                'plan'     => 'premium',
                'status'   => 'active',
                'created_at' => '2025-02-03',
            ],
            [
                'name'     => 'Phạm Thúy Ngân',
                'email'    => 'ngan@gmail.com',
                'role'     => 'student',
                'plan'     => 'free',
                'status'   => 'active',
                'created_at' => '2025-02-08',
            ],
            [
                'name'     => 'Đỗ Quốc Bảo',
                'email'    => 'bao@gmail.com',
                'role'     => 'student',
                'plan'     => 'pro',
                'status'   => 'banned',
                'created_at' => '2025-02-14',
            ],
            [
                'name'     => 'Vũ Thị Hoa',
                'email'    => 'hoa@gmail.com',
                'role'     => 'student',
                'plan'     => 'premium',
                'status'   => 'active',
                'created_at' => '2025-02-20',
            ],
            [
                'name'     => 'Ngô Văn Nam',
                'email'    => 'nam@gmail.com',
                'role'     => 'admin',
                'plan'     => 'premium',
                'status'   => 'active',
                'created_at' => '2025-01-01',
            ],
            [
                'name'     => 'Đinh Thị Mai',
                'email'    => 'mai@gmail.com',
                'role'     => 'instructor',
                'plan'     => 'pro',
                'status'   => 'active',
                'created_at' => '2025-02-25',
            ],
            [
                'name'     => 'Hoàng Văn Tùng',
                'email'    => 'tung@gmail.com',
                'role'     => 'student',
                'plan'     => 'free',
                'status'   => 'active',
                'created_at' => '2025-03-01',
            ],
            [
                'name'     => 'Bùi Thị Thu',
                'email'    => 'thu@gmail.com',
                'role'     => 'student',
                'plan'     => 'pro',
                'status'   => 'active',
                'created_at' => '2025-03-05',
            ],
        ];

        $plans = Plan::whereIn('slug', ['free', 'pro', 'premium'])
            ->get()
            ->keyBy('slug');

        foreach ($users as $data) {
            $userData = collect($data)->except(['plan'])->toArray();

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($userData, [
                    'password'   => Hash::make('password'),
                    'updated_at' => $data['created_at'],
                ])
            );

            $planKey = $data['plan'] ?? 'free';
            $plan = $plans[$planKey] ?? Plan::where('slug', $planKey)->first();

            if (! $plan) {
                continue;
            }

            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id'   => $plan->id,
                    'status'    => 'active',
                    'starts_at' => now(),
                    'ends_at'   => $plan->duration_days > 0 ? now()->addDays($plan->duration_days) : null,
                ]
            );

            UserLog::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'token_limit'     => $plan->token_limit,
                    'duration_days'   => $plan->duration_days,
                    'knowledge_limit' => $plan->knowledge_limit,
                    'download_limit'  => $plan->download_limit,
                ]
            );
        }
    }
}