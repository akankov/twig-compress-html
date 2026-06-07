# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`akankov/twig-compress-html` — a Twig 3 extension wrapping the
[`akankov/html-min`](https://github.com/akankov/html-min) HTML minifier engine
(`^2.8`). Three surfaces:

- **`html_min` filter** — `{{ rawHtml|html_min }}`.
- **`{% htmlmin %} … {% endhtmlmin %}` block tag** — minifies the rendered body.
- **Optional Symfony bundle** (`AkankovTwigCompressHtmlBundle`) — auto-registers
  the extension, exposes the engine's full option surface as YAML config, and
  (opt-in) a `kernel.response` listener that minifies whole `text/html` responses.

The bundle layer depends on Symfony components that are `require-dev` + `suggest`
only (not `require`) — a plain-Twig user never autoloads them.

## `src/` layout

- `HtmlMinExtension.php` — registers the `html_min` filter and the `{% htmlmin %}`
  token parser.
- `HtmlMinRuntime.php` — holds the `HtmlMin` instance; called by the filter and
  the compiled tag.
- `Node/HtmlMinNode.php` — the compiled tag emits
  `ob_start(); …body…; echo $runtime->minify(ob_get_clean());`, so it captures
  already-escaped Twig output and minifies it.
- `TokenParser/HtmlMinTokenParser.php` — parses the `{% htmlmin %}…{% endhtmlmin %}`
  block.
- `Bundle/AkankovTwigCompressHtmlBundle.php` — Symfony auto-registration. Builds a
  `MinifierOptions` from config and injects it into the `HtmlMin` service; wires
  the response listener when `minify_responses` is true.
- `Http/MinifyHtmlResponseListener.php` — opt-in `kernel.response` listener.
  Mirrors the engine's `MinifierMiddleware::shouldMinify()` policy (split on `;`,
  lowercase, allowlist `text/html`); sub-requests, streamed, and binary-file
  responses pass through.

## Load-bearing invariants

Things that are easy to regress silently. Tests guard them; do not weaken or
remove the guards.

- **Escape before minify.** The compiled tag (and the filter) only ever see
  output Twig has *already* autoescaped, so user data interpolated inside a
  `{% htmlmin %}` block stays escaped through minification. Locked by
  `tests/HtmlMinExtensionTest.php::testNestedVariablesAreEscapedInsideTag` — the
  same contract the Laravel binding guards with `testBladeInterpolationStaysEscaped`.
- **Config is drift-proof.** The bundle builds `MinifierOptions` from only the
  keys the user set, letting every omitted key fall through to the *engine's* own
  default — the binding never hard-codes a default value. Adding an engine option
  means adding its snake_case key to `BOOL_OPTIONS` / `LIST_OPTIONS`; the
  camelCase constructor-argument name is derived by `camelize()`, not duplicated.
  `testEveryOptionIsAcceptedAndMinifies` is the safety net: an invalid named
  argument would make the container fail to compile.
- **Response listener stays opt-in.** The bundle must never register the listener
  unless `minify_responses` is true (`testResponseListenerIsNotRegisteredByDefault`).

## Parity & tooling notes

- This package **runs Phan** (CI + `make phan`); the Laravel binding deliberately
  omits it. Phan's `directory_list` (`.phan/config.php`) must list each Symfony
  component whose classes `src/` references — e.g. `symfony/http-foundation` was
  added for the response listener.
- A **line-coverage floor** is enforced by `make coverage` →
  `bin/coverage-check.php` (same dependency-free checker as the Laravel binding).
  `MIN_LINE_COVERAGE` in the `Makefile` starts conservative; raise it toward 100
  once a CI run (where pcov is available) reports the real number.
- There is no `phpunit.xml` — the dist file is `phpunit.xml.dist`, with
  `failOnNotice`/`failOnRisky` on. Use PHPUnit **test stubs** (`createStub()`),
  not mocks, for collaborators you don't set expectations on.

The parent workspace conventions in `../CLAUDE.md` apply (PHP
`8.3.* || 8.4.* || 8.5.*` floor, `declare(strict_types=1)`, PHPStan level max,
PHP-CS-Fixer PSR-12 + risky, `#[Override]` on inherited methods, `#[DataProvider]`
on static `iterable` providers).

## Commands

```bash
vendor/bin/phpunit                 # full suite
make coverage                      # coverage + floor (needs pcov/xdebug)
make ci                            # cs-check + phpstan + phan + rector-check + test
```
