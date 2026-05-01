# Contributing

Thanks for helping improve `akankov/twig-compress-html`. This project is a small
Twig 3 extension wrapping [`akankov/html-min`](https://github.com/akankov/html-min),
so focused changes with clear tests are easiest to review.

## Reporting Bugs

Open a bug report when:

- The `html_min` filter or `{% htmlmin %}` block tag produces incorrect or
  unexpected output.
- Twig's autoescape interacts incorrectly with the filter or tag.
- The Symfony bundle fails to register, mis-applies configuration, or breaks
  container compilation.

Include:

- The package version or commit SHA.
- The PHP version and the Twig version.
- The Symfony version (if you use the bundle).
- The smallest Twig template that reproduces the issue.
- The bundle configuration, if any.
- The actual rendered output and the expected output.
- Any related stack trace.

Please do not report security vulnerabilities in public issues. Use the process
in [SECURITY.md](SECURITY.md) instead.

## Requesting Features

Feature requests should describe the template pattern, the expected output, and
why the behavior belongs in this extension rather than in caller code or in
`akankov/html-min` itself. If the change could affect existing rendered output
of the filter or tag, call that out clearly.

## Development Setup

Install dependencies from the repository root:

```bash
composer install
```

Useful local checks:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/rector process --dry-run
vendor/bin/phan --allow-polyfill-parser
```

Or run the full pipeline at once:

```bash
make ci
```

## Tests

Add regression coverage for behavior changes:

- `tests/HtmlMinExtensionTest.php` — pure-Twig tests for the filter, the tag,
  autoescape interaction, and parser errors.
- `tests/Bundle/AkankovTwigCompressHtmlBundleTest.php` — Symfony kernel-boot
  tests that verify the bundle registers correctly and configuration flows
  through to the underlying `HtmlMin` instance.

Keep tests compatible with PHP 8.3. The Composer platform and Rector config are
pinned to PHP 8.3 even when local checks also run newer versions.

## Pull Requests

Before opening a pull request:

- Keep the change focused and explain the user-visible behavior.
- Add or update tests for filter, tag, or bundle-wiring changes.
- Update the README and `CHANGELOG.md` when configuration or behavior changes.
- Run `make ci` and mention any check that could not be run.
- Link the related issue when there is one.
