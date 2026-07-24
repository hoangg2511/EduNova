<?php

namespace Tests\Unit;

use App\Services\DocumentSecurityService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
class DocumentSecurityServiceTest extends TestCase
{
    protected DocumentSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentSecurityService();
    }

    /** @test */
    public function no_chan_file_pdf_hop_le()
    {
        $file = UploadedFile::fake()->createWithContent(
            'valid.pdf',
            file_get_contents(base_path('tests/fixtures/documents/valid.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertContains($result['status'], ['passed', 'flagged']);
        $this->assertTrue($result['checks']['signature']['valid']);
    }

    /** @test */
    public function chan_file_gia_mao_duoi_mo_rong()
    {
        // file .exe đổi tên thành .pdf
        $file = UploadedFile::fake()->createWithContent(
            'fake.pdf',
            file_get_contents(base_path('tests/fixtures/documents/fake.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertEquals('failed', $result['status']);
        $this->assertFalse($result['checks']['signature']['valid']);
    }

    /** @test */
    public function trich_xuat_duoc_noi_dung_tu_pdf_that()
    {
        $file = UploadedFile::fake()->createWithContent(
            'valid.pdf',
            file_get_contents(base_path('tests/fixtures/documents/valid.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertNotNull($result['extracted_text']);
        $this->assertGreaterThan(30, mb_strlen($result['extracted_text']));
    }

    /** @test */
    public function gan_co_flagged_khi_noi_dung_qua_ngan()
    {
        $file = UploadedFile::fake()->createWithContent(
            'empty.pdf',
            file_get_contents(base_path('tests/fixtures/documents/empty.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertEquals('flagged', $result['status']);
        $this->assertTrue($result['checks']['content']['suspicious']);
    }

    /** @test */
    public function khong_chan_khi_khong_trich_xuat_duoc_noi_dung_doc_cu()
    {
        // .doc binary cũ -> extraction luôn null, không được coi là suspicious
        $file = UploadedFile::fake()->create('old.doc', 100);

        $result = $this->service->scan($file);

        $this->assertNull($result['extracted_text']);
        $this->assertFalse($result['checks']['content']['suspicious'] ?? false);
    }

    /** @test */
    public function bo_qua_virus_scan_khi_chua_cau_hinh_clamav()
    {
        config(['services.clamav.url' => null]);

        $file = UploadedFile::fake()->createWithContent(
            'valid.pdf',
            file_get_contents(base_path('tests/fixtures/documents/valid.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertEquals('skipped', $result['checks']['virus']['status']);
    }
        /** @test */
    public function chan_file_khi_clamav_bao_nhiem_virus()
    {
        config(['services.clamav.url' => 'http://fake-clamav/scan']);

        Http::fake([
            'fake-clamav/*' => Http::response(['Status' => 'FOUND', 'Signature' => 'Test.Virus'], 200),
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'valid.pdf',
            file_get_contents(base_path('tests/fixtures/documents/valid.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('infected', $result['checks']['virus']['status']);
    }

    /** @test */
    public function danh_dau_flagged_khi_clamav_loi_ket_noi()
    {
        config(['services.clamav.url' => 'http://fake-clamav/scan']);
        Http::fake(['fake-clamav/*' => Http::response([], 500)]);

        $file = UploadedFile::fake()->createWithContent(
            'valid.pdf',
            file_get_contents(base_path('tests/fixtures/documents/valid.pdf'))
        );

        $result = $this->service->scan($file);

        $this->assertEquals('error', $result['checks']['virus']['status']);
    }
}