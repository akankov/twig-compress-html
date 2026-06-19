<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Http;

use Akankov\HtmlMin\HtmlMin;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Opt-in `kernel.response` listener that buffers `text/html` responses and
 * runs them through {@see HtmlMin} on the way out.
 *
 * Mirrors the engine's {@see \Akankov\HtmlMin\Middleware\MinifierMiddleware}
 * content-type policy — split the `Content-Type` on `;`, lowercase, allowlist
 * `text/html` — so it sits safely in front of mixed JSON / HTML stacks. It is
 * the Symfony-native counterpart to the Laravel binding's
 * `MinifyHtmlResponseMiddleware`.
 *
 * Registered only when the bundle's `minify_responses` option is `true`; the
 * bundle never wires it onto the response pipeline by default. Sub-requests,
 * streamed responses, and binary-file responses pass through untouched.
 */
final readonly class MinifyHtmlResponseListener
{
    public function __construct(private HtmlMin $minifier)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        if (!self::isHtml($response->headers->get('Content-Type'))) {
            return;
        }

        $response->setContent($this->minifier->minify((string) $response->getContent()));

        // Minifying shrinks the body, so any Content-Length an upstream layer
        // already set is now too large — clients would truncate or over-read the
        // response. Drop it and let Symfony's prepare() re-derive the correct length.
        $response->headers->remove('Content-Length');
    }

    private static function isHtml(?string $contentType): bool
    {
        if ($contentType === null || $contentType === '') {
            return false;
        }

        return strtolower(trim(explode(';', $contentType, 2)[0])) === 'text/html';
    }
}
