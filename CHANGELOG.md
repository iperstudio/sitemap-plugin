# Changelog

## 1.1.0 — Unreleased

- Preserve all `iperstudio.sitemap` options and support the registered `iperstudio.sitemap-plugin` prefix with per-option precedence.
- Build escaped XML with DOM, use translation-specific lastmod dates and add x-default links.
- Exclude errors, drafts, descendants of drafts and noindex translations independently of the active language.
- Add unlisted page exceptions, project filtering, optional priority and opt-in homepage template filtering.
- Preserve the legacy homepage exception, priority values and `sitemapPriority()` helper.
- Honor installation subdirectories and use XSLT 1.0.
- Mark the sitemap document itself noindex.
- Declare Kirby 5/6 compatibility, including provisional Kirby 6 beta support.
- Add isolated regression tests, CI and upgrade documentation.

Review the resulting XML before deployment: error/noindex entries are now omitted and alternate clusters include x-default. No content migration is required.
