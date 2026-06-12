<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests\Bundle;

use Akankov\TwigCompressHtml\Bundle\AkankovTwigCompressHtmlBundle;
use Akankov\TwigCompressHtml\Console\HtmlMinCheckCommand;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class AkankovTwigCompressHtmlBundleTest extends TestCase
{
    private static string $tmp;

    /** @var callable|null */
    private $baselineExceptionHandler;
    /** @var callable|null */
    private $baselineErrorHandler;

    public static function setUpBeforeClass(): void
    {
        self::$tmp = sys_get_temp_dir().'/akankov-twig-compress-html-test-'.bin2hex(random_bytes(6));
        mkdir(self::$tmp.'/templates', 0o777, true);
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(self::$tmp);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->baselineExceptionHandler = set_exception_handler(static fn (): null => null);
        restore_exception_handler();
        $this->baselineErrorHandler = set_error_handler(static fn (): false => false);
        restore_error_handler();
    }

    protected function tearDown(): void
    {
        while (true) {
            $current = set_exception_handler(static fn (): null => null);
            restore_exception_handler();
            if ($current === $this->baselineExceptionHandler || null === $current) {
                break;
            }
            restore_exception_handler();
        }
        while (true) {
            $current = set_error_handler(static fn (): false => false);
            restore_error_handler();
            if ($current === $this->baselineErrorHandler || null === $current) {
                break;
            }
            restore_error_handler();
        }
        parent::tearDown();
    }

    public function testFilterIsRegistered(): void
    {
        $twig = $this->bootTwig([]);
        self::assertNotNull($twig->getFilter('html_min'));
    }

    public function testCheckCommandIsRegistered(): void
    {
        $command = $this->bootContainer([])->get(HtmlMinCheckCommand::class);

        self::assertInstanceOf(HtmlMinCheckCommand::class, $command);
        self::assertSame('html-min:check', $command->getName());
    }

    public function testRemoveCommentsTrueStripsComments(): void
    {
        $twig = $this->bootTwig(['remove_comments' => true]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<html>  <body><!-- gone -->x</body>  </html>{% endhtmlmin %}',
        )->render();

        self::assertStringNotContainsString('<!-- gone -->', $rendered);
        self::assertStringNotContainsString('  ', $rendered);
        self::assertStringContainsString('<body>x', $rendered);
    }

    public function testRemoveCommentsFalsePreservesComments(): void
    {
        $twig = $this->bootTwig(['remove_comments' => false]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<html><body><!-- kept -->x</body></html>{% endhtmlmin %}',
        )->render();

        self::assertStringContainsString('<!-- kept -->', $rendered);
    }

    public function testMinifyInlineCssTrueMinifiesStyleBlock(): void
    {
        $twig = $this->bootTwig(['minify_inline_css' => true]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<style>a { color: red; /* x */ }</style>{% endhtmlmin %}',
        )->render();

        self::assertStringContainsString('<style>a{color:red}</style>', $rendered);
    }

    public function testMinifyInlineJsTrueMinifiesScriptBlock(): void
    {
        $twig = $this->bootTwig(['minify_inline_js' => true]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<script>// gone' . "\n" . 'let x = 1;</script>{% endhtmlmin %}',
        )->render();

        self::assertStringNotContainsString('// gone', $rendered);
        self::assertStringContainsString('let x = 1;', $rendered);
    }

    /**
     * `remove_omitted_html_tags` is one of the options the bundle did not expose
     * before full parity. The engine default is `true`, so setting it `false`
     * and seeing the optional closing tags survive proves the key is now wired
     * through to MinifierOptions.
     */
    public function testPreviouslyUnexposedOptionIsHonored(): void
    {
        $twig = $this->bootTwig(['remove_omitted_html_tags' => false]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<ul><li>a</li><li>b</li></ul>{% endhtmlmin %}',
        )->render();

        self::assertStringContainsString('</li>', $rendered);
    }

    /**
     * `remove_omitted_html_start_tags` arrived with engine v2.9.0 and is the
     * first option added after the bundle reached full parity. Setting it
     * `true` and seeing the `<html>`/`<head>`/`<body>` start tags disappear
     * proves the key is wired through to MinifierOptions.
     */
    public function testStartTagOmissionOptionIsHonored(): void
    {
        $twig = $this->bootTwig(['remove_omitted_html_start_tags' => true]);
        $rendered = $twig->createTemplate(
            '{% htmlmin %}<html><head><title>t</title></head><body><p>x</p></body></html>{% endhtmlmin %}',
        )->render();

        self::assertStringNotContainsString('<body>', $rendered);
        self::assertStringNotContainsString('<html>', $rendered);
        self::assertStringContainsString('<title>t</title>', $rendered);
        self::assertStringContainsString('<p>x', $rendered);
    }

    /**
     * Safety net for the camelize() -> named-argument contract: an invalid
     * MinifierOptions argument name would make the container fail to compile.
     * Setting every exposed option (booleans + list values) at once asserts the
     * whole surface is accepted and the resolved minifier still works.
     */
    public function testEveryOptionIsAcceptedAndMinifies(): void
    {
        $twig = $this->bootTwig([
            'optimize_via_html_dom_parser' => true,
            'optimize_attributes' => true,
            'remove_comments' => true,
            'remove_whitespace_around_tags' => true,
            'remove_omitted_quotes' => true,
            'remove_omitted_html_tags' => true,
            'remove_http_prefix_from_attributes' => true,
            'remove_https_prefix_from_attributes' => true,
            'keep_http_and_https_prefix_on_external_attributes' => true,
            'sort_css_class_names' => true,
            'sort_html_attributes' => true,
            'remove_deprecated_script_charset_attribute' => true,
            'remove_default_attributes' => true,
            'remove_deprecated_anchor_name' => true,
            'remove_deprecated_type_from_stylesheet_link' => true,
            'remove_deprecated_type_from_style_and_link_tag' => true,
            'remove_default_media_type_from_style_and_link_tag' => true,
            'remove_default_type_from_button' => true,
            'remove_deprecated_type_from_script_tag' => true,
            'remove_value_from_empty_input' => true,
            'remove_empty_attributes' => true,
            'sum_up_whitespace' => true,
            'remove_spaces_between_tags' => true,
            'keep_broken_html' => false,
            'minify_inline_css' => true,
            'minify_inline_js' => true,
            'local_domains' => ['example.com'],
            'special_html_comments_starting_with' => ['[if'],
            'special_html_comments_ending_with' => ['[endif]'],
            'special_script_tags' => ['text/template'],
            'template_logic_syntax_in_special_script_tags' => ['{{'],
        ]);

        $rendered = $twig->createTemplate(
            '{% htmlmin %}<html>  <body>x</body>  </html>{% endhtmlmin %}',
        )->render();

        self::assertStringContainsString('<body>x', $rendered);
        self::assertStringNotContainsString('  ', $rendered);
    }

    /**
     * `minify_responses: true` must wire the kernel.response listener so a
     * dispatched text/html response comes back minified. Dispatching through
     * the container-built dispatcher (rather than introspecting it) proves the
     * opt-in wiring end-to-end and is robust to debug listener wrapping.
     */
    public function testMinifyResponsesTrueRegistersResponseListener(): void
    {
        $response = new Response('<html>  <body>hi</body>  </html>', 200, ['Content-Type' => 'text/html']);
        $this->dispatchResponse(['minify_responses' => true], $response);

        self::assertStringNotContainsString('  ', (string) $response->getContent());
        self::assertStringContainsString('<body>hi', (string) $response->getContent());
    }

    public function testResponseListenerIsNotRegisteredByDefault(): void
    {
        $body = '<html>  <body>hi</body>  </html>';
        $response = new Response($body, 200, ['Content-Type' => 'text/html']);
        $this->dispatchResponse([], $response);

        self::assertSame($body, (string) $response->getContent());
    }

    /**
     * @param array<string, mixed> $bundleConfig
     */
    private function dispatchResponse(array $bundleConfig, Response $response): void
    {
        $dispatcher = $this->bootContainer($bundleConfig)->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
        $dispatcher->dispatch($event, KernelEvents::RESPONSE);
    }

    /**
     * @param array<string, mixed> $bundleConfig
     */
    private function bootTwig(array $bundleConfig): Environment
    {
        $twig = $this->bootContainer($bundleConfig)->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }

    /**
     * @param array<string, mixed> $bundleConfig
     */
    private function bootContainer(array $bundleConfig): ContainerInterface
    {
        $kernel = new TestKernel(self::$tmp, $bundleConfig);
        $kernel->boot();
        $testContainer = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $testContainer);

        return $testContainer;
    }
}

final class TestKernel extends Kernel
{
    /**
     * @param array<string, mixed> $bundleConfig
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly array $bundleConfig,
    ) {
        parent::__construct('test_'.bin2hex(random_bytes(4)), true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new AkankovTwigCompressHtmlBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
            ]);
            $container->loadFromExtension('twig', [
                'default_path' => $this->projectDir.'/templates',
                'strict_variables' => true,
            ]);
            $container->loadFromExtension('akankov_twig_compress_html', $this->bundleConfig);
        });
    }

    #[Override]
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    #[Override]
    public function getCacheDir(): string
    {
        return $this->projectDir.'/cache/'.$this->getEnvironment();
    }

    #[Override]
    public function getLogDir(): string
    {
        return $this->projectDir.'/log';
    }
}
