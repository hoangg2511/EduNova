<?php

namespace Tests\Unit;

use App\Http\Controllers\ChatbotController;
use App\Services\AIAgentService;
use PHPUnit\Framework\TestCase;

class ChatbotControllerTest extends TestCase
{
    public function testNormalizeExamPayloadConvertsBoolLikeQuestionsToTrueFalseFormat(): void
    {
        $controller = new ChatbotController($this->createMock(AIAgentService::class));

        $method = new \ReflectionMethod(ChatbotController::class, 'normalizeExamPayload');
        $method->setAccessible(true);

        $payload = [
            'title' => 'Giải tích',
            'description' => 'Bài kiểm tra ngắn',
            'duration' => '30',
            'passMark' => '60',
            'questions' => [
                [
                    'text' => 'Hàm số f(x)=1/x liên tục tại x=0.',
                    'type' => 'single',
                    'points' => '1',
                    'options' => ['Đúng', 'Sai'],
                    'correctAnswers' => [1],
                    'explanation' => '',
                ],
                [
                    'text' => 'Đạo hàm của x^2 là gì?',
                    'type' => 'single',
                    'points' => 1,
                    'options' => ['2x', 'x', '1'],
                    'correctAnswers' => [0],
                    'explanation' => 'Đạo hàm của x^2 là 2x.',
                ],
            ],
        ];

        $normalized = $method->invoke($controller, $payload);

        $this->assertSame('Giải tích', $normalized['title']);
        $this->assertSame(30, $normalized['duration']);
        $this->assertSame(60, $normalized['passMark']);
        $this->assertCount(2, $normalized['questions']);
        $this->assertSame('truefalse', $normalized['questions'][0]['type']);
        $this->assertSame(['true', 'false'], $normalized['questions'][0]['options']);
        $this->assertSame(['false'], $normalized['questions'][0]['correctAnswers']);
        $this->assertSame('single', $normalized['questions'][1]['type']);
        $this->assertSame([0], $normalized['questions'][1]['correctAnswers']);
    }
}
