<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeEventSeeder extends Seeder
{
    public function run()
    {
        DB::table('type_events')->insert([
            [
                'id' => 1,
                'label' => 'Học tập',
                'key' => 'study',
                'color' => '3B82F6',
                'visible' => 1,
                'created_at' => '2026-07-02 11:51:19',
                'updated_at' => '2026-07-02 11:51:20',
            ],
            [
                'id' => 2,
                'label' => 'Thi cử',
                'key' => 'exam',
                'color' => '8B5CF6',
                'visible' => 1,
                'created_at' => '2026-07-02 11:52:11',
                'updated_at' => '2026-07-02 11:52:12',
            ],
            [
                'id' => 3,
                'label' => 'Cá nhân',
                'key' => 'personal',
                'color' => '10B981',
                'visible' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 4,
                'label' => 'Deadline',
                'key' => 'deadline',
                'color' => 'EF4444',
                'visible' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}