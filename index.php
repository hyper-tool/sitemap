<?php
require __DIR__ . '/vendor/autoload.php';

use samdark\sitemap\Sitemap;

global $max_level, $max_page, $now_level, $many_links, $enable_log;

$enable_log = false;
$atom_uri = 'www.yihonyiqi.com';
$base_uris = [
    'http://www.yihonyiqi.com/',
    'http://www.yihonyiqi.com/news.html',
    'http://www.yihonyiqi.com/product.html',
    'http://www.yihonyiqi.com/solution.html',
];
$urls = [];
foreach ($base_uris as $base_uri) {
    $max_level = 5;
    $max_page = 20;
    $now_level = 0;
    $many_links = [];
    $urls = getUrlIterator($base_uri) + $urls;
}
$urls = array_unique($urls);
$sitemap = new Sitemap(__DIR__ . '/sitemap.xml');
// add some URLs
foreach ($urls as $url) {
    $sitemap->addItem($url);
}
// write it
$sitemap->write();


function getUrlIterator(string $base_uri): array
{
    try {
        global $max_level, $max_page, $now_level, $many_links, $atom_uri, $enable_log;
        $now_page = 0;
        set_time_limit(1800);
        ini_set("max_execution_time", 1800);
        ini_set('memory_limit', '512M');
        $html_node = file_get_contents($base_uri);
        $crawler = new  \Symfony\Component\DomCrawler\Crawler($html_node, $base_uri);
        $links = $crawler->filter('a')->links();
        foreach ($links as $link) {
            $uri = $link->getUri();
            if (false !== strpos($uri, $atom_uri)) {
                $temp_links[] = $uri;
            }
        }
        if ($max_level >= $now_level && isset($temp_links)) {
            $temp_links = array_unique($temp_links);
            if (1 < count($temp_links)) {
                foreach ($temp_links as $temp_link) {
                    if ($max_page >= $now_page) {
                        $temp_link = trim(trim($temp_link), '#');
                        if (! in_array($temp_link, $many_links)) {
                            $enable_log && file_put_contents("logs/{$now_level}-{$now_page}", 'no_in-' . $temp_link . PHP_EOL, FILE_APPEND);
                            $many_links[] = $temp_link;
                            getUrlIterator($temp_link);
                        }
                    } else {
                        $enable_log && file_put_contents("logs/{$now_level}-{$now_page}", 'in-' . $temp_link . PHP_EOL, FILE_APPEND);
                        $many_links[] = $temp_link;
                    }
                    $now_page++;
                }

            } else {
                $enable_log && file_put_contents("logs/{$now_level}-{$now_page}", 'tl0-' . $temp_links[0] . PHP_EOL, FILE_APPEND);
                $many_links[] = $temp_links[0];
            }
            $now_level++;
        } else {
            $enable_log && file_put_contents("logs/{$now_level}-{$now_page}", 'base_usi-' . $base_uri . PHP_EOL, FILE_APPEND);
            $many_links[] = $base_uri;
        }
        return array_unique($many_links);
    } catch (Exception $exception) {
        echo $exception->getCode() . ', message:' . $exception->getMessage();
    }
}