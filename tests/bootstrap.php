<?php

ob_start();
$bootstrap = getenv('KIRBY_BOOTSTRAP') ?: dirname(__DIR__) . '/kirby/bootstrap.php';
if (!is_file($bootstrap)) throw new RuntimeException('Install development dependencies or set KIRBY_BOOTSTRAP');
require $bootstrap;
require dirname(__DIR__) . '/index.php';

function check(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: $label\n";
}

function fixture(array $options = [], bool $multilang = true): Kirby\Cms\App
{
    $root = sys_get_temp_dir() . '/iperstudio-plugin-test-' . bin2hex(random_bytes(6));
    foreach (['content', 'site/config', 'site/languages', 'site/plugins', 'site/templates'] as $directory) {
        mkdir($root . '/' . $directory, 0777, true);
    }
    register_shutdown_function(static function () use ($root) { Kirby\Filesystem\Dir::remove($root); });
    if ($multilang) foreach (['it' => true, 'en' => false] as $code => $default) {
        file_put_contents($root . '/site/languages/' . $code . '.php', '<?php return ' . var_export([
            'code' => $code, 'default' => $default, 'name' => $code, 'locale' => 'en_US', 'url' => '/' . $code,
        ], true) . ';');
    }
    $pages = [
        'home' => ['home', 'title: Home'],
        '1_article' => ['articles-item', "title: Article\n----\nintro: Fallback introduction\n----\nhero: hero.png\n----\nmainImage: main.png"],
        '2_noindex' => ['default', "title: Private\n----\nseoNoindex: true"],
        '3_italian-only' => ['default', 'title: Italian only'],
        '4_split' => ['default', "title: Split\n----\nseoNoindex: true"],
        'privacy' => ['default', 'title: Privacy'],
        'error' => ['error', 'title: Error'],
        '_drafts/draft' => ['default', 'title: Draft'],
    ];
    $suffix = $multilang ? '.it' : '';
    file_put_contents($root . '/content/site' . $suffix . '.txt', 'title: Brand');
    foreach ($pages as $folder => [$template, $content]) {
        mkdir($root . '/content/' . $folder, 0777, true);
        file_put_contents($root . '/content/' . $folder . '/' . $template . $suffix . '.txt', $content);
        if ($multilang && $folder !== '3_italian-only') {
            $translation = $folder === '4_split' ? "title: Split EN\n----\nseoNoindex: false" : $content;
            file_put_contents($root . '/content/' . $folder . '/' . $template . '.en.txt', $translation);
            touch($root . '/content/' . $folder . '/' . $template . '.en.txt', 1700000000);
        }
    }
    $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS9sAAAAASUVORK5CYII=');
    foreach (['hero.png', 'main.png', 'share.png'] as $filename) {
        file_put_contents($root . '/content/1_article/' . $filename, $pixel);
    }
    file_put_contents($root . '/content/site.png', $pixel);
    return new Kirby\Cms\App([
        'roots' => ['index' => $root],
        'urls' => ['index' => 'https://example.test/subsite'],
        'options' => array_replace(['languages' => $multilang, 'languages.detect' => false], $options),
    ]);
}
