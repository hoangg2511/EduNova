<?php

namespace App\Http\Controllers;

use App\Models\Knowledge;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KnowledgeController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * Hiển thị danh sách khóa học của người dùng
     */
    public function index()
    {
      $knowledges = \App\Models\Knowledge::where('user_id', auth()->id())
                    ->latest()
                    ->get();

        return view('user.knowledge.index', compact('knowledges'));
    }

    /**
     * Hiển thị trang tạo lộ trình mới
     */
    public function roadmap()
    {
        return view('user.knowledge.roadmap');
    }

    /**
     * POST /api/knowledge/generate
     * Body: { "topic": "Ngữ pháp TOEIC" }
     * Response: { "success": true, "knowledge_tree": {...} }
     */
    public function generate(Request $request): JsonResponse
    {
        Log::info('Nhận yêu cầu tạo cây kiến thức với chủ đề: ' . $request->topic);
        $request->validate([
            'topic' => 'required|string|max:200',
        ]);

        $tree = $this->gemini->generateKnowledgeTree($request->topic);

        if (!$tree) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo cây kiến thức, vui lòng thử lại.',
            ], 500);
        }
        Log::info('Cây kiến thức đã được tạo: ' . json_encode($tree));
        $this->gemini->consumeKnowledge(auth()->id());
        return response()->json([
            'success'        => true,
            'knowledge_tree' => $tree,
        ]);
    }

    /**
     * Lưu lộ trình vào database
     */
    public function store(Request $request)
    {
        Log::info('Bắt đầu xử lý lưu...'); 
        Log::info('Dữ liệu nhận được:', $request->only(['topic', 'format']));

        $validated = $request->validate([
            'topic' => 'required|string',
            'format' => 'required',
            'knowledge_tree' => 'required',
        ]);
        
        Log::info('Validation xong.');

        try {
            // Log dữ liệu trước khi lưu
            Log::info('Chuẩn bị tạo record...');
            
            $knowledge = Knowledge::create([
                'user_id' => auth()->id(),
                'title'   => $validated['topic'],
                'format'  => $validated['format'],
                'data'    => $validated['knowledge_tree'], // Kiểm tra biến này có dữ liệu không?
                'status'  => 'draft',
            ]);

            Log::info('Tạo record thành công ID: ' . $knowledge->id);

            return response()->json(['success' => true, 'knowledge_id' => $knowledge->id]);

        } catch (\Exception $e) {
            Log::error('LỖI THỰC SỰ: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hiển thị chi tiết một khóa học
     */
    public function show($id)
    {
        $knowledge = Knowledge::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('user.knowledge.show', [
            'knowledge' => $knowledge,
        ]);
    }
    /**
     * Cập nhật cây kiến thức (tiêu đề, mô tả, toàn bộ cấu trúc data)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $knowledge = Knowledge::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'knowledge_tree' => 'required|array',
            'knowledge_tree.ten_chuyen_de' => 'required|string|max:255',
            'knowledge_tree.mo_ta'         => 'nullable|string',
            'knowledge_tree.cac_chu_de_lon'   => 'required|array|min:1',
            'knowledge_tree.cac_chu_de_lon.*.ten'    => 'required|string|max:255',
            'knowledge_tree.cac_chu_de_lon.*.mo_ta'  => 'nullable|string',
            'knowledge_tree.cac_chu_de_lon.*.cac_chu_de_con'              => 'nullable|array',
            'knowledge_tree.cac_chu_de_lon.*.cac_chu_de_con.*.ten'        => 'required|string|max:255',
            'knowledge_tree.cac_chu_de_lon.*.cac_chu_de_con.*.noi_dung'   => 'nullable|string',
            'knowledge_tree.cac_chu_de_lon.*.cac_chu_de_con.*.cong_thuc'  => 'nullable|string',
            'knowledge_tree.cac_chu_de_lon.*.cac_chu_de_con.*.vi_du'      => 'nullable|string',
        ]);

        try {
            $knowledge->update([
                'title' => $validated['title'],
                'data'  => $validated['knowledge_tree'],
            ]);

            Log::info('Knowledge tree updated', ['knowledge_id' => $knowledge->id, 'user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Đã lưu thay đổi thành công.',
            ]);
        } catch (\Exception $e) {
            Log::error('Knowledge update failed: ' . $e->getMessage(), ['knowledge_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu thay đổi.',
            ], 500);
        }
    }

    /**
     * Xoá một lộ trình
     */
    public function destroy($id): JsonResponse
    {
        $knowledge = Knowledge::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $knowledge->delete();

        return response()->json(['success' => true, 'message' => 'Đã xoá lộ trình.']);
    }
}