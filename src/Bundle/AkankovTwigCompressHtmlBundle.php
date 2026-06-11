<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Bundle;

use Akankov\HtmlMin\Config\MinifierOptions;
use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\HtmlMinExtension;
use Akankov\TwigCompressHtml\HtmlMinRuntime;
use Akankov\TwigCompressHtml\Http\MinifyHtmlResponseListener;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AkankovTwigCompressHtmlBundle extends AbstractBundle
{
    /**
     * Every boolean toggle on {@see MinifierOptions}, as snake_case config keys.
     * Kept as a flat list so the config tree and the service wiring share one
     * source of truth; the camelCase constructor-argument name is derived by
     * {@see self::camelize()} rather than hard-coded, so adding an engine option
     * only means adding its key here (and to LIST_OPTIONS if it takes a list).
     */
    private const array BOOL_OPTIONS = [
        'optimize_via_html_dom_parser',
        'optimize_attributes',
        'remove_comments',
        'remove_whitespace_around_tags',
        'remove_omitted_quotes',
        'remove_omitted_html_tags',
        'remove_http_prefix_from_attributes',
        'remove_https_prefix_from_attributes',
        'keep_http_and_https_prefix_on_external_attributes',
        'sort_css_class_names',
        'sort_html_attributes',
        'remove_deprecated_script_charset_attribute',
        'remove_default_attributes',
        'remove_deprecated_anchor_name',
        'remove_deprecated_type_from_stylesheet_link',
        'remove_deprecated_type_from_style_and_link_tag',
        'remove_default_media_type_from_style_and_link_tag',
        'remove_default_type_from_button',
        'remove_deprecated_type_from_script_tag',
        'remove_value_from_empty_input',
        'remove_empty_attributes',
        'sum_up_whitespace',
        'remove_spaces_between_tags',
        'keep_broken_html',
        'minify_inline_css',
        'minify_inline_js',
        'remove_omitted_html_start_tags',
    ];

    /**
     * The list-valued options on {@see MinifierOptions} (`string[]` and
     * `string[]|null`). Each is exposed as an array node of scalars.
     */
    private const array LIST_OPTIONS = [
        'local_domains',
        'special_html_comments_starting_with',
        'special_html_comments_ending_with',
        'special_script_tags',
        'template_logic_syntax_in_special_script_tags',
    ];

    public function configure(DefinitionConfigurator $definition): void
    {
        // @phan-suppress-next-line PhanUndeclaredMethod -- rootNode() returns ArrayNodeDefinition for bundle config trees; Phan can't narrow the union.
        $children = $definition->rootNode()->children();

        foreach (self::BOOL_OPTIONS as $key) {
            $children->booleanNode($key)->defaultNull()->end();
        }

        foreach (self::LIST_OPTIONS as $key) {
            $children->arrayNode($key)->scalarPrototype()->end()->end();
        }

        // Opt-in: register the kernel.response listener that minifies whole
        // text/html responses. Off by default, mirroring the Laravel binding's
        // "not on the global stack" posture.
        $children->booleanNode('minify_responses')->defaultFalse()->end();

        $children->end();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @suppress PhanUnusedPublicFinalMethodParameter $builder is required by AbstractBundle::loadExtension signature
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        // Build MinifierOptions from only the keys the user actually set; every
        // unset key falls through to the engine's own default, so this binding
        // never duplicates (and never drifts from) the engine's default values.
        $services->set(MinifierOptions::class)
            ->args(self::minifierOptionArguments($config));

        $services->set(HtmlMin::class)
            ->args([service(MinifierOptions::class)]);

        $services->set(HtmlMinRuntime::class)
            ->args([service(HtmlMin::class)])
            ->tag('twig.runtime');

        $services->set(HtmlMinExtension::class)
            ->tag('twig.extension');

        if ($config['minify_responses'] ?? false) {
            $services->set(MinifyHtmlResponseListener::class)
                ->args([service(HtmlMin::class)])
                ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse']);
        }
    }

    /**
     * Map set config keys to `MinifierOptions` named constructor arguments.
     * Booleans set to `false` are kept (only `null`/omitted is skipped); empty
     * lists are skipped so the engine default (`[]` or `null`) applies.
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed> keyed by `$camelCaseArgumentName`
     */
    private static function minifierOptionArguments(array $config): array
    {
        $args = [];

        foreach (self::BOOL_OPTIONS as $key) {
            if (isset($config[$key])) {
                $args['$' . self::camelize($key)] = $config[$key];
            }
        }

        foreach (self::LIST_OPTIONS as $key) {
            if (!empty($config[$key])) {
                $args['$' . self::camelize($key)] = $config[$key];
            }
        }

        return $args;
    }

    private static function camelize(string $snakeCase): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snakeCase))));
    }
}
