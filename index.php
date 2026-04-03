<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\App;
use Kirby\Cms\Response;

Kirby::plugin('iperstudio/sitemap-plugin', [
    'options' => [
        // Whitelist mode: only these templates are included in the sitemap.
        // Takes precedence over excludeTemplates when not empty.
        'includeTemplates' => [],

        // Blacklist mode: these templates are excluded from the sitemap.
        // Only used when includeTemplates is empty.
        'excludeTemplates' => [],

        // Exclude unlisted pages
        'excludeUnlisted' => true,
    ],

    'routes' => [
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                $kirby   = App::instance();
                $include = $kirby->option('iperstudio.sitemap.includeTemplates', []);
                $exclude = $kirby->option('iperstudio.sitemap.excludeTemplates', []);
                $excludeUnlisted = $kirby->option('iperstudio.sitemap.excludeUnlisted', true);
                $useWhitelist = !empty($include);

                // Get all pages recursively
                $pages = site()->pages()->index();

                // Filter pages
                $pages = $pages->filter(function ($page) use ($include, $exclude, $excludeUnlisted, $useWhitelist) {
                    // Always include homepage, even if unlisted.
                    if ($page->isHomePage()) {
                        return true;
                    }

                    $template = $page->intendedTemplate()->name();

                    if ($useWhitelist) {
                        if (!in_array($template, $include)) {
                            return false;
                        }
                    } elseif (in_array($template, $exclude)) {
                        return false;
                    }

                    if ($excludeUnlisted && $page->isUnlisted()) {
                        return false;
                    }

                    return true;
                });

                $languages = $kirby->languages();
                $isMultilang = $languages->count() > 1;

                $xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
                $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . PHP_EOL;
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

                if ($isMultilang) {
                    $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml"';
                }

                $xml .= '>' . PHP_EOL;

                foreach ($pages as $page) {
                    if ($isMultilang) {
                        foreach ($languages as $lang) {
                            // Skip if the page doesn't exist in this language
                            if (!$page->translation($lang->code())->exists()) {
                                continue;
                            }

                            $xml .= '  <url>' . PHP_EOL;
                            $xml .= '    <loc>' . html($page->url($lang->code())) . '</loc>' . PHP_EOL;
                            $xml .= '    <lastmod>' . $page->modified('c', 'date') . '</lastmod>' . PHP_EOL;

                            // Add hreflang alternates
                            foreach ($languages as $altLang) {
                                if ($page->translation($altLang->code())->exists()) {
                                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . $altLang->code() . '" href="' . html($page->url($altLang->code())) . '" />' . PHP_EOL;
                                }
                            }

                            $xml .= '    <priority>' . sitemapPriority($page) . '</priority>' . PHP_EOL;
                            $xml .= '  </url>' . PHP_EOL;
                        }
                    } else {
                        $xml .= '  <url>' . PHP_EOL;
                        $xml .= '    <loc>' . html($page->url()) . '</loc>' . PHP_EOL;
                        $xml .= '    <lastmod>' . $page->modified('c', 'date') . '</lastmod>' . PHP_EOL;
                        $xml .= '    <priority>' . sitemapPriority($page) . '</priority>' . PHP_EOL;
                        $xml .= '  </url>' . PHP_EOL;
                    }
                }

                $xml .= '</urlset>';

                return new Response($xml, 'application/xml');
            }
        ],
        [
            'pattern' => 'sitemap.xsl',
            'action' => function () {
                $xsl = file_get_contents(__DIR__ . '/sitemap.xsl');
                return new Response($xsl, 'text/xsl');
            }
        ],
        [
            'pattern' => 'sitemap',
            'action' => function () {
                return go('sitemap.xml', 301);
            }
        ]
    ]
]);

/**
 * Calculate priority based on page depth
 */
function sitemapPriority($page): string
{
    if ($page->isHomePage()) {
        return '1.0';
    }
    return number_format(max(0.1, 0.5 / $page->depth()), 1);
}
