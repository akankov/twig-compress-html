<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml;

use Akankov\HtmlMin\HtmlMin;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class HtmlMinRuntime implements RuntimeExtensionInterface
{
    public function __construct(private HtmlMin $htmlMin)
    {
    }

    public function minify(string $html): string
    {
        return $this->htmlMin->minify($html);
    }
}
