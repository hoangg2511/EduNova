<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Database\Seeder;

class FlashCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        // Deck 1: IELTS Vocabulary
        $deck1 = Deck::create([
            'user_id' => $user->id,
            'name' => 'Từ vựng IELTS',
            'subject' => 'Tiếng Anh',
            'description' => 'Bộ từ vựng IELTS Band 6.5–7.5',
            'color' => '#6366f1',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        Card::create([
            'deck_id' => $deck1->id,
            'front' => 'Ubiquitous',
            'back' => 'Có mặt ở khắp nơi, phổ biến rộng rãi',
            'difficulty' => 'hard',
            'status' => 'learning',
            'starred' => true,
            'review_count' => 3,
            'flipped' => false,
            'hint' => 'Adj – everywhere at the same time',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        Card::create([
            'deck_id' => $deck1->id,
            'front' => 'Mitigate',
            'back' => 'Làm giảm nhẹ, giảm thiểu (tác hại)',
            'difficulty' => 'medium',
            'status' => 'new',
            'starred' => false,
            'review_count' => 0,
            'flipped' => false,
            'hint' => 'Verb',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        Card::create([
            'deck_id' => $deck1->id,
            'front' => 'Pragmatic',
            'back' => 'Thực dụng, thực tế',
            'difficulty' => 'easy',
            'status' => 'learned',
            'starred' => false,
            'review_count' => 6,
            'flipped' => false,
            'hint' => 'Adj',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Deck 2: Calculus
        $deck2 = Deck::create([
            'user_id' => $user->id,
            'name' => 'Toán Giải Tích',
            'subject' => 'Toán học',
            'description' => 'Giới hạn, đạo hàm, tích phân',
            'color' => '#f97316',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        Card::create([
            'deck_id' => $deck2->id,
            'front' => 'Đạo hàm của sin(x)',
            'back' => 'cos(x)',
            'difficulty' => 'easy',
            'status' => 'learned',
            'starred' => false,
            'review_count' => 10,
            'flipped' => false,
            'hint' => '',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        Card::create([
            'deck_id' => $deck2->id,
            'front' => '∫ x² dx',
            'back' => 'x³/3 + C',
            'difficulty' => 'medium',
            'status' => 'learning',
            'starred' => true,
            'review_count' => 4,
            'flipped' => false,
            'hint' => 'Tích phân bất định',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Deck 3: Vietnamese History
        $deck3 = Deck::create([
            'user_id' => $user->id,
            'name' => 'Lịch sử Việt Nam',
            'subject' => 'Lịch sử',
            'description' => 'Các mốc lịch sử quan trọng',
            'color' => '#10b981',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        Card::create([
            'deck_id' => $deck3->id,
            'front' => 'Ngày thành lập Đảng Cộng sản Việt Nam',
            'back' => '3/2/1930',
            'difficulty' => 'easy',
            'status' => 'learned',
            'starred' => true,
            'review_count' => 8,
            'flipped' => false,
            'hint' => '',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        Card::create([
            'deck_id' => $deck3->id,
            'front' => 'Chiến thắng Điện Biên Phủ năm nào?',
            'back' => '7/5/1954',
            'difficulty' => 'easy',
            'status' => 'new',
            'starred' => false,
            'review_count' => 0,
            'flipped' => false,
            'hint' => 'Kháng chiến chống Pháp',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
    }
}
