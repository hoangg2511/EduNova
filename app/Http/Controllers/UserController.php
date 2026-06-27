<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Sử dụng Transaction để tạo User và UserLog cùng lúc
        return DB::transaction(function () use ($request) {
            // Tạo user mới
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user', // Mặc định là user
            ]);

            $freePlan = Plan::defaultPlan();

            // 3. Tạo Subscription mặc định
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'status'  => 'active',
                'starts_at' => now(),
                'ends_at'   => null, // Gói miễn phí thường không có ngày hết hạn
            ]);
            // Tạo bản ghi UserLog mặc định cho user (quan trọng để dùng tính năng)
            UserLog::create([
                'user_id'         => $user->id,
                'token_limit'     => $freePlan->token_limit,
                'knowledge_limit' => $freePlan->knowledge_limit,
                'download_limit'  => $freePlan->download_limit,
                'duration_days'   => $freePlan->duration_days,
            ]);

            Log::info('User mới đã được tạo với ID: ' . $user->id);
            return response()->json([
                'message' => 'Đăng ký thành công!',
                'user' => $user
            ], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function registerGoogle(Request $request, FirebaseService $firebase)
    {
        $idToken = $request->input('idToken');

        Log::info('Begin Google register request', [
            'route' => '/register/google',
            'idToken_present' => !empty($idToken),
        ]);

        try {
            $verifiedIdToken = $firebase->verifyToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');

            $user = User::updateOrCreate(
                ['firebase_uid' => $uid],
                [
                    'name' => $name ?? 'Google User',
                    'email' => $email,
                    'password' => bcrypt(uniqid()),
                    'role' => 'user',
                ]
            );

            Auth::login($user);

            Log::info('Google register/login success', [
                'firebase_uid' => $uid,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'message' => 'Đăng nhập thành công'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Google register failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'route' => '/register/google',
                'idToken_present' => !empty($idToken),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Xác thực thất bại: ' . $e->getMessage()
            ], 401);
        }
    }
    public function logout(Request $request)
    {
        // 1. Thực hiện logout thông qua guard (mặc định là 'web')
        Auth::guard('web')->logout();

        // 2. Hủy session hiện tại
        $request->session()->invalidate();

        // 3. Tạo lại CSRF token để tránh tấn công session fixation
        $request->session()->regenerateToken();

        // 4. Chuyển hướng người dùng về trang đăng nhập hoặc trang chủ
        return redirect('/'); 
    }
    public function getStreak()
    {
        return response()->json([
            'streak_days' => auth()->user()->streak_days
        ]);
    }
    // public function updateStreak($user)
    // {
    //     $today = now()->startOfDay();
    //     $lastStudied = $user->last_studied_at ? $user->last_studied_at->startOfDay() : null;

    //     // Trường hợp 1: Đã học hôm nay rồi -> Không làm gì cả
    //     if ($lastStudied && $lastStudied->isSameDay($today)) {
    //         return;
    //     }

    //     // Trường hợp 2: Học vào ngày tiếp theo sau ngày cuối cùng -> Tăng streak
    //     if ($lastStudied && $lastStudied->diffInDays($today) === 1) {
    //         $user->streak_days += 1;
    //     } 
    //     // Trường hợp 3: Học lại sau một thời gian dài gián đoạn -> Reset về 1
    //     else {
    //         $user->streak_days = 1;
    //     }

    //     $user->last_studied_at = now();
    //     $user->save();
    // }
}
