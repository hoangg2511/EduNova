<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
class FlashCardController extends Controller
{
    use AuthorizesRequests;

    // ── Helper: format deck thành cấu trúc frontend cần ──────────────────────
    private function formatDeck(Deck $deck): array
    {
        return [
            'id'        => $deck->id,
            'name'      => $deck->name,
            'subject'   => $deck->subject,
            'status'    => $deck->status,
            'desc'      => $deck->description,
            'color'     => $deck->color ?? '#6366f1',
            'createdAt' => $deck->created_at->timestamp * 1000,
            'cards'     => $deck->cards
                ? $deck->cards->map(fn($c) => $this->formatCard($c))->values()->all()
                : [],
        ];
    }

    // ── Helper: format card ───────────────────────────────────────────────────
    private function formatCard(Card $card): array
    {
        return [
            'id'          => $card->id,
            'front'       => $card->front,
            'back'        => $card->back,
            'difficulty'  => $card->difficulty ?? 'medium',
            'status'      => $card->status ?? 'new',
            'starred'     => (bool) $card->starred,
            'reviewCount' => $card->review_count ?? 0,
            'flipped'     => false,
            'hint'        => $card->hint,
        ];
    }

    // ── index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $flashcards = Deck::where('user_id', auth()->id())
            ->where('status', '!=', 'deleted')
            ->with('cards')
            ->latest()
            ->get()
            ->map(fn($d) => $this->formatDeck($d))
            ->values();

        Log::info('User accessed flashcards index', [
            'user_id' => auth()->id(),
            'flashcard_count' => $flashcards->count(),
        ]);

        if ($request->wantsJson() || $request->query('ajax') == 1) {
            return response()->json([ 'success' => true, 'flashcards' => $flashcards ]);
        }

        return view('user.flashcards.index', [
            'flashcards' => $flashcards,
            'activeDeckId' => null,
        ]);
    }

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'desc'    => 'nullable|string|max:2000',
            'color'   => 'nullable|string|max:20',
        ]);

        $deck = Deck::create([
            'name'         => $validated['name'],
            'subject'      => $validated['subject'] ?? null,
            'description'  => $validated['desc'] ?? null,
            'color'        => $validated['color'] ?? '#6366f1',
            'user_id'      => auth()->id(),
            'status'       => 'new',
            'review_count' => 0,
        ]);

        // Load relationship để formatDeck không bị lỗi
        $deck->load('cards');

        Log::info('Deck created', ['id' => $deck->id, 'user_id' => auth()->id()]);

        return response()->json([
            'success' => true,
            'deck'    => $this->formatDeck($deck),
        ], 201);
    }

    // ── update ────────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $deck = Deck::where('id', $id)
                    ->where('user_id', auth()->id())   // chỉ sửa của chính mình
                    ->where('status', '!=', 'deleted')
                    ->first();

        if (!$deck) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bộ thẻ'], 404);
        }

        $validator = Validator::make($request->all(), [
    'name'    => 'required|string|max:255',
    'subject' => 'nullable|string|max:255',
    'desc'    => 'nullable|string|max:2000',
    'color'   => 'nullable|string|max:20',
    'status'  => 'nullable|in:new,learning,learned',
]);

if ($validator->fails()) {
    // Log toàn bộ lỗi nếu validate không đạt
    Log::error('Validation Failed:', [
        'errors' => $validator->errors()->toArray(),
        'input'  => $request->all()
    ]);
    
    // Trả lỗi về cho người dùng
    return back()->withErrors($validator)->withInput();
}

