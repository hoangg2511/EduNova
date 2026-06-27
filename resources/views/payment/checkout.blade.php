<!DOCTYPE html>
<html>
<head>
    <title>Thanh toán SePay</title>
</head>
<body>
    <h3>Vui lòng xác nhận thanh toán</h3>

    {{-- Form POST thẳng đến SePay, không qua Laravel --}}
    <form method="GET" action="https://pay-sandbox.sepay.vn/v1/checkout" id="sepay-form">
        
        @foreach ($formFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <button type="submit" style="padding: 10px 20px; font-size: 16px;">
            Thanh toán ngay
        </button>
    </form>
</body>
</html>