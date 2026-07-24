<?php

namespace Tests\Unit;

use App\Models\Document;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    public function test_scan_result_is_cast_to_array(): void
    {
        $document = new Document();
        $document->scan_result = [
            'signature' => ['valid' => true],
            'virus' => ['status' => 'skipped'],
        ];

        $this->assertSame([
            'signature' => ['valid' => true],
            'virus' => ['status' => 'skipped'],
        ], $document->scan_result);
        $this->assertSame('array', $document->getCasts()['scan_result']);
    }
}
