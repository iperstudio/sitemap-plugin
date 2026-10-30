<?php

use Kirby\Cms\App;
use Kirby\Http\Response;
use Iperstudio\Sitemap\Sitemap;

require_once __DIR__ . '/Sitemap.php';

App::plugin('iperstudio/sitemap-plugin', [
    'routes' => [
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                return new Response(Sitemap::xml(kirby()), 'application/xml', headers: ['X-Robots-Tag' => 'noindex']);
            },
        ],
        [
            'pattern' => 'sitemap.xsl',
            'action' => function () {
                return new Response(file_get_contents(__DIR__ . '/sitemap.xsl'), 'text/xsl');
            },
        ],
        [
            'pattern' => 'sitemap',
            'action' => function () { return go(rtrim(kirby()->url('index'), '/') . '/sitemap.xml', 301); },
        ],
    ],
]);

// Retain the helper exposed by 1.0 for sites that call it from their templates.
if (!function_exists('sitemapPriority')) {
    function sitemapPriority($page): string
    {
        return Sitemap::priority($page);
    }
}
