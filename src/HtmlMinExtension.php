<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml;

use Akankov\TwigCompressHtml\TokenParser\HtmlMinTokenParser;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class HtmlMinExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'html_min',
                [HtmlMinRuntime::class, 'minify'],
                ['is_safe' => ['html']],
            ),
        ];
    }

    #[Override]
    public function getTokenParsers(): array
    {
        return [new HtmlMinTokenParser()];
    }
}
