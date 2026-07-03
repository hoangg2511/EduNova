<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Controllers\NotificationController;
use App\Models\Event;
use App\Models\TypeEvent;

class CalendarController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $events = Event::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->with('typeEvent')
                    ->get();

        $calendarEvents = $events->map(fn ($event) => $this->mapEventForFrontend($event));

        $eventTypes = TypeEvent::all();

        NotificationController::syncDueEventNotifications(auth()->id());
        return view('user.calendars.index', compact('calendarEvents', 'eventTypes'));
    }

    /**
     * Chuẩn hóa dữ liệu event trả về cho frontend (dùng chung cho index/store/update)
     */
    private function mapEventForFrontend(Event $event): array
    {
        $event->loadMissing('typeEvent');

        return [
            'id'          => $event->id,
            'title'       => $event->title,
            'type'        => $event->typeEvent ? $event->typeEvent->key : 'study',
            'date'        => $event->date ? Carbon::parse($event->date)->format('Y-m-d') : null,
            'startTime'   => $event->start ? Carbon::parse($event->start)->format('H:i') : null,
            'endTime'     => $event->end ? Carbon::parse($event->end)->format('H:i') : null,
            'description' => $event->note,
            'repeat'      => $event->repeat_type ?? 'none',
            'repeatEnd'   => $event->repeat_end_date ? Carbon::parse($event->repeat_end_date)->format('Y-m-d') : null,
            'groupId'     => $event->repeat_group_id,
        ];
    }

    /**
     * Sinh danh sách ngày lặp lại theo loại (daily/weekly/monthly) từ $startDate đến $endDate
     */
    private function generateRecurrenceDates(string $startDate, string $endDate, string $repeatType): array
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $dates = [];

        if ($end->lt($start)) {
            return [$start->format('Y-m-d')];
        }

        switch ($repeatType) {
            case 'daily':
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $dates[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }
                break;

            case 'weekly':
                // Lặp lại đúng thứ trong tuần của ngày bắt đầu
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $dates[] = $cursor->format('Y-m-d');
                    $cursor->addWeek();
                }
                break;

            case 'monthly':
                // Lặp lại đúng ngày trong tháng của ngày bắt đầu, tự động
                // hạ về ngày cuối cùng nếu tháng đó không đủ số ngày (vd 31/2 -> 28/2)
                $originalDay = $start->day;
                $cursor       = $start->copy()->startOfMonth();
                $endMonth     = $end->copy()->startOfMonth();

                while ($cursor->lte($endMonth)) {
                    $targetDay  = min($originalDay, $cursor->daysInMonth);
                    $occurrence = $cursor->copy()->day($targetDay);

                    if ($occurrence->betweenIncluded($start, $end)) {
                        $dates[] = $occurrence->format('Y-m-d');
                    }
                    $cursor->addMonth();
                }
                break;

            default:
                $dates[] = $start->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Lưu lịch trình vào database (hỗ trợ tạo hàng loạt nếu có lặp lại)
     */
    public function store(Request $request)
    {
        Log::info('Đang gọi Calendar::store', ['payload' => $request->all()]);
        
        $this->normalizeTimeInput($request, ['startTime', 'endTime']);
        $request->validate([
            'title'       => 'required|string|max:255',
            'date'        => 'required|date',
            'startTime'   => 'nullable|date_format:H:i',
            'endTime'     => 'nullable|date_format:H:i|after_or_equal:startTime',
            'description' => 'nullable|string',
            'type'        => 'nullable|exists:type_events,key',
            'repeat'      => 'nullable|in:none,daily,weekly,monthly',
            'repeatEnd'   => 'nullable|date|after_or_equal:date|required_unless:repeat,none',
        ]);

        try {
            $typeEventId = null;
            if ($request->filled('type')) {
                $typeEvent = TypeEvent::where('key', $request->type)->first();
                if ($typeEvent) {
                    $typeEventId = $typeEvent->id;
                } else {
                    Log::warning('Calendar::Store - TypeEvent không tồn tại', ['type' => $request->type]);
                }
            }

            $repeatType    = $request->input('repeat', 'none');
            $createdEvents = [];

            if ($repeatType === 'none' || !$request->filled('repeatEnd')) {
                // Sự kiện đơn lẻ
                $createdEvents[] = Event::create([
                    'user_id'         => auth()->id(),
                    'title'           => $request->title,
                    'date'            => $request->date,
                    'start'           => $request->startTime,
                    'end'             => $request->endTime,
                    'note'            => $request->description,
                    'type_event_id'   => $typeEventId,
                    'status'          => 'active',
                    'repeat_type'     => 'none',
                    'repeat_end_date' => null,
                    'repeat_group_id' => null,
                ]);
            } else {
                // Sự kiện lặp lại: sinh danh sách ngày rồi tạo hàng loạt, gom nhóm bằng repeat_group_id
                $dates   = $this->generateRecurrenceDates($request->date, $request->repeatEnd, $repeatType);
                $groupId = (string) Str::uuid();

                foreach ($dates as $d) {
                    $createdEvents[] = Event::create([
                        'user_id'         => auth()->id(),
                        'title'           => $request->title,
                        'date'            => $d,
                        'start'           => $request->startTime,
                        'end'             => $request->endTime,
                        'note'            => $request->description,
                        'type_event_id'   => $typeEventId,
                        'status'          => 'active',
                        'repeat_type'     => $repeatType,
                        'repeat_end_date' => $request->repeatEnd,
                        'repeat_group_id' => $groupId,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($createdEvents) > 1
                    ? 'Đã thêm ' . count($createdEvents) . ' sự kiện lặp lại thành công!'
                    : 'Sự kiện đã được thêm thành công!',
                'data'    => collect($createdEvents)->map(fn ($e) => $this->mapEventForFrontend($e))->values(),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Calendar::Store - Lỗi hệ thống: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra.'], 500);
        }
    }

    /**
     * Cập nhật sự kiện hiện có.
     * - Nếu sự kiện không lặp và vẫn không lặp -> update bình thường.
     * - Nếu chuyển sang lặp / thay đổi quy tắc lặp -> xóa chuỗi cũ (nếu có) và sinh lại chuỗi mới.
     * - Nếu chỉ sửa nội dung (title/giờ/mô tả) mà giữ nguyên quy tắc lặp -> chỉ update đúng bản ghi đó.
     */
    public function update(Request $request, $id)
    {
        Log::info('Đang gọi Calendar::update', ['id' => $id, 'payload' => $request->all()]);

        $event = Event::where('user_id', auth()->id())->findOrFail($id);
        $this->normalizeTimeInput($request, ['startTime', 'endTime']);
        $request->validate([
            'title'       => 'required|string|max:255',
            'date'        => 'required|date',
            'startTime'   => 'nullable|date_format:H:i',
            'endTime'     => 'nullable|date_format:H:i|after_or_equal:startTime',
            'description' => 'nullable|string',
            'type'        => 'nullable|exists:type_events,key',
            'repeat'      => 'nullable|in:none,daily,weekly,monthly',
            'repeatEnd'   => 'nullable|date|after_or_equal:date|required_unless:repeat,none',
        ]);

        try {
            $typeEventId = null;
            if ($request->filled('type')) {
                $typeEvent = TypeEvent::where('key', $request->type)->first();
                if ($typeEvent) {
                    $typeEventId = $typeEvent->id;
                } else {
                    Log::warning('Calendar::Update - TypeEvent không tồn tại', ['type' => $request->type]);
                }
            }

            $repeatType = $request->input('repeat', 'none');
            $oldGroupId = $event->repeat_group_id;

            $oldRepeatEnd            = $event->repeat_end_date ? Carbon::parse($event->repeat_end_date)->format('Y-m-d') : null;
            $oldDateOfThisOccurrence = $event->date ? Carbon::parse($event->date)->format('Y-m-d') : null;
            $repeatChanged = $repeatType !== ($event->repeat_type ?? 'none')
                || $request->date !== $oldDateOfThisOccurrence
                || $request->repeatEnd !== $oldRepeatEnd;

            // ══════════════════════════════════════════════
            // TH1: KHÔNG lặp -> sự kiện đơn lẻ
            // ══════════════════════════════════════════════
            if ($repeatType === 'none' || !$request->filled('repeatEnd')) {
                $scope = 'single';

                if ($event->repeat_group_id) {
                    Event::where('repeat_group_id', $event->repeat_group_id)
                        ->where('id', '!=', $event->id)
                        ->where('user_id', auth()->id())
                        ->delete();
                }

                $event->update([
                    'title'           => $request->title,
                    'date'            => $request->date,
                    'start'           => $request->startTime,
                    'end'             => $request->endTime,
                    'note'            => $request->description,
                    'type_event_id'   => $typeEventId,
                    'repeat_type'     => 'none',
                    'repeat_end_date' => null,
                    'repeat_group_id' => null,
                ]);

                $resultEvents = [$event];

            // ══════════════════════════════════════════════
            // TH2: CÓ lặp, quy tắc lặp KHÔNG đổi, event đã thuộc 1 chuỗi sẵn có
            //      -> chỉ sửa nội dung của đúng occurrence này
            // ══════════════════════════════════════════════
            } elseif (!$repeatChanged && $event->repeat_group_id) {
                $scope = 'occurrence';

                $event->update([
                    'title'         => $request->title,
                    'start'         => $request->startTime,
                    'end'           => $request->endTime,
                    'note'          => $request->description,
                    'type_event_id' => $typeEventId,
                ]);

                $resultEvents = [$event];

            // ══════════════════════════════════════════════
            // TH3: CÓ lặp, và (quy tắc lặp đổi HOẶC event hiện chưa có chuỗi)
            //      -> xóa cũ, SINH LẠI CHUỖI MỚI
            // ══════════════════════════════════════════════
            } else {
                $scope = 'series';

                // Mặc định dùng ngày người dùng gửi lên làm mốc bắt đầu mới
                $seriesStartDate = $request->date;

                if ($event->repeat_group_id) {
                    // FIX QUAN TRỌNG: nếu người dùng KHÔNG chủ động đổi "Lặp từ ngày"
                    // (request->date vẫn bằng đúng ngày của occurrence đang sửa),
                    // thì phải dùng NGÀY BẮT ĐẦU GỐC của cả chuỗi (MIN(date) trong nhóm),
                    // KHÔNG được dùng ngày của riêng occurrence đang sửa —
                    // nếu không, các occurrence trước occurrence này sẽ bị mất khi regenerate.
                    if ($request->date === $oldDateOfThisOccurrence) {
                        $groupMinDate = Event::where('repeat_group_id', $event->repeat_group_id)
                            ->where('user_id', auth()->id())
                            ->min('date');

                        if ($groupMinDate) {
                            $seriesStartDate = Carbon::parse($groupMinDate)->format('Y-m-d');
                        }
                    }

                    Event::where('repeat_group_id', $event->repeat_group_id)
                        ->where('user_id', auth()->id())
                        ->delete();
                } else {
                    // Event đang đơn lẻ, giờ mới chuyển sang lặp -> dùng đúng ngày người dùng nhập
                    $event->delete();
                }

                $dates        = $this->generateRecurrenceDates($seriesStartDate, $request->repeatEnd, $repeatType);
                $groupId      = (string) Str::uuid();
                $resultEvents = [];

                foreach ($dates as $d) {
                    $resultEvents[] = Event::create([
                        'user_id'         => auth()->id(),
                        'title'           => $request->title,
                        'date'            => $d,
                        'start'           => $request->startTime,
                        'end'             => $request->endTime,
                        'note'            => $request->description,
                        'type_event_id'   => $typeEventId,
                        'status'          => 'active',
                        'repeat_type'     => $repeatType,
                        'repeat_end_date' => $request->repeatEnd,
                        'repeat_group_id' => $groupId,
                    ]);
                }
            }

            Log::info('Sự kiện đã được cập nhật thành công.', ['id' => $id, 'scope' => $scope]);

            return response()->json([
                'success'    => true,
                'message'    => count($resultEvents) > 1
                    ? 'Đã cập nhật và tạo ' . count($resultEvents) . ' sự kiện lặp lại thành công!'
                    : 'Sự kiện đã được cập nhật!',
                'scope'      => $scope,
                'data'       => collect($resultEvents)->map(fn ($e) => $this->mapEventForFrontend($e))->values(),
                'oldGroupId' => $oldGroupId,
            ]);
        } catch (\Exception $e) {
            Log::error('LỖI CẬP NHẬT: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi khi cập nhật!'], 500);
        }
    }

    /**
     * Xóa sự kiện (tùy chọn xóa cả chuỗi lặp lại nếu deleteSeries=1)
     */
    public function destroy(Request $request, $id)
    {
        try {
            Log::info('Yêu cầu xóa sự kiện với ID: ' . $id);
            $event = Event::where('user_id', auth()->id())->findOrFail($id);

            $deleteSeries = $request->boolean('deleteSeries');
            $deletedIds   = [$event->id];

            if ($deleteSeries && $event->repeat_group_id) {
                $deletedIds = Event::where('repeat_group_id', $event->repeat_group_id)
                    ->where('user_id', auth()->id())
                    ->pluck('id')
                    ->toArray();

                Event::where('repeat_group_id', $event->repeat_group_id)
                    ->where('user_id', auth()->id())
                    ->delete();
            } else {
                $event->delete();
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Sự kiện đã được xóa!',
                'deletedIds' => $deletedIds,
            ]);
        } catch (\Exception $e) {
            Log::error('LỖI XÓA: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể xóa sự kiện này!'], 500);
        }
    }
    private function normalizeTimeInput(Request $request, array $fields): void
    {
        $normalized = [];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value && preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $value, $m)) {
                $normalized[$field] = $m[1];
            }
        }
        if ($normalized) {
            $request->merge($normalized);
        }
    }
}