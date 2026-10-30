<?php

namespace Iperstudio\Sitemap;

use Kirby\Cms\App;
use Kirby\Cms\Page;

final class Sitemap
{
    public static function option(App $kirby, string $key, mixed $fallback = null): mixed
    {
        return $kirby->option('iperstudio.sitemap-plugin.' . $key)
            ?? $kirby->option('iperstudio.sitemap.' . $key, $fallback);
    }

    public static function includes(Page $page, ?string $language = null): bool
    {
        $kirby = $page->kirby();
        if ($page->isDraft() || $page->isErrorPage()) return false;
        foreach ($page->parents() as $parent) if ($parent->isDraft()) return false;
        if ($language !== null && $kirby->multilang() && !$page->translation($language)->exists()) return false;
        if ($page->content($language)->get('seoNoindex')->isTrue()) return false;

        // Keep the legacy homepage exception unless a project opts into filtering it.
        if (!$page->isHomePage() || self::option($kirby, 'filterHome', false)) {
            $template = $page->intendedTemplate()->name();
            $include = self::option($kirby, 'includeTemplates', []);
            $exclude = self::option($kirby, 'excludeTemplates', []);
            if ($include && !in_array($template, $include, true)) return false;
            if (!$include && in_array($template, $exclude, true)) return false;
        }
        $allowedUnlisted = self::option($kirby, 'includeUnlisted', []);
        if (!$page->isHomePage() && $page->isUnlisted() && self::option($kirby, 'excludeUnlisted', true) && !in_array($page->id(), $allowedUnlisted, true)) return false;
        $filter = self::option($kirby, 'pageFilter');
        return !is_callable($filter) || $filter($page, $language) === true;
    }

    public static function priority(Page $page): string
    {
        return $page->isHomePage() ? '1.0' : number_format(max(0.1, 0.5 / $page->depth()), 1, '.', '');
    }

    public static function xml(App $kirby): string
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $stylesheet = rtrim($kirby->url('index'), '/') . '/sitemap.xsl';
        $xml->appendChild($xml->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . htmlspecialchars($stylesheet, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"'));
        $namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        $root = $xml->appendChild($xml->createElementNS($namespace, 'urlset'));
        if ($kirby->multilang()) $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        foreach ($kirby->site()->index() as $page) {
            $urls = [];
            if ($kirby->multilang()) {
                foreach ($kirby->languages() as $language) {
                    $code = $language->code();
                    if (self::includes($page, $code)) $urls[$code] = $page->url($code);
                }
            } elseif (self::includes($page)) {
                $urls[''] = $page->url();
            }
            $alternates = $urls;
            $default = $kirby->defaultLanguage()?->code();
            if ($default && isset($urls[$default]) && self::option($kirby, 'xDefault', true)) $alternates['x-default'] = $urls[$default];
            foreach ($urls as $code => $url) {
                $entry = $root->appendChild($xml->createElementNS($namespace, 'url'));
                $entry->appendChild($xml->createElementNS($namespace, 'loc'))->appendChild($xml->createTextNode($url));
                if ($modified = $page->modified(DATE_ATOM, 'date', $code ?: null)) {
                    $entry->appendChild($xml->createElementNS($namespace, 'lastmod'))->appendChild($xml->createTextNode($modified));
                }
                if ($kirby->multilang()) foreach ($alternates as $language => $href) {
                    $link = $entry->appendChild($xml->createElementNS('http://www.w3.org/1999/xhtml', 'xhtml:link'));
                    $link->setAttribute('rel', 'alternate');
                    $link->setAttribute('hreflang', $language);
                    $link->setAttribute('href', $href);
                }
                if (self::option($kirby, 'priority', true)) {
                    $entry->appendChild($xml->createElementNS($namespace, 'priority', self::priority($page)));
                }
            }
        }
        return $xml->saveXML();
    }
}
