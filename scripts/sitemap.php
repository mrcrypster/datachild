<?php

chdir(__DIR__ . '/..');
require_once __DIR__ . '/../lib/app.php';

function url($url) {
  return '<url>' .
          "<loc>https://datachild.net{$url}</loc>" .
          '</url>';
}

$app = new app();

$f = fopen(__DIR__ . '/../web/sitemap.xml', 'w');
fputs($f, '<?xml version="1.0" encoding="UTF-8"?>');
fputs($f, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

$cats = [];

foreach ( $app->get_articles() as $article ) {
  fputs($f, url($article['url']));

  if ( $article['category'] && !$cats[$article['category']] ) {
    $cats[$article['category']] = true;
    fputs($f, url('/' . $article['category']));
  }

  foreach ( preg_split('/\s*[,;]\s*/', $article['tags']) as $t ) if ( $t ) {
    if ( !$cats[$t] ) {
      $cats[$t] = true;
      fputs($f, url('/' . $t));
    }
  }
}

fputs($f, '</urlset>');
