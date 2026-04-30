<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Node;

use Akankov\TwigCompressHtml\HtmlMinRuntime;
use Override;
use Twig\Compiler;
use Twig\Node\Node;

final class HtmlMinNode extends Node
{
    public function __construct(Node $body, int $lineno, string $tag)
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write('echo $this->env->getRuntime(')
            ->string(HtmlMinRuntime::class)
            ->raw(")->minify(ob_get_clean());\n");
    }
}
