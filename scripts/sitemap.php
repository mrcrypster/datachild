<?php

chdir(__DIR__ . '/..');
require_once __DIR__ . '/../lib/app.php';

$app = new app();

$f = fopen(__DIR__ . '/../web/sitemap.xml', 'w');
fputs($f, '<?xml version="1.0" encoding="UTF-8"?>');
fputs($f, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

foreach ( $app->get_articles() as $article ) {
  fputs($f, '<url>' .
            "<loc>https://datachild.net{$article['url']}</loc>" .
            '</url>');
}

fputs($f, '</urlset>');
