<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $this->auth = $factory->createAuth();
    }

    public function verifyToken(string $idToken)
    {
        return $this->auth->verifyIdToken($idToken);
    }
    public function registerOrLoginGoogle(string $idToken): array
    {
        // 1. Xác thực với Firebase
        $verifiedIdToken = $this->auth->verifyIdToken($idToken);
        $uid = $verifiedIdToken->claims()->get('sub');
        $email = $verifiedIdToken->claims()->get('email');
        $name = $verifiedIdToken->claims()->get('name');

        // 2. Tự động Đăng ký hoặc Đăng nhập
        $user = User::updateOrCreate(
            ['firebase_uid' => $uid], // Tìm dựa trên UID
            [
                'email' => $email,
                'name'  => $name,
                'password' => bcrypt(uniqid()) // Password ngẫu nhiên cho user Google
            ]
        );

        return [
            'status' => 'success',
            'user' => $user,
            'message' => 'Đăng nhập thành công'
        ];
    }
}