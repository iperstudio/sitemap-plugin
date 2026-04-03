# Iperstudio Sitemap Plugin

Provides:

- `sitemap.xml` auto-generated route
- `sitemap.xsl` styled XML stylesheet
- Multilanguage support with `hreflang` alternates
- Configurable template inclusion/exclusion
- Auto-priority based on page depth

****

## Installation

### Composer

```
composer require iperstudio/sitemap-plugin
```

## Setup

The sitemap is available automatically at:

`https://yoursite.com/sitemap.xml`

A redirect from `/sitemap` to `/sitemap.xml` is included out of the box.

## Options

Add to your `config.php`:
```php
return [
    // Whitelist mode: only these templates are included.
    // Takes precedence over excludeTemplates when not empty.
    'iperstudio.sitemap.includeTemplates' => [],

    // Blacklist mode: these templates are excluded.
    // Only used when includeTemplates is empty.
    'iperstudio.sitemap.excludeTemplates' => [],

    // Exclude unlisted pages (default: true)
    'iperstudio.sitemap.excludeUnlisted' => true,
];
```

### Template filtering

**Whitelist** — include only specific templates:
```php
'iperstudio.sitemap.includeTemplates' => ['article', 'project'],
```

**Blacklist** — exclude specific templates:
```php
'iperstudio.sitemap.excludeTemplates' => ['legal', 'error'],
```

When `includeTemplates` is not empty, `excludeTemplates` is ignored.

## Features

### Priority
Page priority is calculated automatically based on depth:

| Page | Priority |
|------|----------|
| Homepage | `1.0` |
| Depth 1 | `0.5` |
| Depth 2 | `0.3` |
| Depth 3+ | `0.1` |

### Multilanguage
In multilanguage Kirby setups, the sitemap automatically adds `hreflang` alternate links for each language a page is translated into.

### Stylesheet
Visiting `/sitemap.xml` in a browser renders a human-readable styled view via the bundled `sitemap.xsl`.

## License

MIT

## Credits

- [Iperstudio](https://github.com/iperstudio)
