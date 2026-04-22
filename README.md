# Mercury Bootstrapper

WordPress plugin that automates the baseline setup of a fresh WordPress site for Mercury Media projects.

## What it does

Upload, activate, click **Run Full Setup** — and in ~2–3 minutes a fresh WordPress install goes from empty to baseline-ready:

- Default content, themes, and plugins removed
- WordPress core settings configured (Estonian locale, Europe/Tallinn timezone, post-name permalinks, etc.)
- Hello Elementor theme installed + activated
- Baseline plugins installed + activated from wp.org: Elementor, Yoast SEO, UpdraftPlus, EWWW Image Optimizer, Complianz
- Premium plugins (Elementor Pro, WP Rocket) uploaded via the admin page as `.zip` files — the plugin installs and activates them
- Empty "Avaleht" (home) page created and set as the static front page
- Security hardening: XML-RPC disabled, trackbacks disabled, `readme.html` and `license.txt` removed
- Launch Checklist shown at the end — what's done and what's still manual (licence keys, Complianz wizard, hosting-level SSL/SMTP, wp-config constants)

## Who it's for

Mercury Media's internal WordPress workflow. Works on any hosting where you have WP admin access — tested primarily with Veebimajutus.ee, but hosting-agnostic by design (no SSH or WP-CLI required).

## Installation

1. Download the latest `mercury-bootstrapper.zip` from [Releases](https://github.com/TristanTom/mercury-bootstrapper/releases/latest)
2. In WP admin: **Plugins → Add New → Upload Plugin**
3. Choose the `.zip`, install, and activate
4. A **Mercury Setup** menu item appears — open it, follow the on-screen steps

## Updates

The plugin checks GitHub Releases for new versions. When a newer release is published, the standard WordPress update flow will show an "Update available" notice on the Plugins page.

## Security

This is an open-source GPL-licensed plugin. Code contains no secrets, API keys, or client data. All admin actions require WordPress nonces and the `manage_options` capability. External requests are limited to `api.wordpress.org` (plugin/theme downloads) and `api.github.com` (update checks).

## License

GPLv2 or later — see [LICENSE](LICENSE).

## Status

Under active development — see [implementation plan](../../.claude/plans/).
