# Iperstudio Sitemap Plugin

Generates `/sitemap.xml`, styles it with `/sitemap.css`, and redirects `/sitemap` to the XML document. Supports translated URLs, reciprocal hreflang links, x-default and translation-specific modification dates.

Requires PHP 8.2+, DOM and Kirby 5 or 6. Tested locally with Kirby 5.3.1, 5.6.1 and 6.0.0-beta.1/beta.2; Kirby 6 support is provisional while it is in beta. The SEO plugin is optional.

## Installation

Run this command from your Kirby project's root directory:

```sh
composer require "iperstudio/sitemap-plugin:^1.3"
```

The package is available on [Packagist](https://packagist.org/packages/iperstudio/sitemap-plugin); no custom `repositories` entry or GitHub credentials are required. The Kirby Composer installer places the plugin in `site/plugins/sitemap-plugin`. Allow `getkirby/composer-installer` if Composer prompts for permission to run it.

Commit `composer.json` and `composer.lock`. Keep the installed plugin directory out of version control and make changes in this plugin's source repository, rather than editing the installed files.

### Switching an existing installation to Packagist

If the plugin was installed with Composer through a `vcs` repository, remove only this plugin's entry from your project's `repositories` array. Keep repositories required by other dependencies, then run the update command below. Existing configuration and content can remain in place.

For a manually copied installation:

1. Back up any custom changes and move `site/plugins/sitemap-plugin` outside `site/plugins` so the old and new copies cannot load together.
2. If the plugin files were tracked by Git, remove them from the index with `git rm -r --cached -- site/plugins/sitemap-plugin` and add the directory to `.gitignore`.
3. Run the installation command above. Move project-specific behavior into the documented configuration options instead of copying changes over the installed package.

## Updates

Update this plugin within the version constraint in your project's `composer.json`:

```sh
composer update iperstudio/sitemap-plugin
```

Review the changelog and the `composer.lock` diff, run your project's checks, and commit the updated lock file. A `^1.0` constraint accepts compatible 1.x releases; exact versions and constraints such as `~1.0.0` may prevent the update. To explicitly adopt the current release series, use:

```sh
composer require "iperstudio/sitemap-plugin:^1.3"
```

On other environments, install the committed lock file instead of resolving new versions during deployment:

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

Project content is managed separately; Composer does not synchronize it.

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
| `excludeBranches` | `[]` | Skip these page IDs and all their descendants before loading their children |
| `pageFilter` | `null` | Callback `(Page $page, ?string $language): bool`; must return true to include a URL |
| `filterHome` | `false` | Apply template filtering to home as well |
| `priority` | `true` | Emit legacy automatic priority values |
| `xDefault` | `true` | Add x-default when the default-language URL is eligible |
| `stylesheet` | `'css'` | Browser presentation: `'css'`, legacy `'xsl'`, or `false` for raw XML |

Drafts, descendants of drafts, error pages and pages marked `seoNoindex` are excluded. Each language is evaluated independently: a missing or noindex translation cannot appear as a URL or an alternate, and cannot remove another eligible translation. Shared project filters can be supplied to both SEO and sitemap plugins.

The homepage still bypasses template filtering by default, as it did in 1.0. Set `filterHome` to true to change this. A `pageFilter` and `seoNoindex` still apply to the homepage. Priority defaults are preserved: home gets `1.0`, other pages use `max(0.1, 0.5 / depth)`, rounded to one decimal place. Set `priority` to false to omit this optional field. The legacy `sitemapPriority()` helper is retained.

XML is built with DOM for escaping, and all route/stylesheet URLs honor installation subdirectories. CSS formats the XML directly without JavaScript or browser XSLT. The legacy `/sitemap.xsl` endpoint remains available for existing integrations. The XML response carries `X-Robots-Tag: noindex` for the sitemap document itself; this does not exclude the pages it lists.

The plugin does not infer every project's access or redirect rules. Use `pageFilter` to exclude private pages, redirect-only records or other routes that do not represent public content. A sitemap contains URLs and should only list content intended to be public.

### Large sites

Pages are visited progressively through `children()`, without building a full `site()->index()` first. To avoid scanning entire sections that do not belong in the sitemap, configure their page IDs:

```php
'iperstudio.sitemap-plugin.excludeBranches' => ['internal', 'archive/imports'],
```

Each excluded branch root and all its descendants are omitted in every language. Its children are never loaded by the sitemap traversal. `includeUnlisted` and the homepage exception do not override branch exclusions. The same exclusions apply to `Sitemap::includes()`.

Template, unlisted, noindex and `pageFilter` exclusions still affect individual pages only: an excluded parent can have eligible children. Without explicit branch exclusions, the sitemap still visits every published page; the progressive traversal avoids flat index construction, but Kirby retains loaded page objects and the XML is still assembled in memory.

## Upgrading from 1.2

Version 1.3 replaces full site indexing with progressive traversal and adds the optional `excludeBranches` setting described above. Existing filters and sitemap entries are preserved unless branch exclusions are configured. No content migration is required. Use the update workflow above.

## Upgrading from 1.1

Version 1.2 replaces the default XSLT presentation with CSS because browsers are removing native XSLT support. Sitemap URLs, XML namespaces, filters, modification dates, priority and hreflang data are unchanged; only the stylesheet processing instruction changes. There are no new runtime dependencies or content migrations.

The CSS view displays a readable list with URLs, modification dates and translated alternates, supports narrow viewports and dark mode, and works without JavaScript or XSLT. URLs are selectable text rather than the clickable HTML links produced by the old XSL transformation. The XML remains a sitemap for crawlers, not an HTML document.

Set `iperstudio.sitemap-plugin.stylesheet` to `false` for the browser's raw XML view. Setting it to `'xsl'` explicitly restores the legacy presentation while a browser still supports XSLT; this also restores the browser's deprecation warning. Existing `/sitemap` and `/sitemap.xsl` routes are retained. The update workflow above applies to sites on `^1.0` and `^1.1` as well.

## Upgrading from 1.0

Version 1.1 is a compatible minor release. Route names, legacy configuration, the homepage exception, automatic priority and the global helper remain available. No content migration is required.

The sitemap now excludes error/noindex pages and adds x-default links. Modification timestamps use each translation's content file. These corrections can change the XML entries and should be reviewed before deployment. The new `seoNoindex` field can be supplied by the SEO plugin or your own blueprint.

Use the update workflow above. Removing legacy configuration or changing established defaults is reserved for a future major release.

## Development and releases

```sh
composer install
composer test
```

Or test against an existing Kirby checkout:

```sh
KIRBY_BOOTSTRAP=/absolute/path/to/kirby/bootstrap.php php tests/run.php
```

Tests use disposable fixture sites and do not modify real content. CI covers PHP 8.2/8.4 with Kirby 5 and PHP 8.4 with Kirby 6 beta. For a new release, update the changelog and `version`, run `composer validate` and the tests, then commit and push the changes before creating and pushing the matching version tag. The explicit version supports Kirby's update checks for ZIP installations; it must match the Git tag. Packagist reads published Git tags; its GitHub webhook must be enabled to discover releases automatically. Do not move or recreate existing tags. Documentation-only changes do not require a version bump.

## License

MIT. See [LICENSE.md](LICENSE.md).
