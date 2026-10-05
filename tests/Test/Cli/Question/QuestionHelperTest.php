<?php

declare(strict_types=1);

namespace Test\Cli\Question;

use Neutrino\Cli\Output\QuestionHelper;
use Neutrino\Cli\Output\Writer;
use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;
use Neutrino\Debug\Reflexion;
use Test\TestCase\TestCase;

class QuestionHelperTest extends TestCase
{
    protected static $file = __DIR__ . '/test';

    protected $stdin;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->closeStdIn();

        @unlink(self::$file);
    }

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(self::$file);
    }

    protected function getStdIn()
    {
        if (isset($this->stdin)) {
            return $this->stdin;
        }

        return $this->stdin = fopen(self::$file, 'r');
    }

    protected function closeStdIn()
    {
        if (isset($this->stdin)) {
            fclose($this->stdin);
            $this->stdin = null;
        }
    }

    public function mockStdIn($mock)
    {
        file_put_contents(self::$file, $mock);
    }

    public function testDoAsk(): void
    {
        $output = $this->createStub(Writer::class);
        $question = new Question('test');

        $this->mockStdIn('test');

        $this->assertEquals(
            'test',
            Reflexion::invoke(QuestionHelper::class, 'doAsk', $output, $this->getStdIn(), $question),
        );
    }

    /**
     * @return array
     */
    public static function dataAskQuestion(): array
    {
        return [
            ['', "\n", new Question('test')],
            ['abc', "\n", new Question('test', 'abc')],
            [false, "\n", new ConfirmationQuestion('Ask this', false)],
            [true, "\n", new ConfirmationQuestion('Ask this', true)],
            [true, "y", new ConfirmationQuestion('Ask this', false)],
            [false, "n", new ConfirmationQuestion('Ask this', true)],
            ['a', "\n", new ChoiceQuestion('Ask this', ['a', 'b', 'c'], 'a')],
            ['b', "b", new ChoiceQuestion('Ask this', ['a', 'b', 'c'], 'a')],
            ['b', "1", new ChoiceQuestion('Ask this', ['a', 'b', 'c'], 'a')],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataAskQuestion')]
    public function testAskQuestion($expected, $mock, $question): void
    {
        $output = $this->createStub(Writer::class);

        $this->mockStdIn($mock);

        $result = Reflexion::invoke(QuestionHelper::class, 'ask', $output, $this->getStdIn(), $question);

        $this->assertEquals($expected, $result);
    }

    public function testAskChoiceQuestionMultiAttemps(): void
    {
        $output = $this->createStub(Writer::class);

        $this->mockStdIn("\n\nb");
        $question = new ChoiceQuestion('Ask this', ['a', 'b', 'c'], 'a', 3);

        $result = Reflexion::invoke(QuestionHelper::class, 'ask', $output, $this->getStdIn(), $question);

        $this->assertEquals('b', $result);
    }
}
