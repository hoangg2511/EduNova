<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\NotificationController;
use App\Models\Event;
use App\Models\TypeEvent;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1. Lấy danh sách sự kiện của user hiện tại
        $events = Event::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->with('typeEvent')
                    ->get();

        // 2. Map events cho frontend
        $calendarEvents = $events->map(function ($event) {
            return [
                'id'          => $event->id,
                'title'       => $event->title,
                'type'        => $event->typeEvent ? $event->typeEvent->key : 'study', 
                'date'        => $event->date ? $event->date->format('Y-m-d') : null,
                'startTime'   => $event->start,
                'endTime'     => $event->end,
                'description' => $event->note,
                'repeat'      => 'none',
            ];
        });

        // 3. Lấy danh sách loại sự kiện cho legend
        $eventTypes = TypeEvent::all();

        NotificationController::syncDueEventNotifications(auth()->id());
        return view('user.calendars.index', compact('calendarEvents', 'eventTypes'));
    }

    /**
     * Lưu lịch trình vào database
     */
    public function store(Request $request)
    {
        log::info("[$request] Đang gọi Calendar::store");
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'startTime' => 'nullable|date_format:H:i',
            'endTime' => 'nullable|date_format:H:i|after_or_equal:startTime',
            'description' => 'nullable|string',
            'type' => 'nullable|exists:type_events,key',
        ]);
            
       try {
            // 1. Tìm ID của type_event dựa trên tên được gửi lên
            $typeEventId = null;
            if ($request->has('type')) {
                $typeEvent = TypeEvent::where('key', $request->type)->first();
                
                if ($typeEvent) {
                    $typeEventId = $typeEvent->id;
                } else {
                    // Tùy chọn: Log cảnh báo nếu không tìm thấy loại sự kiện
                    Log::warning('Calendar::Store - TypeEvent không tồn tại', ['type' => $request->type]);
                }
            }

            // 2. Lưu sự kiện
            $event = Event::create([
                'user_id'       => auth()->id(),
                'title'         => $request->title,
                'date'          => $request->date,
                'start'         => $request->startTime,
                'end'           => $request->endTime,
                'note'          => $request->description,
                'type_event_id' => $typeEventId, // Sử dụng ID tìm được hoặc null
                'status'        => 'active',
            ]);
            return response()->json([
                'success' => true, 
                'message' => 'Sự kiện đã được thêm thành công!',
                'data'    => $event
            ], 201);

        } catch (\Exception $e) {
            Log::error('Calendar::Store - Lỗi hệ thống: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra.'], 500);
        }
    }

    /**
     * Cập nhật sự kiện hiện có
     */
    public function update(Request $request, $id)
    {
        log::info("[$request] Đang gọi Calendar::update");
        // Kiểm tra quyền sở hữu để tránh người dùng sửa lịch của người khác
        $event = Event::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'startTime' => 'nullable|date_format:H:i',
            'endTime' => 'nullable|date_format:H:i|after_or_equal:startTime',
            'description' => 'nullable|string',
            'type' => 'nullable|exists:type_events,key',
        ]);
        try {
            $event->update([
                'title'         => $request->title,
                'date'          => $request->date,
                'start'         => $request->startTime,
                'end'           => $request->endTime,
                'note'          => $request->description,
                'type_event_id' => TypeEvent::where('key', $request->type)->first()->id ?? null,
            ]);
            log::info("[$event] Sự kiện đã được cập nhật thành công.");
            return response()->json([
                'success' => true,
                'message' => 'Sự kiện đã được cập nhật!',
                'data'    => $event
            ]);
        } catch (\Exception $e) {
            Log::error('LỖI CẬP NHẬT: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi khi cập nhật!'], 500);
        }
    }

    /**
     * Xóa sự kiện
     */
    public function destroy($id)
    {
        try {
            Log::info('Yêu cầu xóa sự kiện với ID: ' . $id);
            // Đảm bảo chỉ xóa sự kiện thuộc về user đang đăng nhập
            $event = Event::where('user_id', auth()->id())->findOrFail($id);
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sự kiện đã được xóa!'
            ]);
        } catch (\Exception $e) {
            Log::error('LỖI XÓA: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể xóa sự kiện này!'], 500);
        }
    }
}