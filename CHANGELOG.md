# Changelog

All notable changes to `akankov/twig-compress-html` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] — 2026-05-31

Tracks the latest engine: requires `akankov/html-min` **^2.8** (was ^2.6). Since
2.6 the engine gained 100% line coverage, mutation-tested hardening, an
internal decomposition, and an HTML-parser cleanup — all behaviour-preserving
for the well-formed output this extension produces. No API change to the filter,
`{% htmlmin %}` tag, or bundle.

### Changed

- Bump the `akankov/html-min` requirement from `^2.6` to `^2.8`.

## [1.1.0] — 2026-05-28

Exposes the opt-in inline CSS/JS minification toggles added in
`akankov/html-min` 2.6 through the bundle config. Released from PR
[#2](https://github.com/akankov/twig-compress-html/pull/2).

### Added

- `minify_inline_css` and `minify_inline_js` bundle config keys, mapping to the
  `doMinifyInlineCss()` / `doMinifyInlineJs()` toggles in `akankov/html-min`.
  Both default to off; set to `true` to minify the contents of inline `<style>`
  / `<script>` blocks captured by the `{% htmlmin %}` tag or `html_min` filter.
  Requires `akankov/html-min` ^2.6.

## [1.0.0] — 2026-05-01

### Added

- Initial Twig 3 extension wrapping `akankov/html-min`:
  - `html_min` filter for piping a value through the minifier
    (`{{ html|html_min }}`).
  - `{% htmlmin %}...{% endhtmlmin %}` block tag for wrapping a
    region of template output and minifying the captured render.
  - `HtmlMinRuntime` runtime class for lazy `HtmlMin` resolution.
- `AkankovTwigCompressHtmlBundle` for Symfony 7 with a config tree
  (`akankov_twig_compress_html`) mapping boolean keys
  (`remove_comments`, `sum_up_whitespace`, `optimize_attributes`,
  `sort_html_attributes`, `remove_omitted_quotes`) to the chainable
  setters on the underlying `HtmlMin` instance.
- Quality toolchain mirroring `akankov/html-min`: PHPStan level max
  with the Symfony and PHPUnit extensions, PHP-CS-Fixer with the
  PSR-12 + PHP 8.3 + PHPUnit 10 migration rule sets, Rector
  (`UP_TO_PHP_83` + `TYPE_DECLARATION` + `DEAD_CODE`), Phan, and
  PHPUnit 12 with strict `failOn` settings.
- GitHub Actions CI matrix on PHP 8.3 / 8.4 / 8.5 plus separate
  jobs for PHPStan, Phan, Rector dry-run, and PHP-CS-Fixer.
- Community files: `LICENSE` (MIT), `CODE_OF_CONDUCT.md`
  (Contributor Covenant 2.1), `CONTRIBUTING.md`, `SECURITY.md`,
  issue and pull-request templates, `FUNDING.yml`.
