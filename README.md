# twig-compress-html

A Twig 3 extension wrapping [`akankov/html-min`](https://packagist.org/packages/akankov/html-min) — exposes an `html_min` filter and an `{% htmlmin %}...{% endhtmlmin %}` block tag, with an optional Symfony bundle for auto-registration.

## Requirements

- PHP `^8.3`
- `twig/twig` `^3.0`
- `akankov/html-min` `^1.0`

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
```

Any key you omit leaves the upstream `HtmlMin` default in place — the bundle only calls a setter when you set the corresponding key.

The filter and tag become available in all templates automatically.

## Tests

```sh
composer install
vendor/bin/phpunit
```

## License

MIT
