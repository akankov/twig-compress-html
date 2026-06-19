# Changelog

All notable changes to `akankov/twig-compress-html` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.1] — 2026-06-19

### Fixed

- **Stale `Content-Length` after minification.** The opt-in `kernel.response`
  listener shrinks the body via `setContent()` but left any pre-set
  `Content-Length` header in place, which could truncate or over-read the
  response downstream. The header is now removed after minifying so Symfony
  re-derives the correct length.

### Added

- **Mutation testing.** An Infection config and a non-blocking `make infection`
  gate (MSI floor 70, measured ~75%), matching the engine's setup.
- **Supply-chain tooling.** A non-blocking `composer audit` CI job and a 7-day
  new-release cooldown added to the existing Dependabot config.

## [1.4.0] — 2026-06-12

Engine v2.9 option parity, the html-min:check console command, and a
100% line-coverage gate. No breaking changes.

### Changed

- **Line-coverage floor raised 90 → 100** (`MIN_LINE_COVERAGE` in the
  Makefile), reaching parity with the Laravel binding's gate.
- **README: streamed/206/ESI pass-through guidance.** Documents precisely
  what the opt-in response listener skips and why.

### Added

- **`html-min:check` console command.** With `symfony/console` installed
  (new `suggest`; the bundle only registers the command when the component
  is present), `bin/console html-min:check FILE` minifies the file in
  memory and reports the byte savings without writing to disk — parity
  with the Laravel binding's Artisan command, same single-read error
  handling and exit codes.

- **`remove_omitted_html_start_tags` config key.** Exposes engine v2.9.0's
  opt-in `<html>`/`<head>`/`<body>` start-tag omission
  (`MinifierOptions::$removeOmittedHtmlStartTags`). Off by default, matching
  the engine default.
- **Options parity guard.** A reflection-based test
  (`tests/Bundle/OptionsParityTest.php`) asserts that `BOOL_OPTIONS` /
  `LIST_OPTIONS` exactly match the constructor surface of the engine's
  `MinifierOptions`, so an engine release that adds or renames an option now
  fails CI instead of drifting silently. (This guard is what surfaced the
  missing v2.9.0 key above.)

### Changed

- Engine requirement raised from `akankov/html-min ^2.8` to `^2.9` — the new
  config key maps to a named constructor argument that only exists from
  v2.9.0.

## [1.3.0] — 2026-06-07

Brings the Symfony bundle to feature parity with the Laravel binding.

### Added

- **Full config parity.** The bundle now exposes every `MinifierOptions`
  option (the 26 boolean toggles plus the five list options `local_domains`,
  `special_html_comments_starting_with`, `special_html_comments_ending_with`,
  `special_script_tags`, `template_logic_syntax_in_special_script_tags`),
  matching the Laravel binding's `config/htmlmin.php`. Previously only seven
  booleans were configurable.
- **Opt-in response minification.** A new `minify_responses: true` flag
  registers a `kernel.response` listener (`Http\MinifyHtmlResponseListener`)
  that minifies whole `text/html` responses, mirroring the Laravel binding's
  `MinifyHtmlResponseMiddleware`. Off by default; sub-requests, streamed, and
  non-`text/html` responses pass through untouched.
- **Line-coverage CI gate.** A `make coverage` target and a CI coverage job
  (pcov) enforce a floor via `bin/coverage-check.php`, matching the Laravel
  binding's quality bar.

### Changed

- The bundle builds a `MinifierOptions` and injects it into `HtmlMin` instead of
  chaining individual `do*()` setters, so a future engine option only needs its
  key added to one list — the binding no longer duplicates engine defaults.

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
