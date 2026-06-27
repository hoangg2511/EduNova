@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[50vh] text-center p-6">
    <div class="bg-yellow-100 p-4 rounded-full mb-4">
        <svg class="w-16 h-16 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-800">Thanh toán đã bị hủy</h1>
    <p class="text-gray-600 mt-2">Bạn đã hủy giao dịch. Đơn hàng của bạn vẫn được lưu lại.</p>
    <a href="/" class="mt-6 bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">Về trang chủ</a>
</div>
@endsection