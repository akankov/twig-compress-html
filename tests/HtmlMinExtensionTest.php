<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests;

use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\HtmlMinExtension;
use Akankov\TwigCompressHtml\HtmlMinRuntime;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

final class HtmlMinExtensionTest extends TestCase
{
    /**
     * @param array<string, string> $templates
     */
    private function buildEnv(array $templates): Environment
    {
        $env = new Environment(new ArrayLoader($templates), [
            'autoescape' => 'html',
            'cache' => false,
            'strict_variables' => true,
        ]);
        $env->addExtension(new HtmlMinExtension());
        $env->addRuntimeLoader(new FactoryRuntimeLoader([
            HtmlMinRuntime::class => static fn (): HtmlMinRuntime => new HtmlMinRuntime(new HtmlMin()),
        ]));

        return $env;
    }

    public function testFilterMinifiesWhitespace(): void
    {
        $env = $this->buildEnv([
            't' => "{{ '<html>  <body>x</body>  </html>'|html_min }}",
        ]);
        $output = $env->render('t');

        self::assertStringNotContainsString('  ', $output);
        self::assertStringContainsString('<body>x', $output);
    }

    public function testTagMinifiesAndInterpolates(): void
    {
        $env = $this->buildEnv([
            't' => '{% htmlmin %}<html>  <body>{{ name }}</body>  </html>{% endhtmlmin %}',
        ]);
        $output = $env->render('t', ['name' => 'x']);

        self::assertStringNotContainsString('  ', $output);
        self::assertStringContainsString('<body>x', $output);
    }

    public function testFilterOutputIsNotDoubleEscaped(): void
    {
        $env = $this->buildEnv([
            't' => "{{ '<html><body>x</body></html>'|html_min }}",
        ]);
        $output = $env->render('t');

        self::assertStringContainsString('<body>', $output);
        self::assertStringNotContainsString('&lt;body&gt;', $output);
    }

    public function testTagWithoutClosingThrowsSyntaxError(): void
    {
        $env = $this->buildEnv(['t' => '{% htmlmin %}<html></html>']);

        $this->expectException(SyntaxError::class);
        $env->render('t');
    }

    public function testNestedVariablesAreEscapedInsideTag(): void
    {
        $env = $this->buildEnv([
            't' => '{% htmlmin %}<p>{{ user }}</p>{% endhtmlmin %}',
        ]);
        $output = $env->render('t', ['user' => '<script>alert(1)</script>']);

        self::assertStringNotContainsString('<script>alert(1)</script>', $output);
        self::assertStringContainsString('&lt;script&gt;', $output);
    }
}
