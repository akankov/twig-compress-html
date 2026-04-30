<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\TokenParser;

use Akankov\TwigCompressHtml\Node\HtmlMinNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class HtmlMinTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): \Akankov\TwigCompressHtml\Node\HtmlMinNode
    {
        $line = $token->getLine();
        $stream = $this->parser->getStream();
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decideEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new HtmlMinNode($body, $line, $this->getTag());
    }

    public function decideEnd(Token $token): bool
    {
        return $token->test('endhtmlmin');
    }

    public function getTag(): string
    {
        return 'htmlmin';
    }
}
