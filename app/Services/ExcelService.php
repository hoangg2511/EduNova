<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\Auth;
class ExcelService
{
  public function templateQuestion()
{
    $response = new StreamedResponse(function () {
        $handle = fopen('php://output', 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        
        $delimiter = ';';

        // Dòng hướng dẫn: Phải thêm 7 dấu phân cách trống để đủ 8 cột
        fputcsv($handle, ['HƯỚNG DẪN: Không thay đổi tiêu đề cột dưới đây.', '', '', '', '', '', '', ''], $delimiter);
        
        // Tiêu đề cột (8 cột)
        fputcsv($handle, [
            'Câu hỏi', 'Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D', 
            'Đáp án đúng (0-3)', 'Giải thích', 'Loại câu hỏi'
        ], $delimiter);
        
        // Dữ liệu mẫu (8 cột)
        $data = [
            ['Thủ đô Việt Nam?', 'Hà Nội', 'TP HCM', 'Đà Nẵng', 'Huế', '0', 'Hà Nội là thủ đô VN', 'single'],
            ['Những thành phố nào thuộc Việt Nam?', 'Hà Nội', 'London', 'TP HCM', 'Tokyo', '0;2', 'Hà Nội và TP HCM đều là TP của VN', 'multiple'],
            ['Việt Nam nằm ở khu vực Đông Nam Á phải không?', 'Đúng', 'Sai', '', '', 'true', 'Việt Nam là một quốc gia thuộc khu vực ĐNA', 'truefalse']
        ];

        foreach ($data as $row) {
            fputcsv($handle, $row, $delimiter);
        }
        fclose($handle);
    }, 200, [
        'Content-Type' => 'text/csv; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="Template_Cau_Hoi.csv"',
    ]);

    return $response;
}

    public function templateExam()
    {
        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            // 1. Dòng thông tin bài thi (Header chính)
            fputcsv($handle, ['Tên bài thi', 'Mô tả', 'Thời gian (phút)', 'Điểm đậu (%)']);
            fputcsv($handle, ['Bài kiểm tra mẫu', 'Mô tả bài kiểm tra tại đây', '60', '50']);

            // Dòng trống phân cách
            fputcsv($handle, []);

            // 2. Dòng header cho danh sách câu hỏi
            fputcsv($handle, ['Câu hỏi', 'Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D', 'Đáp án đúng (0-3)', 'Giải thích','Loại câu hỏi(single/multiple/truefalse)']);

            // 3. Dữ liệu câu hỏi mẫu
            $questions = [
                ['Thủ đô Việt Nam?', 'Hà Nội', 'TP HCM', 'Đà Nẵng', 'Huế', '0', 'Hà Nội là thủ đô VN','single'],
                ['Những thành phố nào thuộc Việt Nam?', 'Hà Nội', 'London', 'TP HCM', 'Tokyo', '0;2', 'Hà Nội và TP HCM đều là TP của VN','multiple']
            ];

            foreach ($questions as $q) {
               fputcsv($handle, $q);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="Template_Bai_Thi.csv"',
        ]);

        return $response;
    }

    public function importExam($filePath)
    {
        $handle = fopen($filePath, 'r');

        // 1. Bỏ dòng tiêu đề đầu tiên (Tên bài thi, Mô tả...)
        fgetcsv($handle);

        // 2. Dòng dữ liệu bài thi
        $examData = fgetcsv($handle);

        $exam = Exam::create([
            'user_id'     => Auth::id(),
            'title'       => $examData[0],
            'description' => $examData[1] ?? '',
            'duration'    => $examData[2] ?? 30,
            'passMark'    => $examData[3] ?? 60, // sửa từ pass_mark -> passMark
        ]);

        // 3. Đọc tiếp và LINH ĐỘNG xác định có dòng trống / header câu hỏi hay không
        $pos = ftell($handle);
        $peek = fgetcsv($handle);

        while ($peek !== FALSE) {
            $firstCell = trim($peek[0] ?? '');

            // Dòng trống -> bỏ qua, đọc dòng kế tiếp
            if ($firstCell === '') {
                $pos = ftell($handle);
                $peek = fgetcsv($handle);
                continue;
            }

            // Dòng header câu hỏi (không phải dữ liệu câu hỏi thật) -> bỏ qua
            if (mb_strtolower($firstCell) === mb_strtolower('Câu hỏi')) {
                $pos = ftell($handle);
                $peek = fgetcsv($handle);
                continue;
            }

            // Không phải dòng trống, không phải header -> đây chính là câu hỏi đầu tiên
            break;
        }

        // Quay lại đúng vị trí bắt đầu của dòng câu hỏi đầu tiên
        fseek($handle, $pos);

        // 4. Lặp và lưu câu hỏi
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data[0])) continue; // bỏ dòng trống lẻ tẻ nếu có

            $type = strtolower(trim($data[7] ?? 'single'));
            $correctRaw = $data[5] ?? '0';

            if ($type === 'multiple') {
                // "0;2" -> [0, 2]
                $correctArray = array_map('intval', explode(';', $correctRaw));
            } elseif ($type === 'truefalse') {
                // giữ nguyên dạng chuỗi '0'/'1' hoặc chuyển sang 'true'/'false' tùy theo
                // cách front-end của bạn đang lưu (xem examApp() dùng 'true'/'false')
                $correctArray = [(string) trim($correctRaw)];
            } else {
                $correctArray = [(int) trim($correctRaw)];
            }

            Question::create([
                'examId'         => $exam->id,
                'text'           => $data[0],
                'options'        => [$data[1] ?? '', $data[2] ?? '', $data[3] ?? '', $data[4] ?? ''],
                'correctAnswers' => $correctArray,
                'explanation'    => $data[6] ?? '',
                'type'           => $type,
            ]);
        }

        fclose($handle);
        return $exam;
    }
    
    public function exportExam($examId)
    {
        // 1. Lấy dữ liệu bài thi kèm câu hỏi
        $exam = Exam::with('questions')->findOrFail($examId);

        // 2. Tạo file CSV tạm trong bộ nhớ (php://output)
        $filename = "exam_" . $exam->id . "_" . date('Ymd_His') . ".csv";
        
        // Thiết lập header để trình duyệt tải file
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($exam) {
            $file = fopen('php://output', 'w');
            
            // Thêm BOM để mở file CSV bằng Excel không bị lỗi font (Tiếng Việt)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Dòng 1: Header thông tin bài thi
            fputcsv($file, ['Tên bài thi', 'Mô tả', 'Thời gian', 'Pass Mark']);
            // Dòng 2: Dữ liệu bài thi
            fputcsv($file, [$exam->title, $exam->description, $exam->duration, $exam->passMark]);
            
            fputcsv($file, []); // Dòng trống
            
            // Dòng 4: Header câu hỏi
            fputcsv($file, ['Câu hỏi', 'Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D', 'Đáp án đúng', 'Giải thích', 'Loại']);

            // Dòng 5+: Dữ liệu câu hỏi
            foreach ($exam->questions as $question) {
                fputcsv($file, [
                    $question->text,
                    $question->options[0] ?? '',
                    $question->options[1] ?? '',
                    $question->options[2] ?? '',
                    $question->options[3] ?? '',
                    implode(';', $question->correctAnswers),
                    $question->explanation,
                    $question->type
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}