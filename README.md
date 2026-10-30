# Iperstudio Sitemap Plugin

Generates `/sitemap.xml`, styles it with `/sitemap.css`, and redirects `/sitemap` to the XML document. Supports translated URLs, reciprocal hreflang links, x-default and translation-specific modification dates.

Requires PHP 8.2+, DOM and Kirby 5 or 6. Tested locally with Kirby 5.3.1, 5.6.1 and 6.0.0-beta.1/beta.2; Kirby 6 support is provisional while it is in beta. The SEO plugin is optional.

## Installation

Install from Packagist:

```sh
composer require iperstudio/sitemap-plugin:^1.2
```

The Kirby Composer installer places the plugin in `site/plugins/sitemap-plugin`. No custom Composer repository is required.

## Configuration

Use the registered prefix `iperstudio.sitemap-plugin` for new configurations:

```php
return [
    'iperstudio.sitemap-plugin' => [
        'excludeTemplates' => ['error', 'redirect'],
        'includeUnlisted' => ['privacy'],
        'pageFilter' => static fn ($page) => $page->isHomePage()
            || $page->isListed() || $page->id() === 'privacy',
    ],
];
```

Flat dotted keys also work. All existing `iperstudio.sitemap` options remain supported. For each option, the new prefix takes precedence, including false values and empty arrays. Options are read at runtime rather than registered with defaults that would mask the legacy configuration.

| Option | Default | Purpose |
| --- | --- | --- |
| `includeTemplates` | `[]` | Allowed intended templates; non-empty list takes precedence over exclusions |
| `excludeTemplates` | `[]` | Excluded intended templates when no allowlist is configured |
| `excludeUnlisted` | `true` | Omit unlisted pages except home |
| `includeUnlisted` | `[]` | Explicitly allow these unlisted page IDs |
| `pageFilter` | `null` | Callback `(Page $page, ?string $language): bool`; must return true to include a URL |
| `filterHome` | `false` | Apply template filtering to home as well |
| `priority` | `true` | Emit legacy automatic priority values |
| `xDefault` | `true` | Add x-default when the default-language URL is eligible |
| `stylesheet` | `'css'` | Browser presentation: `'css'`, legacy `'xsl'`, or `false` for raw XML |

Drafts, descendants of drafts, error pages and pages marked `seoNoindex` are excluded. Each language is evaluated independently: a missing or noindex translation cannot appear as a URL or an alternate, and cannot remove another eligible translation. Shared project filters can be supplied to both SEO and sitemap plugins.

The homepage still bypasses template filtering by default, as it did in 1.0. Set `filterHome` to true to change this. A `pageFilter` and `seoNoindex` still apply to the homepage. Priority defaults are preserved: home gets `1.0`, other pages use `max(0.1, 0.5 / depth)`, rounded to one decimal place. Set `priority` to false to omit this optional field. The legacy `sitemapPriority()` helper is retained.

XML is built with DOM for escaping, and all route/stylesheet URLs honor installation subdirectories. CSS formats the XML directly without JavaScript or browser XSLT. The legacy `/sitemap.xsl` endpoint remains available for existing integrations. The XML response carries `X-Robots-Tag: noindex` for the sitemap document itself; this does not exclude the pages it lists.

The plugin does not infer every project's access or redirect rules. Use `pageFilter` to exclude private pages, redirect-only records or other routes that do not represent public content. A sitemap contains URLs and should only list content intended to be public.

## Upgrading from 1.1

Version 1.2 replaces the default XSLT presentation with CSS because browsers are removing native XSLT support. Sitemap URLs, XML namespaces, filters, modification dates, priority and hreflang data are unchanged; only the stylesheet processing instruction changes. There are no new runtime dependencies or content migrations.

The CSS view displays a readable list with URLs, modification dates and translated alternates, supports narrow viewports and dark mode, and works without JavaScript or XSLT. URLs are selectable text rather than the clickable HTML links produced by the old XSL transformation. The XML remains a sitemap for crawlers, not an HTML document.

Set `iperstudio.sitemap-plugin.stylesheet` to `false` for the browser's raw XML view. Setting it to `'xsl'` explicitly restores the legacy presentation while a browser still supports XSLT; this also restores the browser's deprecation warning. Existing `/sitemap` and `/sitemap.xsl` routes are retained. Sites on `^1.0` or `^1.1` can update to 1.2 with the command below.

```sh
composer update iperstudio/sitemap-plugin
```

## Upgrading from 1.0

Version 1.1 is a compatible minor release. Route names, legacy configuration, the homepage exception, automatic priority and the global helper remain available. No content migration is required.

The sitemap now excludes error/noindex pages and adds x-default links. Modification timestamps use each translation's content file. These corrections can change the XML entries and should be reviewed before deployment. The new `seoNoindex` field can be supplied by the SEO plugin or your own blueprint.

```sh
composer update iperstudio/sitemap-plugin
```

A `^1.0` constraint accepts 1.1; an exact `1.0.2` or `~1.0.0` constraint must be changed to opt in. Removing legacy configuration or changing established defaults is reserved for a future major release.

## Development and releases

```sh
composer install
composer test
```

Or test against an existing Kirby checkout:

```sh
KIRBY_BOOTSTRAP=/absolute/path/to/kirby/bootstrap.php php tests/run.php
```

Tests use disposable fixture sites and do not modify real content. CI covers PHP 8.2/8.4 with Kirby 5 and PHP 8.4 with Kirby 6 beta. Run `composer validate`, update the changelog and `version` together, then create the matching `v1.2.0` tag when releasing. The explicit version supports Kirby's update checks for ZIP installations; it must match the Git tag. Configure Packagist's repository webhook after submitting the public repository.

## License

MIT. See [LICENSE.md](LICENSE.md).
