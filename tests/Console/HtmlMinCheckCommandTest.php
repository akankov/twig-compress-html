<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests\Console;

use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\Console\HtmlMinCheckCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class HtmlMinCheckCommandTest extends TestCase
{
    public function testReportsByteSavingsWithoutTouchingTheFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'twig-htmlmin');
        self::assertNotFalse($path);
        $verbose = "<html>\n  <body>\n    <p>hello   world</p>\n  </body>\n</html>\n";
        file_put_contents($path, $verbose);

        $tester = new CommandTester(new HtmlMinCheckCommand(new HtmlMin()));
        $exit = $tester->execute(['file' => $path]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertStringContainsString('Reduced from', $tester->getDisplay());
        self::assertStringContainsString('B to', $tester->getDisplay());
        self::assertSame($verbose, file_get_contents($path), 'the file must not be modified');
    }

    public function testMissingFileFailsWithError(): void
    {
        $tester = new CommandTester(new HtmlMinCheckCommand(new HtmlMin()));
        $exit = $tester->execute(['file' => '/nope/does/not/exist.html']);

        self::assertSame(Command::FAILURE, $exit);
        self::assertStringContainsString('Cannot read input file', $tester->getDisplay());
    }

    public function testKilobyteAndPercentFormatting(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'twig-htmlmin-kb');
        self::assertNotFalse($path);
        // > 1 KiB of collapsible whitespace so the KB branch is exercised.
        file_put_contents($path, '<p>' . str_repeat('x  y  ', 400) . '</p>');

        $tester = new CommandTester(new HtmlMinCheckCommand(new HtmlMin()));
        $exit = $tester->execute(['file' => $path]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertStringContainsString('KB', $tester->getDisplay());
        self::assertStringContainsString('-', $tester->getDisplay());
    }
}
