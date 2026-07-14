<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Đừng quên mã hóa mật khẩu
            'role' => 'admin', // Giả sử cột này là string
        ]);

        // 2. Tạo User thường
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->call(ExamSeeder::class);
        $this->call(ExamAttemptSeeder::class);
        $this->call(FlashCardSeeder::class);
        $this->call(NewsArticleSeeder::class);
        $this->call(DocumentSeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(QuestionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(WalletConfigSeeder::class);
        $this->call(TypeEventSeeder::class);
    }
}
