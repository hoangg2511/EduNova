<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use App\Models\Tag;
use App\Models\Type;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy user ids từ DB (giả sử đã chạy UserSeeder)
        $hung  = User::where('email', 'hung@gmail.com')->value('id')  ?? 1;
        $mai   = User::where('email', 'mai@gmail.com')->value('id')   ?? 1;
        $ha    = User::where('email', 'khoa@gmail.com')->value('id')  ?? 1; // dùng tạm
        $nam   = User::where('email', 'nam@gmail.com')->value('id')   ?? 1;
        $hoa   = User::where('email', 'hoa@gmail.com')->value('id')   ?? 1;
        $lan   = User::where('email', 'lan@gmail.com')->value('id')   ?? 1;
        $admin = $nam; // admin duyệt bài

        $docs = [
            [
                'user_id'     => $hung,
                'title'       => 'Bài giảng Toán cao cấp A1 - Chương 1',
                'description' => 'Tài liệu bài giảng toán cao cấp A1 chương 1, bao gồm lý thuyết và bài tập có lời giải đầy đủ.',
                'tag'     => 'Toán học',
                'type'        => 'pdf',
                'file_path'   => 'documents/toan-cao-cap-a1-chuong-1.pdf',
                'size'        => '4.2 MB',
                'status'      => 'pending',
                'downloads'   => 0,
                'created_at'  => '2025-06-10',
            ],
            [
                'user_id'     => $mai,
                'title'       => 'Đề thi Python cuối kỳ 2024',
                'description' => 'Đề thi lập trình Python kỳ 2 năm 2024, bao gồm phần lý thuyết và thực hành.',
                'tag'     => 'CNTT',
                'type'        => 'docx',
                'file_path'   => 'documents/de-thi-python-cuoi-ky-2024.docx',
                'size'        => '1.1 MB',
                'status'      => 'pending',
                'downloads'   => 0,
                'created_at'  => '2025-06-09',
            ],
            [
                'user_id'     => $ha,
                'title'       => 'Slide Tiếng Anh giao tiếp - Unit 5',
                'description' => 'Slide bài giảng tiếng Anh giao tiếp unit 5, chủ đề du lịch và văn hóa.',
                'tag'     => 'Ngoại ngữ',
                'type'        => 'pptx',
                'file_path'   => 'documents/tieng-anh-giao-tiep-unit-5.pptx',
                'size'        => '8.7 MB',
                'status'      => 'pending',
                'downloads'   => 0,
                'created_at'  => '2025-06-08',
            ],
            [
                'user_id'     => $hung,
                'title'       => 'Vật lý đại cương - Cơ học',
                'description' => 'Giáo trình vật lý đại cương phần cơ học.',
                'tag'     => 'Vật lý',
                'type'        => 'pdf',
                'file_path'   => 'documents/vat-ly-dai-cuong-co-hoc.pdf',
                'size'        => '6.3 MB',
                'status'      => 'approved',
                'downloads'   => 892,
                'reviewed_at' => '2025-06-02',
                'created_at'  => '2025-06-01',
            ],
            [
                'user_id'     => $nam,
                'title'       => 'Bảng công thức Toán rút gọn',
                'description' => 'Tổng hợp công thức toán học thường dùng.',
                'tag'     => 'Toán học',
                'type'        => 'pdf',
                'file_path'   => 'documents/bang-cong-thuc-toan.pdf',
                'size'        => '0.8 MB',
                'status'      => 'approved',
                'downloads'   => 2140,
                'reviewed_at' => '2025-05-16',
                'created_at'  => '2025-05-15',
            ],
            [
                'user_id'     => $mai,
                'title'       => 'Excel nâng cao - PivotTable & Chart',
                'description' => 'Hướng dẫn sử dụng PivotTable và Chart trong Excel nâng cao.',
                'tag'     => 'CNTT',
                'type'        => 'xlsx',
                'file_path'   => 'documents/excel-nang-cao-pivot.xlsx',
                'size'        => '3.2 MB',
                'status'      => 'approved',
                'downloads'   => 437,
                'reviewed_at' => '2025-05-21',
                'created_at'  => '2025-05-20',
            ],
            [
                'user_id'     => $hoa,
                'title'       => 'Kỹ năng thuyết trình hiệu quả',
                'description' => 'Slide hướng dẫn kỹ năng thuyết trình chuyên nghiệp.',
                'tag'     => 'Kỹ năng',
                'type'        => 'pptx',
                'file_path'   => 'documents/ky-nang-thuyet-trinh.pptx',
                'size'        => '12.1 MB',
                'status'      => 'approved',
                'downloads'   => 1823,
                'reviewed_at' => '2025-05-11',
                'created_at'  => '2025-05-10',
            ],
            [
                'user_id'     => $lan,
                'title'       => 'Đề cương ôn thi Hóa học',
                'description' => 'Đề cương ôn tập hóa học dành cho kỳ thi cuối học kỳ.',
                'tag'     => 'Hóa học',
                'type'        => 'docx',
                'file_path'   => 'documents/de-cuong-on-thi-hoa-hoc.docx',
                'size'        => '2.5 MB',
                'status'      => 'rejected',
                'downloads'   => 0,
                'reviewed_at' => '2025-06-06',
                'rejection_reason' => 'Nội dung chưa đầy đủ, thiếu phần bài tập minh họa.',
                'created_at'  => '2025-06-05',
            ],
        ];

        foreach ($docs as $doc) {
            // Ensure tag and type exist
            $tagModel = Tag::firstOrCreate(['name' => $doc['tag']], ['description' => $doc['tag']]);
            $typeModel = Type::firstOrCreate(['name' => $doc['type']], ['description' => $doc['type']]);

            // Create or update document (map to actual columns)
            $docData = [
                'name'        => $doc['title'],
                'description' => $doc['description'] ?? null,
                'url'         => $doc['file_path'] ?? null,
                'downloads'   => $doc['downloads'] ?? 0,
                'size'        => $doc['size'] ?? null,
                'created_at'  => $doc['created_at'] ?? now(),
                'updated_at'  => $doc['created_at'] ?? now(),
            ];

            $document = Document::updateOrCreate(
                ['name' => $doc['title']],
                $docData
            );

            // Attach tag and type pivot records if not attached
            if ($tagModel && method_exists($document, 'tags')) {
                $document->tags()->syncWithoutDetaching([$tagModel->id]);
            }
            if ($typeModel && method_exists($document, 'types')) {
                $document->types()->syncWithoutDetaching([$typeModel->id]);
            }
        }
    }
}