<?php

declare(strict_types=1);

namespace Test\Debug;

use Neutrino\Debug\Highlight;
use PHPUnit\Framework\TestCase;

final class HighlightTest extends TestCase
{
    private string $file = '';

    protected function setUp(): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'nucleon-highlight');
        file_put_contents($this->file, "<?php\n\n\$a = 1;\n\nreturn \$a;\n");
    }

    protected function tearDown(): void
    {
        Highlight::$disabled = false;
        @unlink($this->file);
    }

    public function testHtml(): void
    {
        $this->assertTrue(Highlight::available());
        $this->assertSame('<span class="hl-keyword">SELECT</span> * <span class="hl-keyword">FROM</span> <span class="hl-type">users</span>', Highlight::html('SELECT * FROM users', 'sql'));

        Highlight::$disabled = true;

        $this->assertFalse(Highlight::available());
        $this->assertSame('SELECT &lt;b&gt;', Highlight::html('SELECT <b>', 'sql'));
    }

    public function testTerminal(): void
    {
        $this->assertStringContainsString("\e[", Highlight::terminal('SELECT 1', 'sql'));

        Highlight::$disabled = true;

        $this->assertSame('SELECT 1', Highlight::terminal('SELECT 1', 'sql'));
    }

    public function testFileFragment(): void
    {
        $html = Highlight::fileFragment($this->file, 3, 1);

        $this->assertSame([
            '<span class="hl-gutter">2</span> ',
            '<span class="hl-gutter hl-current">3</span><span class="hl-variable">$a</span> = 1;',
            '<span class="hl-gutter">4</span> ',
        ], explode("\n", $html));
    }

    public function testFileFragmentWithoutHighlighting(): void
    {
        Highlight::$disabled = true;

        $this->assertSame(
            "<span class=\"hl-gutter\">2</span> \n<span class=\"hl-gutter hl-current\">3</span>\$a = 1;\n<span class=\"hl-gutter\">4</span> ",
            Highlight::fileFragment($this->file, 3, 1),
        );
    }

    public function testFileFragmentOfAMissingFile(): void
    {
        $this->assertSame('', Highlight::fileFragment('/missing/file.php', 3));
        $this->assertSame('', Highlight::fileFragment($this->file, 0));
    }

    public function testCss(): void
    {
        $this->assertStringContainsString('.hl-keyword{', Highlight::css());
        $this->assertStringContainsString('.hl-gutter.hl-current{', Highlight::css());
    }
}
