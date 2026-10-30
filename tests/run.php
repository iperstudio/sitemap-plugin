<?php

require __DIR__ . '/bootstrap.php';

use Iperstudio\Sitemap\Sitemap;

function sitemapDocument(Kirby\Cms\App $kirby): array
{
    $xml = new DOMDocument;
    check($xml->loadXML(Sitemap::xml($kirby)), 'Sitemap is well-formed XML');
    $xpath = new DOMXPath($xml);
    $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xpath->registerNamespace('x', 'http://www.w3.org/1999/xhtml');
    return [$xml, $xpath];
}

$kirby = fixture();
$kirby->setCurrentLanguage('it');
[$xml, $xpath] = sitemapDocument($kirby);
$actual = [];
foreach ($xpath->query('//s:loc') as $node) $actual[] = $node->textContent;
$expected = [];
foreach (['home' => ['it', 'en'], 'article' => ['it', 'en'], 'italian-only' => ['it'], 'split' => ['en']] as $id => $languages) {
    foreach ($languages as $language) $expected[] = $kirby->site()->find($id)->url($language);
}
sort($actual); sort($expected);
check($actual === $expected && count($actual) === count(array_unique($actual)), 'Only eligible translated URLs appear, without duplicates');
foreach ($xpath->query('//s:url') as $entry) {
    $url = $xpath->query('s:loc', $entry)->item(0)->textContent;
    $links = [];
    foreach ($xpath->query('x:link', $entry) as $link) $links[$link->getAttribute('hreflang')] = $link->getAttribute('href');
    if (str_ends_with($url, '/split')) {
        check($links === ['en' => $url], 'Noindex translation is excluded from URLs and alternates, even when it is the current language');
    } elseif (str_ends_with($url, '/italian-only')) {
        check(array_keys($links) === ['it', 'x-default'], 'Missing translations are omitted from hreflang');
    } else {
        $keys = array_keys($links); sort($keys);
        check($keys === ['en', 'it', 'x-default'], 'Multilingual clusters contain both languages and x-default');
    }
    $modified = $xpath->query('s:lastmod', $entry)->item(0)->textContent;
    check((bool)DateTimeImmutable::createFromFormat(DATE_ATOM, $modified), 'lastmod is a valid timestamp');
    if (str_contains($url, '/en/')) check(strtotime($modified) === 1700000000, 'lastmod uses the actual translation modification time');
}
check(str_contains($xml->saveXML(), 'https://example.test/subsite/sitemap.xsl'), 'Stylesheet URL includes the installation base path');
$draft = $kirby->site()->draft('draft');
$child = new Kirby\Cms\Page(['slug' => 'child', 'parent' => $draft, 'num' => 1]);
check(!Sitemap::includes($draft) && !Sitemap::includes($child), 'Drafts and descendants are excluded');
check(sitemapPriority($kirby->site()->homePage()) === '1.0' && sitemapPriority($kirby->site()->find('article')) === '0.5', 'Legacy helper and priority values are retained');

$kirby = fixture(['iperstudio.sitemap' => ['includeTemplates' => ['default'], 'excludeTemplates' => ['default']]]);
check(!Sitemap::includes($kirby->site()->find('article')) && Sitemap::includes($kirby->site()->find('italian-only')), 'Legacy nested options work and includeTemplates takes precedence');
check(Sitemap::includes($kirby->site()->homePage()), 'Legacy homepage filtering exception is preserved');
$kirby = fixture(['iperstudio.sitemap.excludeUnlisted' => false]);
check(Sitemap::includes($kirby->site()->find('privacy')), 'Legacy flat options preserve false values');
$kirby = fixture([
    'iperstudio.sitemap.includeTemplates' => ['excluded'],
    'iperstudio.sitemap-plugin.includeTemplates' => [],
    'iperstudio.sitemap-plugin.includeUnlisted' => ['privacy'],
    'iperstudio.sitemap-plugin.priority' => false,
    'iperstudio.sitemap-plugin.pageFilter' => static fn ($page) => $page->id() !== 'article',
]);
check(Sitemap::includes($kirby->site()->find('privacy')) && !Sitemap::includes($kirby->site()->find('article')), 'New prefix overrides legacy values, supports empty arrays and project filters');
[$xml, $xpath] = sitemapDocument($kirby);
check($xpath->query('//s:priority')->length === 0, 'Automatic priority can be disabled');

$kirby = fixture(['iperstudio.sitemap-plugin' => ['includeTemplates' => ['excluded'], 'filterHome' => true]]);
check(!Sitemap::includes($kirby->site()->homePage()), 'Homepage template filtering is available explicitly');
$kirby = fixture([], false);
[$xml, $xpath] = sitemapDocument($kirby);
check($xpath->query('//s:loc')->length === 3 && $xpath->query('//x:link')->length === 0, 'Single-language sites have no hreflang links');
$response = $kirby->call('sitemap.xml', 'GET');
check($response->code() === 200 && $response->header('X-Robots-Tag') === 'noindex', 'Sitemap route returns XML with noindex for the sitemap document');
$xsl = new DOMDocument;
check($xsl->loadXML($kirby->call('sitemap.xsl', 'GET')->body()), 'Stylesheet route returns well-formed XSL');
check($xsl->documentElement->getAttribute('version') === '1.0', 'Stylesheet uses browser-compatible XSLT 1.0');
if (class_exists('XSLTProcessor')) {
    $processor = new XSLTProcessor;
    $processor->importStylesheet($xsl);
    check(str_contains($processor->transformToXML($xml), '/article'), 'Stylesheet renders actual page URLs');
}
$kirby = fixture();
unlink($kirby->root('languages') . '/en.php');
$kirby = new Kirby\Cms\App(['roots' => ['index' => $kirby->root('index')], 'urls' => ['index' => 'https://example.test/subsite'], 'options' => ['languages' => true]]);
[$xml, $xpath] = sitemapDocument($kirby);
check($xpath->query('//x:link')->length > 0 && str_contains($xml->saveXML(), '/it/article'), 'A multilingual setup with one configured language still uses translated URLs');

$kirby = new Kirby\Cms\App(['roots' => ['index' => $kirby->root('index')], 'urls' => ['index' => 'https://example.test/sub&site'], 'options' => ['languages' => true]]);
[$xml, $xpath] = sitemapDocument($kirby);
check(str_contains($xml->saveXML(), 'sub&amp;site') && str_contains($xpath->query('//s:loc')->item(0)->textContent, 'sub&site'), 'URLs containing ampersands are escaped once and round-trip correctly');

ob_end_flush();
