# Security Policy

## Supported Versions

Security fixes land on the latest `1.x` release. Older majors will only
be patched once a `2.x` exists, and only for critical issues.

| Version | Supported |
| ------- | --------- |
| 1.x     | ✅        |
| < 1.0   | ❌        |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security problems.**

Report vulnerabilities privately via GitHub's private reporting:

1. Go to the repo's [Security tab](https://github.com/akankov/twig-compress-html/security).
2. Click **Report a vulnerability**.
3. Describe the issue, affected versions, and a proof-of-concept if you
   have one.

If GitHub's private reporting is unavailable to you, email
<akankov@gmail.com> instead.

## What to expect

- **Acknowledgement**: within 5 business days.
- **Triage & severity assessment**: within 10 business days.
- **Fix timeline**: depends on severity. Critical issues get a patch
  release as soon as a fix is verified; low-severity issues may be
  bundled into the next regular release.
- **Disclosure**: coordinated. We'll publish a GitHub Security Advisory
  (GHSA) crediting the reporter once a fix is released, unless you
  request otherwise.

## Scope

Findings in scope:

- The `html_min` filter or `{% htmlmin %}` block tag breaking Twig's
  autoescape contract (e.g. allowing user-supplied HTML to bypass
  escaping, or producing markup that enables XSS).
- The Symfony bundle exposing services or configuration in ways that
  weaken the host application's security posture.
- Denial-of-service via pathological template input rendered through
  the filter or tag (catastrophic regex, exponential blowup,
  unbounded memory).
- Vulnerabilities in runtime dependencies that this extension exposes
  (e.g. via the `HtmlMinRuntime` constructor surface).

Out of scope:

- Issues in `akankov/html-min` itself (report those to
  [akankov/html-min](https://github.com/akankov/html-min/security)).
- Issues in Twig or Symfony upstream (report those to their
  respective security teams).
- Issues that require a malicious maintainer to already be running
  code on your system.
- Findings in the dev-only toolchain (PHPUnit, PHPStan, etc.) unless
  they affect the published artifact.
