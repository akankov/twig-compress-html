<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests\Bundle;

use Akankov\TwigCompressHtml\Bundle\AkankovTwigCompressHtmlBundle;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
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

    /**
     * @param array<string, bool|null> $bundleConfig
     */
    private function bootTwig(array $bundleConfig): Environment
    {
        $kernel = new TestKernel(self::$tmp, $bundleConfig);
        $kernel->boot();
        $testContainer = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $testContainer);
        $twig = $testContainer->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}

final class TestKernel extends Kernel
{
    /**
     * @param array<string, bool|null> $bundleConfig
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
