<?php
namespace Database\Seeders;
 
use App\Models\NewsArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
 
class NewsArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title'          => 'Ứng dụng AI trong giảng dạy: Tương lai của nền giáo dục Việt Nam',
                'excerpt'        => 'Các mô hình ngôn ngữ lớn đang thay đổi cách giáo viên thiết kế bài giảng, giúp cá nhân hóa lộ trình học tập cho từng học sinh hiệu quả hơn bao giờ hết.',
                'content'        => $this->makeContent('AI đang cách mạng hóa giáo dục'),
                'category'       => 'edu',
                'emoji'          => '🎓',
                'author_name'    => 'Phạm Hồng Anh',
                'author_initials'=> 'PH',
                'read_time'      => 8,
                'views'          => 2400,
                'is_featured'    => true,
                'status'         => 'published',
                'published_at'   => now()->subDays(2),
            ],
            [
                'title'          => 'Phương pháp học tập chủ động giúp ghi nhớ lâu hơn 3 lần',
                'excerpt'        => 'Nghiên cứu từ Đại học Quốc gia cho thấy học sinh áp dụng phương pháp active learning đạt kết quả vượt trội hơn đáng kể so với học thụ động.',
                'content'        => $this->makeContent('Active learning và khoa học ghi nhớ'),
                'category'       => 'edu',
                'emoji'          => '📚',
                'author_name'    => 'Nguyễn Thị Lan',
                'author_initials'=> 'NL',
                'read_time'      => 5,
                'views'          => 1800,
                'status'         => 'published',
                'published_at'   => now()->subDays(4),
            ],
            [
                'title'          => 'Ra mắt nền tảng học trực tuyến tích hợp thực tế ảo VR tại Việt Nam',
                'excerpt'        => 'EduVR chính thức công bố nền tảng học tập thực tế ảo đầu tiên dành cho bậc THPT, mang lại trải nghiệm học hoàn toàn mới.',
                'content'        => $this->makeContent('Thực tế ảo trong giáo dục'),
                'category'       => 'tech',
                'emoji'          => '💻',
                'author_name'    => 'Trần Minh Đức',
                'author_initials'=> 'TĐ',
                'read_time'      => 4,
                'views'          => 2100,
                'status'         => 'published',
                'published_at'   => now()->subDays(5),
            ],
            [
                'title'          => 'Kỳ thi Olympic Toán học quốc gia 2026 — Kết quả và điểm nổi bật',
                'excerpt'        => 'Học sinh Hà Nội và TP.HCM thống lĩnh bảng vàng với 12 huy chương vàng, phá kỷ lục 3 năm liên tiếp của kỳ thi.',
                'content'        => $this->makeContent('Kết quả kỳ thi Olympic Toán học'),
                'category'       => 'event',
                'emoji'          => '🏆',
                'author_name'    => 'Lê Thu Hương',
                'author_initials'=> 'LH',
                'read_time'      => 6,
                'views'          => 3200,
                'status'         => 'published',
                'published_at'   => now()->subDays(6),
            ],
            [
                'title'          => 'Bộ GD&ĐT công bố lịch thi tốt nghiệp THPT năm học 2025–2026',
                'excerpt'        => 'Kỳ thi được tổ chức vào ngày 25–27 tháng 6 với nhiều điều chỉnh quan trọng trong cấu trúc đề thi và quy chế thi.',
                'content'        => $this->makeContent('Lịch thi tốt nghiệp THPT 2026'),
                'category'       => 'notice',
                'emoji'          => '📢',
                'author_name'    => 'Ban biên tập',
                'author_initials'=> 'BB',
                'read_time'      => 3,
                'views'          => 4500,
                'status'         => 'published',
                'published_at'   => now()->subDays(7),
            ],
            [
                'title'          => 'Thiền định 10 phút mỗi ngày cải thiện điểm số học sinh đáng kể',
                'excerpt'        => 'Đại học Huế công bố nghiên cứu về tác động tích cực của chánh niệm đến khả năng tập trung và kết quả học tập.',
                'content'        => $this->makeContent('Thiền định và học tập'),
                'category'       => 'health',
                'emoji'          => '🧘',
                'author_name'    => 'PGS. Hoàng Nam',
                'author_initials'=> 'HN',
                'read_time'      => 7,
                'views'          => 987,
                'status'         => 'published',
                'published_at'   => now()->subDays(8),
            ],
            [
                'title'          => 'ChatGPT-5 trong lớp học: Cơ hội hay thách thức cho giáo viên?',
                'excerpt'        => 'Giáo viên và phụ huynh chia sẻ góc nhìn đa chiều về việc tích hợp AI vào quá trình học tập của học sinh phổ thông.',
                'content'        => $this->makeContent('ChatGPT và tương lai lớp học'),
                'category'       => 'tech',
                'emoji'          => '🤖',
                'author_name'    => 'Nguyễn Văn Bình',
                'author_initials'=> 'NB',
                'read_time'      => 8,
                'views'          => 5600,
                'status'         => 'published',
                'published_at'   => now()->subDays(10),
            ],
            [
                'title'          => 'Học bổng Vingroup 2026 chính thức mở đơn đăng ký toàn quốc',
                'excerpt'        => 'Tổng cộng 500 suất học bổng trị giá 50 triệu đồng dành cho sinh viên xuất sắc, hạn nộp hồ sơ đến ngày 30/7.',
                'content'        => $this->makeContent('Học bổng Vingroup 2026'),
                'category'       => 'notice',
                'emoji'          => '📋',
                'author_name'    => 'Ban biên tập',
                'author_initials'=> 'BB',
                'read_time'      => 4,
                'views'          => 6800,
                'status'         => 'published',
                'published_at'   => now()->subDays(12),
            ],
            [
                'title'          => 'Lập trình Python được đưa vào chương trình lớp 6 từ năm học tới',
                'excerpt'        => 'Bộ GD&ĐT xác nhận Python sẽ là ngôn ngữ lập trình đầu tiên học sinh tiếp cận từ bậc THCS trong khung chương trình mới.',
                'content'        => $this->makeContent('Python vào chương trình THCS'),
                'category'       => 'tech',
                'emoji'          => '🖥️',
                'author_name'    => 'Ban biên tập',
                'author_initials'=> 'BB',
                'read_time'      => 5,
                'views'          => 7200,
                'status'         => 'published',
                'published_at'   => now()->subDays(14),
            ],
            [
                'title'          => 'STEM hay STEAM? Vai trò của nghệ thuật trong giáo dục hiện đại',
                'excerpt'        => 'Nhiều trường tiên tiến bổ sung chữ A (Arts) vào chương trình STEM để phát triển tư duy sáng tạo và toàn diện hơn.',
                'content'        => $this->makeContent('STEAM education'),
                'category'       => 'edu',
                'emoji'          => '🎨',
                'author_name'    => 'Đinh Thị Mai',
                'author_initials'=> 'DM',
                'read_time'      => 6,
                'views'          => 1200,
                'status'         => 'published',
                'published_at'   => now()->subDays(15),
            ],
            [
                'title'          => 'Vận động thể chất 30 phút giúp học sinh tập trung tốt hơn 40%',
                'excerpt'        => 'Báo cáo y tế mới nhất chứng minh học sinh tập thể dục đều đặn mỗi ngày có khả năng ghi nhớ và phản xạ nhanh hơn rõ rệt.',
                'content'        => $this->makeContent('Thể dục và học tập'),
                'category'       => 'health',
                'emoji'          => '🏃',
                'author_name'    => 'BS. Vũ Minh',
                'author_initials'=> 'VM',
                'read_time'      => 5,
                'views'          => 876,
                'status'         => 'published',
                'published_at'   => now()->subDays(16),
            ],
            [
                'title'          => 'EduTalk 2026 — Diễn giả nổi bật và các chủ đề giáo dục trọng tâm',
                'excerpt'        => 'Sự kiện giáo dục lớn nhất năm quy tụ 30 diễn giả quốc tế với chủ đề về tương lai học tập và đổi mới sáng tạo.',
                'content'        => $this->makeContent('EduTalk 2026'),
                'category'       => 'event',
                'emoji'          => '🎤',
                'author_name'    => 'Lê Gia Hưng',
                'author_initials'=> 'LG',
                'read_time'      => 6,
                'views'          => 1600,
                'status'         => 'published',
                'published_at'   => now()->subDays(18),
            ],
            [
                'title'          => 'Ngủ ít hơn 7 tiếng làm giảm hiệu quả ghi nhớ của học sinh 30%',
                'excerpt'        => 'Viện Khoa học Giáo dục nhấn mạnh tầm quan trọng của giấc ngủ với học sinh THPT trong mùa thi cuối năm.',
                'content'        => $this->makeContent('Giấc ngủ và học tập'),
                'category'       => 'health',
                'emoji'          => '😴',
                'author_name'    => 'PGS. Trần Thị Nga',
                'author_initials'=> 'TN',
                'read_time'      => 7,
                'views'          => 2900,
                'status'         => 'published',
                'published_at'   => now()->subDays(20),
            ],
            [
                'title'          => 'Danh sách 512 trường đạt chuẩn quốc gia 2026 vừa được công bố',
                'excerpt'        => 'Số trường đạt chuẩn quốc gia mức độ 2 tăng 18% so với năm trước, phản ánh nỗ lực đầu tư hạ tầng giáo dục toàn quốc.',
                'content'        => $this->makeContent('Trường chuẩn quốc gia 2026'),
                'category'       => 'notice',
                'emoji'          => '🏫',
                'author_name'    => 'Ban biên tập',
                'author_initials'=> 'BB',
                'read_time'      => 3,
                'views'          => 3100,
                'status'         => 'published',
                'published_at'   => now()->subDays(22),
            ],
            [
                'title'          => 'Hội thảo Giáo dục Đông Nam Á 2026 — Việt Nam đăng cai tại TPHCM',
                'excerpt'        => 'Hơn 500 chuyên gia giáo dục từ 11 quốc gia sẽ quy tụ tại TP.HCM vào tháng 8 để thảo luận tương lai giáo dục khu vực.',
                'content'        => $this->makeContent('Hội thảo Giáo dục ASEAN'),
                'category'       => 'event',
                'emoji'          => '🌏',
                'author_name'    => 'Phạm Quốc Tuấn',
                'author_initials'=> 'PT',
                'read_time'      => 5,
                'views'          => 1400,
                'status'         => 'published',
                'published_at'   => now()->subDays(25),
            ],
            [
                'title'          => 'Phòng lab ảo — giải pháp thực hành khoa học giá rẻ cho vùng xa',
                'excerpt'        => 'Công nghệ mô phỏng 3D giúp học sinh ở vùng khó khăn trải nghiệm thí nghiệm khoa học như thật mà không cần thiết bị đắt tiền.',
                'content'        => $this->makeContent('Lab ảo cho vùng xa'),
                'category'       => 'tech',
                'emoji'          => '🔬',
                'author_name'    => 'Vũ Anh Tuấn',
                'author_initials'=> 'VA',
                'read_time'      => 6,
                'views'          => 1300,
                'status'         => 'published',
                'published_at'   => now()->subDays(26),
            ],
            [
                'title'          => 'App học tiếng Anh với AI của EduNova đạt 1 triệu người dùng',
                'excerpt'        => 'Chỉ sau 6 tháng ra mắt, EduNova English đã chinh phục cộng đồng người học với tỉ lệ hoàn thành khóa học cao nhất thị trường.',
                'content'        => $this->makeContent('EduNova English 1 triệu người dùng'),
                'category'       => 'tech',
                'emoji'          => '📱',
                'author_name'    => 'Trần Phú Cường',
                'author_initials'=> 'TC',
                'read_time'      => 4,
                'views'          => 3700,
                'status'         => 'published',
                'published_at'   => now()->subDays(28),
            ],
            [
                'title'          => 'Dạy toán qua trò chơi — mô hình gamification lan rộng tại TPHCM',
                'excerpt'        => 'Hàng chục trường tiểu học thí điểm gamification trong giờ toán và ghi nhận tỉ lệ học sinh yêu thích môn học tăng gấp đôi.',
                'content'        => $this->makeContent('Gamification trong dạy toán'),
                'category'       => 'edu',
                'emoji'          => '🧮',
                'author_name'    => 'Nguyễn Thanh Tùng',
                'author_initials'=> 'NT',
                'read_time'      => 5,
                'views'          => 2000,
                'status'         => 'published',
                'published_at'   => now()->subDays(30),
            ],
            [
                'title'          => 'Chương trình trao đổi học sinh Việt – Nhật mở rộng quy mô năm 2026',
                'excerpt'        => 'Thỏa thuận hợp tác mới nâng số lượng học sinh trao đổi lên 1.000 em/năm, bao gồm cả học bổng chi phí sinh hoạt.',
                'content'        => $this->makeContent('Chương trình trao đổi Việt Nhật'),
                'category'       => 'edu',
                'emoji'          => '🌍',
                'author_name'    => 'Cao Thị Phương',
                'author_initials'=> 'CP',
                'read_time'      => 5,
                'views'          => 2300,
                'status'         => 'published',
                'published_at'   => now()->subDays(32),
            ],
            [
                'title'          => 'Cuộc thi Sáng tạo Khoa học Kỹ thuật Quốc gia — Hạn nộp bài sắp tới',
                'excerpt'        => 'Năm nay mở rộng thêm hạng mục AI & Machine Learning dành riêng cho học sinh phổ thông, giải thưởng lên tới 100 triệu đồng.',
                'content'        => $this->makeContent('Cuộc thi KHKT quốc gia'),
                'category'       => 'event',
                'emoji'          => '🏅',
                'author_name'    => 'Lê Thanh Bình',
                'author_initials'=> 'LB',
                'read_time'      => 4,
                'views'          => 1100,
                'status'         => 'published',
                'published_at'   => now()->subDays(35),
            ],
        ];
 
        foreach ($articles as $data) {
            $data['slug'] = NewsArticle::generateSlug($data['title']);
            NewsArticle::create($data);
        }
 
        $this->command->info('✅ Seeded ' . count($articles) . ' news articles.');
    }
 
    private function makeContent(string $topic): string
    {
        return <<<HTML
<h2>Giới thiệu</h2>
<p>Trong bối cảnh giáo dục Việt Nam đang trải qua giai đoạn chuyển mình mạnh mẽ, chủ đề <strong>{$topic}</strong> ngày càng thu hút sự quan tâm của đông đảo giáo viên, học sinh và phụ huynh trên cả nước.</p>
 
<h2>Tác động thực tiễn</h2>
<p>Nhiều nghiên cứu trong và ngoài nước đã chứng minh rằng việc áp dụng các phương pháp giáo dục hiện đại mang lại hiệu quả vượt trội so với cách dạy truyền thống. Các chuyên gia nhấn mạnh tầm quan trọng của việc cá nhân hóa lộ trình học tập cho từng học sinh.</p>
 
<blockquote>"Giáo dục không phải là đổ đầy một cái thùng, mà là thắp sáng một ngọn lửa." — William Butler Yeats</blockquote>
 
<h2>Xu hướng hiện nay</h2>
<p>Tại Việt Nam, nhiều trường học đã bắt đầu thí điểm các mô hình giáo dục mới với kết quả đáng khích lệ. Tỉ lệ học sinh hứng thú với việc học tăng đáng kể, đồng thời kết quả thi cử cũng được cải thiện rõ rệt.</p>
<ul>
    <li>Tăng cường sự tham gia chủ động của học sinh</li>
    <li>Ứng dụng công nghệ vào giảng dạy</li>
    <li>Đánh giá liên tục thay vì kiểm tra định kỳ</li>
    <li>Phát triển kỹ năng mềm song song với kiến thức</li>
</ul>
 
<h2>Thách thức cần vượt qua</h2>
<p>Mặc dù nhiều kết quả tích cực đã được ghi nhận, vẫn còn không ít thách thức trong quá trình triển khai đại trà. Sự chênh lệch về cơ sở vật chất giữa các vùng miền, cũng như việc đào tạo lại đội ngũ giáo viên, là những bài toán cần được giải quyết đồng bộ.</p>
 
<h2>Kết luận</h2>
<p>Với sự quan tâm ngày càng tăng từ phía Nhà nước, các tổ chức giáo dục và cộng đồng doanh nghiệp, tương lai của giáo dục Việt Nam đang mở ra nhiều cơ hội đầy hứa hẹn. Điều quan trọng là tất cả các bên liên quan cùng chung tay xây dựng một nền giáo dục toàn diện, hiện đại và công bằng cho mọi học sinh.</p>
HTML;
    }
}