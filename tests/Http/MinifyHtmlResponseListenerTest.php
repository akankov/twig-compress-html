<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests\Http;

use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\Http\MinifyHtmlResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MinifyHtmlResponseListenerTest extends TestCase
{
    public function testMinifiesHtmlResponse(): void
    {
        $response = new Response(
            '<html>  <body><!-- x -->hi</body>  </html>',
            200,
            ['Content-Type' => 'text/html'],
        );

        $this->dispatch($response);

        self::assertStringNotContainsString('<!-- x -->', (string) $response->getContent());
        self::assertStringNotContainsString('  ', (string) $response->getContent());
        self::assertStringContainsString('<body>hi', (string) $response->getContent());
    }

    public function testMinifiesHtmlResponseWithCharsetParameter(): void
    {
        $response = new Response(
            '<html>  <body>hi</body>  </html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );

        $this->dispatch($response);

        self::assertStringNotContainsString('  ', (string) $response->getContent());
    }

    public function testLeavesNonHtmlResponseUntouched(): void
    {
        $json = '{ "a":  1 }';
        $response = new Response($json, 200, ['Content-Type' => 'application/json']);

        $this->dispatch($response);

        self::assertSame($json, (string) $response->getContent());
    }

    public function testLeavesResponseWithoutContentTypeUntouched(): void
    {
        $body = '<html>  <body>hi</body>  </html>';
        $response = new Response($body);
        $response->headers->remove('Content-Type');

        $this->dispatch($response);

        self::assertSame($body, (string) $response->getContent());
    }

    public function testIgnoresStreamedResponse(): void
    {
        $streamed = new StreamedResponse(static function (): void {
            echo '<html>  <body>hi</body>  </html>';
        }, 200, ['Content-Type' => 'text/html']);

        // No exception and nothing buffered: a StreamedResponse has no content
        // to rewrite, so the listener must skip it.
        $this->dispatch($streamed);

        self::assertFalse($streamed->getContent());
    }

    public function testIgnoresSubRequest(): void
    {
        $body = '<html>  <body>hi</body>  </html>';
        $response = new Response($body, 200, ['Content-Type' => 'text/html']);

        $this->dispatch($response, HttpKernelInterface::SUB_REQUEST);

        self::assertSame($body, (string) $response->getContent());
    }

    private function dispatch(Response $response, int $requestType = HttpKernelInterface::MAIN_REQUEST): void
    {
        $listener = new MinifyHtmlResponseListener(new HtmlMin());
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            $requestType,
            $response,
        );

        $listener->onKernelResponse($event);
    }
}
