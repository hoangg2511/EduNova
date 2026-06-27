<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Đổi tên bảng từ 'securities' (số nhiều) sang 'securities' (tên bạn muốn)
        // Lưu ý: Laravel thường dùng số nhiều (securities), 
        // nhưng nếu bạn muốn bảng tên là 'security', hãy dùng dòng dưới:
        Schema::rename('securities', 'security'); // Nếu bạn muốn đổi sang tên bảng khác, ví dụ 'security_configs'
    }

    public function down()
    {
        Schema::rename('security', 'securities');
    }
};
