<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Tests\Bundle;

use Akankov\HtmlMin\Config\MinifierOptions;
use Akankov\TwigCompressHtml\Bundle\AkankovTwigCompressHtmlBundle;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Drift guard between the engine's option surface and the bundle's config tree.
 *
 * The bundle exposes every MinifierOptions constructor parameter as a
 * snake_case config key (BOOL_OPTIONS / LIST_OPTIONS). Nothing else enforces
 * that promise: `composer update` pulling an engine release that adds an
 * option would otherwise leave the new option silently unreachable from
 * bundle config. This test makes that drift a CI failure.
 */
final class OptionsParityTest extends TestCase
{
    public function testBundleExposesExactlyTheEngineOptionSurface(): void
    {
        $engineBools = [];
        $engineLists = [];

        foreach ((new ReflectionMethod(MinifierOptions::class, '__construct'))->getParameters() as $parameter) {
            $type = $parameter->getType();
            self::assertInstanceOf(
                ReflectionNamedType::class,
                $type,
                \sprintf('MinifierOptions::$%s has an unexpected compound type; teach this guard about it.', $parameter->getName()),
            );

            $key = self::snakeCase($parameter->getName());
            match ($type->getName()) {
                'bool' => $engineBools[] = $key,
                'array' => $engineLists[] = $key,
                default => self::fail(\sprintf(
                    'MinifierOptions::$%s is a %s; the bundle only knows bool and array options — teach this guard and the config tree about it.',
                    $parameter->getName(),
                    $type->getName(),
                )),
            };
        }

        self::assertEqualsCanonicalizing(
            $engineBools,
            self::bundleOptionKeys('BOOL_OPTIONS'),
            'BOOL_OPTIONS in AkankovTwigCompressHtmlBundle no longer matches the bool parameters of MinifierOptions::__construct.',
        );
        self::assertEqualsCanonicalizing(
            $engineLists,
            self::bundleOptionKeys('LIST_OPTIONS'),
            'LIST_OPTIONS in AkankovTwigCompressHtmlBundle no longer matches the list parameters of MinifierOptions::__construct.',
        );
    }

    /**
     * @return list<string>
     */
    private static function bundleOptionKeys(string $constant): array
    {
        $keys = (new ReflectionClassConstant(AkankovTwigCompressHtmlBundle::class, $constant))->getValue();
        self::assertIsArray($keys);

        $strings = [];
        foreach ($keys as $key) {
            self::assertIsString($key);
            $strings[] = $key;
        }

        return $strings;
    }

    private static function snakeCase(string $camelCase): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $camelCase));
    }
}
