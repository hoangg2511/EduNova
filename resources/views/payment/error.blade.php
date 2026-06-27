@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[50vh] text-center p-6">
    <div class="bg-red-100 p-4 rounded-full mb-4">
        <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-800">Thanh toán thất bại!</h1>
    <p class="text-gray-600 mt-2">Đã có lỗi xảy ra trong quá trình xử lý giao dịch. Vui lòng thử lại.</p>
    <a href="/checkout" class="mt-6 bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">Thử lại</a>
</div>
@endsection