$validated = $validator->validated();

        $deck->update([
            'name'        => $validated['name'],
            'subject'     => $validated['subject'] ?? $deck->subject,
            'description' => $validated['desc'] ?? $deck->description,
            'color'       => $validated['color'] ?? $deck->color,
            'status'      => $validated['status'] ?? $deck->status,
        ]);

        // Reload để lấy cards hiện tại
        $deck->load('cards');

        Log::info('Deck updated', ['id' => $deck->id]);

        return response()->json([
            'success' => true,
            'deck'    => $this->formatDeck($deck),
        ]);
    }

    // ── destroy ───────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $deck = Deck::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->where('status', '!=', 'deleted')
                    ->first();

        if (!$deck) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bộ thẻ'], 404);
        }

        $deck->update(['status' => 'deleted']);

        Log::info('Deck soft-deleted', ['id' => $deck->id]);

        return response()->json(['success' => true, 'message' => 'Đã xóa bộ thẻ']);
    }

    public function storeCard(Request $request, $deckId)
    {
        // Log bắt đầu quá trình tạo thẻ
        Log::info('Bắt đầu quy trình tạo thẻ mới', ['deck_id' => $deckId, 'user_id' => auth()->id()]);

        $deck = Deck::where('id', $deckId)
                    ->where('user_id', auth()->id())
                    ->where('status', '!=', 'deleted')
                    ->first();

        if (!$deck) {
            // Log cảnh báo khi không tìm thấy bộ thẻ (có thể là lỗi UI hoặc kẻ xấu dò ID)
            Log::warning('Không tìm thấy bộ thẻ để thêm thẻ', ['deck_id' => $deckId, 'user_id' => auth()->id()]);
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bộ thẻ'], 404);
        }

        try {
            $validated = $request->validate([
                'front'      => 'required|string|max:1000',
                'back'       => 'required|string|max:1000',
                'difficulty' => 'nullable|in:easy,medium,hard',
                'hint'       => 'nullable|string|max:255',
                'starred'     => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log khi validate thất bại
            Log::error('Validation thất bại khi tạo thẻ', ['errors' => $e->errors(), 'deck_id' => $deckId]);
            throw $e;
        }

        $card = Card::create([
            'deck_id'     => $deck->id,
            'front'       => $validated['front'],
            'back'        => $validated['back'],
            'difficulty'  => $validated['difficulty'] ?? 'medium',
            'status'      => 'new',
            'starred'     => $validated['starred'] ?? false,
            'review_count'=> 0,
            'hint'        => $validated['hint'] ?? null,
        ]);

        // Log thành công kèm thông tin quan trọng để dễ truy vết
        Log::info('Thẻ đã được tạo thành công', [
            'card_id' => $card->id, 
            'deck_id' => $deck->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'card'    => $this->formatCard($card),
        ], 201);
    }
    public function destroyCard($id)
    {
        Log::info('Bắt đầu quy trình soft-delete thẻ', ['card_id' => $id]);

        $card = Card::where('id', $id)
                    ->first();

        if (!$card) {
            Log::warning('Xóa thẻ thất bại: Thẻ không tồn tại hoặc không thuộc quyền sở hữu', ['card_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thẻ'], 404);
        }

        $card->update(['status' => 'deleted']);

        Log::info('Thẻ đã được soft-deleted thành công', ['card_id' => $card->id]);

        return response()->json(['success' => true, 'message' => 'Đã xóa thẻ']);
    }

    public function updateStatus(Request $request, $id)
    {
        // Xác thực thẻ thuộc về user
        $card = Card::where('id', $id)
                    ->whereHas('deck', function($query) {
                        $query->where('user_id', auth()->id());
                    })->firstOrFail();

        // Validate dữ liệu gửi lên
        $validated = $request->validate([
            'status'  => 'nullable|string',
            'starred' => 'nullable|boolean',
        ]);

        // Cập nhật các trường được cung cấp
        if ($request->has('status')) {
            $card->status = $validated['status'];
            $card->review_count = $card->review_count + 1;
            
            // GỌI HÀM CẬP NHẬT STREAK TẠI ĐÂY
            // Mỗi khi người dùng thay đổi status của thẻ (tức là họ đã học/tương tác)
            auth()->user()->incrementStreak();
        }
        
        if ($request->has('starred')) {
            $card->starred = $validated['starred'];
        }

        $card->save();

        return response()->json([
            'success' => true, 
            'card' => $this->formatCard($card),
            // Trả thêm streak về để UI cập nhật ngay mà không cần reload
            'streak_days' => auth()->user()->streak_days 
        ]);
    }
    public function updateCard(Request $request, $deckId, $cardId)
    {
        Log::info('Bắt đầu quy trình cập nhật thẻ', [
            'deck_id' => $deckId,
            'card_id' => $cardId,
            'user_id' => auth()->id(),
        ]);

        // Xác thực deck thuộc về user hiện tại
        $deck = Deck::where('id', $deckId)
                    ->where('user_id', auth()->id())
                    ->where('status', '!=', 'deleted')
                    ->first();

        if (!$deck) {
            Log::warning('Không tìm thấy bộ thẻ khi cập nhật thẻ', ['deck_id' => $deckId]);
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bộ thẻ'], 404);
        }

        // Xác thực thẻ thuộc đúng deck đó
        $card = Card::where('id', $cardId)
                    ->where('deck_id', $deck->id)
                    ->first();

        if (!$card) {
            Log::warning('Không tìm thấy thẻ để cập nhật', ['card_id' => $cardId, 'deck_id' => $deck->id]);
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thẻ'], 404);
        }

        try {
            $validated = $request->validate([
                'front'      => 'required|string|max:1000',
                'back'       => 'required|string|max:1000',
                'difficulty' => 'nullable|in:easy,medium,hard',
                'hint'       => 'nullable|string|max:255',
                'starred'    => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation thất bại khi cập nhật thẻ', ['errors' => $e->errors(), 'card_id' => $cardId]);
            throw $e;
        }

        $card->update([
            'front'      => $validated['front'],
            'back'       => $validated['back'],
            'difficulty' => $validated['difficulty'] ?? $card->difficulty,
            'hint'       => $validated['hint'] ?? null,
            'starred'    => $validated['starred'] ?? $card->starred,
        ]);

        Log::info('Thẻ đã được cập nhật thành công', [
            'card_id' => $card->id,
            'deck_id' => $deck->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'card'    => $this->formatCard($card),
        ]);
    }
}