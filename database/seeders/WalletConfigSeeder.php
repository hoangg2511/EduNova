<?php

namespace Database\Seeders;

use App\Models\WalletConfig;
use Illuminate\Database\Seeder;

class WalletConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'key'         => 'earning_rate.document_upload',
                'value'       => 50,
                'description' => 'Số coin nhận được khi tài liệu upload được duyệt (approved)',
            ],
            [
                'key'         => 'spending_rate.document_download',
                'value'       => 5,
                'description' => 'Số coin bị trừ mỗi lượt tải tài liệu',
            ],
            [
                'key'         => 'earning_rate.daily_checkin',
                'value'       => 10,
                'description' => 'Số coin nhận được khi điểm danh hàng ngày',
            ],
            [
                'key'         => 'daily_limit.earn',
                'value'       => 200,
                'description' => 'Giới hạn tối đa coin có thể kiếm được mỗi ngày (chống spam)',
            ],
        ];

        foreach ($configs as $c) {
            WalletConfig::updateOrCreate(['key' => $c['key']], $c);
        }
    }
}