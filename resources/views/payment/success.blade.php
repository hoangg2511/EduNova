@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[50vh] text-center p-6">
    <div class="bg-green-100 p-4 rounded-full mb-4">
        <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-800">Thanh toán thành công!</h1>
    <p class="text-gray-600 mt-2">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.</p>
    <a href="/" class="mt-6 bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Về trang chủ</a>
</div>
@endsection