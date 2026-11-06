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
check(str_contains($xml->saveXML(), 'type="text/css" href="https://example.test/subsite/sitemap.css"'), 'CSS is the default stylesheet and honors the installation base path');
check(!str_contains($xml->saveXML(), 'text/xsl'), 'Default sitemap does not invoke browser XSLT');
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
$css = $kirby->call('sitemap.css', 'GET');
check($css->code() === 200 && $css->type() === 'text/css' && str_contains($css->body(), '@namespace sitemap'), 'CSS route serves namespace-aware sitemap styles');
$xsl = new DOMDocument;
check($xsl->loadXML($kirby->call('sitemap.xsl', 'GET')->body()), 'Legacy XSL route remains available');
check($xsl->documentElement->getAttribute('version') === '1.0', 'Legacy stylesheet remains XSLT 1.0');
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

$content = [];
foreach (['css', 'xsl', false] as $style) {
    $kirby = new Kirby\Cms\App([
        'roots' => ['index' => $kirby->root('index')],
        'urls' => ['index' => 'https://example.test/sub&site'],
        'options' => ['languages' => true, 'iperstudio.sitemap-plugin.stylesheet' => $style],
    ]);
    [$xml, $xpath] = sitemapDocument($kirby);
    $content[] = $xml->documentElement->C14N();
    if ($style === false) check($xpath->query('/processing-instruction("xml-stylesheet")')->length === 0, 'Stylesheet can be disabled for raw XML');
    if ($style === 'xsl') check(str_contains($xml->saveXML(), 'type="text/xsl"'), 'Legacy XSL can be explicitly enabled');
}
check(count(array_unique($content)) === 1, 'CSS, legacy XSL and raw XML have identical sitemap data');
$kirby = fixture(['iperstudio.sitemap.stylesheet' => false]);
check(!str_contains(Sitemap::xml($kirby), '<?xml-stylesheet'), 'Stylesheet option also supports the legacy prefix');

$tree = [
    '5_container' => ['container', 'title: Container'],
    '5_container/1_child' => ['default', 'title: Child'],
    '5_container/1_child/1_leaf' => ['default', 'title: Leaf'],
    '5_container/2_sibling' => ['default', 'title: Sibling'],
    '5_container-extra' => ['default', 'title: Similar ID'],
    '2_noindex/1_public' => ['default', 'title: Public child'],
    'privacy/1_public' => ['default', 'title: Public child'],
    '_drafts/draft/1_hidden' => ['default', 'title: Hidden child'],
];
foreach ([false, true] as $multilang) {
    $kirby = fixture(['iperstudio.sitemap-plugin' => [
        'excludeTemplates' => ['container'],
        'pageFilter' => static fn ($page) => $page->id() !== 'container/child',
    ]], $multilang, $tree);
    $languages = [null];
    if ($multilang) {
        $languages = [];
        foreach ($kirby->languages() as $language) $languages[] = $language->code();
    }
    [$xml, $xpath] = sitemapDocument($kirby);
    $actual = [];
    foreach ($xpath->query('//s:loc') as $node) $actual[] = $node->textContent;
    $expected = [];
    foreach ($kirby->site()->index() as $page) {
        foreach ($languages as $language) {
            if (Sitemap::includes($page, $language)) $expected[] = $page->url($language);
        }
    }
    check($actual === $expected, 'Progressive traversal preserves index order and URLs on nested sites');
    foreach (['container/child/leaf', 'noindex/public', 'privacy/public'] as $id) {
        check(in_array($kirby->site()->find($id)->url($multilang ? 'it' : null), $actual, true), 'Eligible descendants survive template, callback, noindex and unlisted parent exclusions');
    }

    $visited = [];
    $kirby = fixture(['iperstudio.sitemap-plugin' => [
        'excludeBranches' => ['container/child', 'privacy', 'home'],
        'includeUnlisted' => ['privacy'],
        'pageFilter' => static function ($page) use (&$visited) {
            $visited[] = $page->id();
            return true;
        },
    ]], $multilang, $tree);
    $branch = $kirby->site()->find('container/child');
    $privacy = $kirby->site()->find('privacy');
    check($branch->children === null && $privacy->children === null, 'Excluded branch children start unloaded');
    [$xml, $xpath] = sitemapDocument($kirby);
    check($branch->children === null && $privacy->children === null, 'Excluded branch children are never loaded by sitemap generation');
    check(!in_array('container/child', $visited, true) && !in_array('container/child/leaf', $visited, true), 'Branch exclusions run before page callbacks');
    $expected = [];
    foreach (['container/child', 'privacy', 'home'] as $id) {
        $url = $kirby->site()->find($id)->url($multilang ? 'it' : null);
        check($xpath->query('//s:loc[.="' . $url . '"]')->length === 0, 'Branch roots are excluded, including home and explicitly allowed unlisted pages');
    }
    foreach ($kirby->site()->index() as $page) {
        if (in_array($page->id(), ['home', 'privacy', 'privacy/public', 'container/child', 'container/child/leaf', 'error', 'noindex'], true)) continue;
        foreach ($languages as $language) {
            // The split page only has an eligible English translation.
            if ($page->id() === 'split' && $language !== 'en') continue;
            if ($page->id() === 'italian-only' && $language === 'en') continue;
            $expected[] = $page->url($language);
        }
    }
    $actual = [];
    foreach ($xpath->query('//s:loc') as $node) $actual[] = $node->textContent;
    check($actual === $expected, 'Nested branch exclusion preserves siblings, similarly named pages and translation eligibility');
    check(!Sitemap::includes($branch) && !Sitemap::includes($branch->find('leaf')), 'Public includes helper excludes branch roots and descendants');
}
$kirby = fixture(['iperstudio.sitemap.excludeBranches' => ['article']]);
check(!Sitemap::includes($kirby->site()->find('article')) && !str_contains(Sitemap::xml($kirby), '/article'), 'Branch exclusions support the legacy option prefix');
$kirby = fixture([
    'iperstudio.sitemap.excludeBranches' => ['article'],
    'iperstudio.sitemap-plugin.excludeBranches' => [],
]);
check(Sitemap::includes($kirby->site()->find('article')) && str_contains(Sitemap::xml($kirby), '/article'), 'An empty new branch list overrides legacy branch exclusions');

ob_end_flush();
