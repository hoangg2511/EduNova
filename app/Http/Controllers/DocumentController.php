<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\MyDocument;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\UserLog;
use App\Models\DocumentReview;
class DocumentController extends Controller
{
    protected $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }

 
    public function index()
    {
        // 1. Tất cả tài liệu
        $documents = Document::where('status', 'approved')
        ->with(['tags', 'types', 'reviews.user:id,name']) 
            ->latest()
            ->paginate(12);

        // 2. Tài liệu đã lưu (Bookmark)
        $savedDocuments = auth()->user()
            ->savedDocuments()
            ->where('status', 'approved')
            ->with(['tags', 'types', 'reviews.user:id,name'])
            ->latest()
            ->paginate(12);

        // 3. Tài liệu tự upload (qua bảng uploads)
        $myDocuments = Document::where('uploaded_by', auth()->id())
        ->with(['tags', 'types', 'reviews.user:id,name'])
        ->latest()
        ->paginate(12);

        // Gắn Signed URL cho cả 3 danh sách
        foreach ([$documents, $savedDocuments, $myDocuments] as $collection) {
            $collection->getCollection()->transform(function ($doc) {
                $signed = '';
                try {
                    if (!empty($doc->url)) {
                        $signed = $this->supabaseService->getSignedUrl('documents', $doc->url, 86400);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to generate signed url', ['id' => $doc->id]);
                }
                $doc->setAttribute('view_url', $signed);
                return $doc;
            });
        }

        return view('user.documents.index', compact('documents', 'savedDocuments', 'myDocuments'));
    }

    public function viewPage($id)
    {
        $document = Document::findOrFail($id);
        abort_unless(
            (int)$document->status === 1 || $document->uploaded_by === auth()->id(),
            403
        );

        // Truyền stream URL thay vì signed URL
        $streamUrl = route('user.documents.stream', $document->id);
        $document->setAttribute('view_url', $streamUrl);

        return view('user.documents.view', compact('document'));
    }

    public function stream(Document $document)
    {
        abort_unless(
            (int)$document->status === 1 || $document->uploaded_by === auth()->id(),
            403
        );

        try {
            // Lấy nội dung file từ Supabase
            $signedUrl = $this->supabaseService->getSignedUrl('documents', $document->url, 300);
            
            $fileContent = file_get_contents($signedUrl);
            
            if ($fileContent === false) {
                abort(404, 'Không thể tải file');
            }

            $ext      = strtolower(pathinfo($document->url, PATHINFO_EXTENSION));
            $mimeMap  = [
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'mp4'  => 'video/mp4',
            ];
            $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

            return response($fileContent, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($document->url) . '"',
                // Cho phép iframe load từ cùng origin
                'X-Frame-Options'     => 'SAMEORIGIN',
                // Xóa CSP block
                'Content-Security-Policy' => "default-src 'self'",
            ]);

        } catch (\Exception $e) {
            Log::error('Stream document failed', ['id' => $document->id, 'error' => $e->getMessage()]);
            abort(500, 'Lỗi khi stream tài liệu');
        }
    }

    // Trong App\Http\Controllers\DocumentController.php

    public function download(Document $document)
    {
        // 1. Kiểm tra quyền (chỉ cho phép nếu tài liệu công khai hoặc thuộc về user)
        abort_unless(
            (int)$document->status === 1 ,
            403
        );

        // 2. Kiểm tra hạn mức (Nếu tài liệu không phải của user thì mới trừ lượt)
        if ((int) $document->uploaded_by !== (int)auth()->id()) {
        
            if (!$this->decreaseDownloadLimit(auth()->id())) {
                // Nếu hàm trả về false, nghĩa là hạn mức đã bằng 0
                return response()->json([
                    'success' => false, 
                    'message' => 'Bạn đã hết lượt tải tài liệu.'
                ], 403);
            }
        }

        try {
            $response = $this->supabaseService->downloadFile('documents', $document->url);

            if (!$response->successful()) {
                Log::error('Download failed', ['document_id' => $document->id, 'response' => $response->body()]);
                return back()->with('error', 'Không thể tải tài liệu tại thời điểm này.');
            }

            // 3. Trả về file cho trình duyệt
            $fileName = basename($document->url);
            
            return response($response->body(), 200, [
                'Content-Type' => $response->header('Content-Type'),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Download error: ' . $e->getMessage());
            return back()->with('error', 'Đã xảy ra lỗi hệ thống.');
        }
    }
    private function decreaseDownloadLimit(int $userId): bool
    {
        // Việc kiểm tra download_limit > 0 ở đây giúp chặn việc trừ vào con số âm
        $affected = UserLog::where('user_id', $userId)
            ->where('download_limit', '>', 0)
            ->decrement('download_limit');

        // 2. Nếu $affected > 0, nghĩa là đã trừ thành công 1 đơn vị
        return $affected > 0;
    }
    /**
     * Upload tài liệu mới
     */
    public function upload(Request $request): JsonResponse
    {
        // Log thông tin người dùng đang thực hiện hành động
        Log::info('User started document upload', ['user_id' => auth()->id() ?? 'guest']);

        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,mp4|max:51200',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'author' => 'required|string|max:255',
            ]);

            $file = $request->file('file');
            $tags = [];
            if ($request->has('category')) {
                $tags = explode('/', trim($request->category, '/'));
            }

            // 2. Tạo đường dẫn lưu trữ có cấu trúc (Folder/UUID_Time.ext)
            $categoryDir = $request->category ? trim($request->category, '/') . '/' : 'others/';
            $fileName = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $categoryDir . $fileName; // Supabase sẽ tự tạo folder nếu dùng đường dẫn này
            // Log thông tin file trước khi upload
            Log::info('Checking upload path', [
                'full_path' => $filePath,
            ]);
            Log::debug('File details for upload', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize()
            ]);

            $fileName = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $fileName;
            $fileSizeFormatted = $this->formatBytes($file->getSize());
            
            $fileContent = file_get_contents($file->getRealPath());
            $uploadResponse = $this->supabaseService->uploadDocument(
                'documents',
                $filePath,
                $fileContent,
                $file->getClientMimeType()
            );
            
            if (!$uploadResponse->successful()) {
                // Log lỗi từ phía Supabase trả về
                Log::error('Supabase upload API failure', [
                    'status' => $uploadResponse->status(),
                    'response' => $uploadResponse->body()
                ]);
                return response()->json(['error' => 'Lỗi khi upload file lên Cloud'], 500);
            }

            $documentUrl = $this->supabaseService->getSignedUrl('documents', $filePath, 86400 * 365);

            $document = Document::create([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? '',
                'url'         => $filePath,
                'downloads'   => 0,
                'rate'        => 0,
                'medium_rate' => 0,
                'size'        => $fileSizeFormatted,
                'author'      => $validated['author'],
                'status'      => "pending",            // chờ duyệt
                'uploaded_by' => auth()->id(), // ← thêm
            ]);

            // Prepare signed URL for front-end viewing/downloading
            $viewUrl = '';
            try {
                $viewUrl = $this->supabaseService->getSignedUrl('documents', $filePath, 86400 * 365);
                $document->setAttribute('view_url', $viewUrl);
            } catch (\Exception $e) {
                Log::warning('Failed to generate signed url after upload', ['document_id' => $document->id, 'error' => $e->getMessage()]);
            }

            // Log thành công kèm ID tài liệu
            Log::info('Document uploaded and recorded successfully', [
                'document_id' => $document->id,
                'storage_path' => $filePath
            ]);

            return response()->json([
                'success' => true,
                'document' => $document,
                'view_url' => $viewUrl,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log lỗi validation
            Log::warning('Document upload validation failed', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation error', 'messages' => $e->errors()], 422);

        } catch (\Exception $e) {
            // Log lỗi nghiêm trọng (Exception)
            Log::critical('Document upload system error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json(['error' => 'Có lỗi hệ thống xảy ra'], 500);
        }
    }
    

    public function saveDocument(Request $request): JsonResponse
    {
        // Log thông tin request để biết client gửi gì lên
        Log::info('Đang xử lý yêu cầu lưu tài liệu', ['document_id' => $request->document_id, 'user_id' => auth()->id()]);

        $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        try {
            $user = auth()->user();
            
            $isAlreadySaved = \App\Models\MyDocument::where('user_id', $user->id)
                ->where('document_id', $request->document_id)
                ->exists();

            if ($isAlreadySaved) {
                Log::warning('Tài liệu đã được lưu trước đó', ['document_id' => $request->document_id, 'user_id' => $user->id]);
                return response()->json(['message' => 'Tài liệu đã nằm trong thư viện của bạn'], 409);
            }

            \App\Models\MyDocument::create([
                'user_id' => $user->id,
                'document_id' => $request->document_id
            ]);

            Log::info('Lưu tài liệu thành công', ['document_id' => $request->document_id, 'user_id' => $user->id]);
            return response()->json(['success' => true, 'message' => 'Lưu tài liệu thành công!'], 200);
            
        } catch (\Exception $e) {
            // Log chi tiết lỗi để dễ dàng kiểm tra trong file laravel.log
            Log::error('Lỗi hệ thống khi lưu tài liệu', [
                'document_id' => $request->document_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Không thể lưu tài liệu'], 500);
        }
    }

    
    public function unsaveDocument($document_id): JsonResponse
    {
        try {
            $user = auth()->user();

            // Tìm và xóa bản ghi
            $deleted = \App\Models\MyDocument::where('user_id', $user->id)
                ->where('document_id', $document_id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Đã xóa tài liệu khỏi thư viện.'
                ], 200);
            }

            return response()->json(['message' => 'Tài liệu không tồn tại trong thư viện.'], 404);

        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa khỏi my_documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Không thể xóa tài liệu'], 500);
        }
    }
    /**
     * Format bytes to human-readable size
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function storeReview(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
 
        // Upsert: nếu user đã đánh giá thì cập nhật, chưa thì tạo mới
        $review = DocumentReview::updateOrCreate(
            [
                'document_id' => $document->id,
                'user_id'     => auth()->id(),
            ],
            [
                'rating'  => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );
 
        // Tính lại rating trung bình ngay lập tức
        $document->recalcRating();
        $document->refresh();
 
        Log::info('Document review saved', [
            'document_id' => $document->id,
            'user_id'     => auth()->id(),
            'rating'      => $validated['rating'],
        ]);
 
        return response()->json([
            'success' => true,
            'message' => $review->wasRecentlyCreated ? 'Đã gửi đánh giá!' : 'Đã cập nhật đánh giá!',
            'review'  => [
                'id'      => $review->id,
                'user'    => auth()->user()->name,
                'rating'  => $review->rating,
                'comment' => $review->comment ?? 'Không có nhận xét.',
            ],
            'new_rating'  => $document->rate,
            'new_reviews' => $document->reviews()->count(),
        ], 200);
    }
 
}
