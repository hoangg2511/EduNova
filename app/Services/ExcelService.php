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
            
            // BOM cho UTF-8 để hiển thị đúng tiếng Việt trong Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Dòng hướng dẫn
            fputcsv($handle, ['HƯỚNG DẪN: Không thay đổi tiêu đề cột dưới đây.']);
            
            // Tiêu đề cột theo yêu cầu mới
            fputcsv($handle, [
                'Câu hỏi', 
                'Đáp án A', 
                'Đáp án B', 
                'Đáp án C', 
                'Đáp án D', 
                'Đáp án đúng (0-3)', 
                'Giải thích',
                'Loại câu hỏi(single/multiple/truefalse)'
            ]);
            
            // Dòng dữ liệu mẫu mới
            fputcsv($handle, [
                'Thủ đô Việt Nam?', 
                'Hà Nội', 
                'TP HCM', 
                'Đà Nẵng', 
                'Huế', 
                '0', 
                'Hà Nội là thủ đô VN',
                'single'
            ]);
            fputcsv($handle, [
                'Những thành phố nào thuộc Việt Nam?', 
                'Hà Nội', 
                'London', 
                'TP HCM', 
                'Tokyo', 
                '0;2', 
                'Hà Nội và TP HCM đều là TP của VN',
                'multiple'
            ]);

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
            
            // BOM cho UTF-8 để hiển thị đúng tiếng Việt trong Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

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
        
        // 1. Bỏ qua các dòng tiêu đề (Dòng 1, 2)
        fgetcsv($handle); // Dòng: Tên bài thi, Mô tả...
        $examData = fgetcsv($handle); // Dòng: Dữ liệu bài thi mẫu

        // 2. Lưu bài thi vào database
        $exam = Exam::create([
            'user_id'     => Auth::id(),
            'title'       => $examData[0],
            'description' => $examData[1],
            'duration'    => $examData[2],
            'pass_mark'   => $examData[3],
        ]);

        // 3. Bỏ qua dòng trống và dòng header câu hỏi
        fgetcsv($handle); // Dòng trống
        fgetcsv($handle); // Dòng header: Câu hỏi, Đáp án A...

        // 4. Lặp và lưu câu hỏi
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data[0])) continue; // Bỏ qua dòng trống nếu có
            $type = $data[7] ?? 'single'; // Lấy cột loại câu hỏi
            $correctRaw = $data[5];       // Ví dụ: "0" hoặc "0|2"

            if ($type === 'multiple') {
                // Chuyển "0;2" thành mảng số [0, 2]
                $correctArray = array_map('intval', explode(';', $correctRaw));
            } else {
                // Chuyển "0" thành mảng số [0]
                $correctArray = [$correctRaw];
            }
            Question::create([
                'examId'        => $exam->id,
                'text'           => $data[0],
                'options'        => [$data[1], $data[2], $data[3], $data[4]],
                'correctAnswers' => $correctArray,
                'explanation'    => $data[6],
                'type'           => $data[7],
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