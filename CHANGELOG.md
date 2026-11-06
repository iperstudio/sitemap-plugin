# Changelog

## 1.3.0 — 2026-11-06

- Traverse published pages progressively instead of building the full site index before filtering.
- Add `excludeBranches` to skip configured page IDs and their entire subtrees before loading descendants.

## 1.2.0 — 2026-10-30

- Replace the default browser XSLT transformation with CSS applied directly to sitemap XML.
- Add the `/sitemap.css` route with responsive, dark-mode-aware styles.
- Add `stylesheet: 'css' | 'xsl' | false`, including support for the legacy option prefix.
- Retain `/sitemap.xsl` and opt-in legacy XSLT rendering for compatibility.
- Preserve the exact sitemap data across CSS, legacy XSL and raw XML views.

The CSS view displays URLs as selectable text, rather than transformed HTML hyperlinks. No content migration is needed. Browser XSLT is no longer required by default.

## 1.1.0 — 2026-10-30

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
