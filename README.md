[![CI](https://github.com/akankov/twig-compress-html/actions/workflows/ci.yml/badge.svg)](https://github.com/akankov/twig-compress-html/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/akankov/twig-compress-html/v)](https://packagist.org/packages/akankov/twig-compress-html)
[![Monthly Downloads](http://poser.pugx.org/akankov/twig-compress-html/d/monthly)](https://packagist.org/packages/akankov/twig-compress-html)
[![Dependents](http://poser.pugx.org/akankov/twig-compress-html/dependents)](https://packagist.org/packages/akankov/twig-compress-html)
[![License](http://poser.pugx.org/akankov/twig-compress-html/license)](https://packagist.org/packages/akankov/twig-compress-html)

# twig-compress-html

A Twig 3 extension wrapping [`akankov/html-min`](https://packagist.org/packages/akankov/html-min) — exposes an `html_min` filter and an `{% htmlmin %}...{% endhtmlmin %}` block tag, with an optional Symfony bundle for auto-registration.

## Requirements

- PHP `8.3.* || 8.4.* || 8.5.*`
- `twig/twig` `^3.0`
- `akankov/html-min` `^2.9`

## Install

```sh
composer require akankov/twig-compress-html
```

## Plain Twig usage

```php
use Akankov\HtmlMin\HtmlMin;
use Akankov\TwigCompressHtml\HtmlMinExtension;
use Akankov\TwigCompressHtml\HtmlMinRuntime;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

$twig = new Environment(new FilesystemLoader(__DIR__.'/templates'));
$twig->addExtension(new HtmlMinExtension());
$twig->addRuntimeLoader(new FactoryRuntimeLoader([
    HtmlMinRuntime::class => static fn () => new HtmlMinRuntime(new HtmlMin()),
]));
```

### Filter

```twig
{{ rawHtml|html_min }}
```

### Block tag

```twig
{% htmlmin %}
<html>
  <body>
    {{ content }}
  </body>
</html>
{% endhtmlmin %}
```

The tag captures rendered output (variables are escaped first by Twig's autoescape, then minified), so it's safe to interpolate user data inside.

## Symfony usage

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Akankov\TwigCompressHtml\Bundle\AkankovTwigCompressHtmlBundle::class => ['all' => true],
];
```

Optionally tune `HtmlMin` via `config/packages/akankov_twig_compress_html.yaml`:

```yaml
akankov_twig_compress_html:
    remove_comments: true
    sum_up_whitespace: true
    optimize_attributes: true
    sort_html_attributes: true
    remove_omitted_quotes: false
    minify_inline_css: true
    local_domains: ['example.com']
```

The filter and tag become available in all templates automatically.

### Configuration

Every config key is a snake_case mirror of a property on
`Akankov\HtmlMin\Config\MinifierOptions`; the bundle camel-cases them and builds
the options object that backs the shared `HtmlMin` service, so:

```yaml
remove_comments: true       # → MinifierOptions::$removeComments
sum_up_whitespace: true     # → MinifierOptions::$sumUpWhitespace
local_domains: ['a.test']   # → MinifierOptions::$localDomains
```

All of the engine's options are accepted — the 27 boolean toggles plus the list
options `local_domains`, `special_html_comments_starting_with`,
`special_html_comments_ending_with`, `special_script_tags`, and
`template_logic_syntax_in_special_script_tags`. Any key you omit falls through
to the engine's own default, so the bundle never pins (or drifts from) those
defaults. This matches the surface exposed by the Laravel binding's
`config/htmlmin.php`.

### Response minification (opt-in)

To minify whole `text/html` responses — not just `{% htmlmin %}` blocks — enable
the `kernel.response` listener. It is **off by default** (the bundle never adds
it to the response pipeline on its own), mirroring the Laravel binding's
`MinifyHtmlResponseMiddleware`:

```yaml
akankov_twig_compress_html:
    minify_responses: true
```

Sub-requests, streamed responses, and non-`text/html` responses pass through
untouched, so it is safe in front of a mixed JSON / HTML application.

## Versioning

This package follows [Semantic Versioning](https://semver.org/). From **1.0.0** onward the public surface — the `html_min` filter, the `{% htmlmin %}` block tag, the Symfony bundle's config keys, and the opt-in response listener — is stable; breaking changes are reserved for a new major version. The underlying engine is tracked via a caret constraint (`akankov/html-min: ^2.9`), so it picks up engine minor/patch releases automatically.

## Tests

```sh
composer install
vendor/bin/phpunit

# line coverage + floor enforcement (needs pcov or xdebug)
make coverage
```

## License

MIT
