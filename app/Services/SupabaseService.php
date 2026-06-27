<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    /**
     * Test kết nối tới Supabase
     */
    /**
     * Test kết nối tới Supabase Storage
     * Lấy danh sách các buckets hiện có
     */
    public function listAllBuckets()
    {
        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ])->get("{$this->url}/storage/v1/bucket")->json();
    }

    /**
     * Upload tài liệu vào Storage của Supabase
     * @param string $bucket Tên bucket trong Supabase
     * @param string $path Đường dẫn lưu file
     * @param mixed $fileContent Nội dung file (dùng file_get_contents)
     * @param string $contentType MIME type của file
     */
    public function uploadDocument(string $bucket, string $path, $fileContent, string $contentType = 'application/octet-stream')
    {
        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => $contentType,
        ])->withBody($fileContent, $contentType)
          ->post("{$this->url}/storage/v1/object/{$bucket}/{$path}");
    }

    public function getSignedUrl(string $bucket, string $path, int $expiresIn = 3600)
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ])->post("{$this->url}/storage/v1/object/sign/{$bucket}/{$path}", [
            'expiresIn' => $expiresIn
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Lưu ý: $data['signedURL'] là path tương đối, cần ghép với $this->url
            return $this->url . '/storage/v1' . $data['signedURL'];
        }

        return $response->body();
    }

    public function downloadFile(string $bucket, string $path)
    {
        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ])->get("{$this->url}/storage/v1/object/public/{$bucket}/{$path}");
    }


    public function uploadImage($file, string $bucket, string $folder = 'uploads')
    {
        // Tạo tên file ngẫu nhiên để tránh trùng lặp
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = "{$folder}/{$fileName}";
        
        // Gọi lại hàm uploadDocument đã có của bạn
        return $this->uploadDocument(
            $bucket, 
            $path, 
            file_get_contents($file->getRealPath()), 
            $file->getMimeType()
        );
    }
    public function getPublicUrl(string $bucket, string $path): string
    {
        // Cấu trúc URL tiêu chuẩn của Supabase cho file công khai
        return "{$this->url}/storage/v1/object/public/{$bucket}/{$path}";
    }
}