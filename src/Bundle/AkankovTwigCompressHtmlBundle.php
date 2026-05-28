<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Bundle;

use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\HtmlMinExtension;
use Akankov\TwigCompressHtml\HtmlMinRuntime;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AkankovTwigCompressHtmlBundle extends AbstractBundle
{
    private const array SETTER_MAP = [
        'remove_comments' => 'doRemoveComments',
        'sum_up_whitespace' => 'doSumUpWhitespace',
        'optimize_attributes' => 'doOptimizeAttributes',
        'sort_html_attributes' => 'doSortHtmlAttributes',
        'remove_omitted_quotes' => 'doRemoveOmittedQuotes',
        'minify_inline_css' => 'doMinifyInlineCss',
        'minify_inline_js' => 'doMinifyInlineJs',
    ];

    public function configure(DefinitionConfigurator $definition): void
    {
        // @phan-suppress-next-line PhanUndeclaredMethod -- rootNode() returns ArrayNodeDefinition for bundle config trees; Phan can't narrow the union.
        $children = $definition->rootNode()->children();
        foreach (array_keys(self::SETTER_MAP) as $key) {
            $children->booleanNode($key)->defaultNull()->end();
        }
        $children->end();
    }

    /**
     * @param array<string, bool|null> $config
     *
     * @suppress PhanUnusedPublicFinalMethodParameter $builder is required by AbstractBundle::loadExtension signature
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $htmlMin = $services->set(HtmlMin::class);
        foreach (self::SETTER_MAP as $key => $method) {
            if (isset($config[$key])) {
                $htmlMin->call($method, [$config[$key]]);
            }
        }

        $services->set(HtmlMinRuntime::class)
            ->args([service(HtmlMin::class)])
            ->tag('twig.runtime');

        $services->set(HtmlMinExtension::class)
            ->tag('twig.extension');
    }
}
