<?php

chdir(__DIR__ . '/..');


# pull git
passthru('git pull');


# compile CSS
$web = realpath(__DIR__ . '/../web');
passthru("lessc {$web}/s.less {$web}/s.css");


# remove cache
exec('rm -rf /var/cache/nginx/datachild/*');


# rebuild sitemaps
include __DIR__ . '/sitemap.php';


# regenerate README.md articles
$md = '# Datachild -  blog about data and ML' . "\n" .
      'This is a backend repository for https://datachild.net/ platform.' . "\n\n";
foreach ( $app->get_articles() as $a ) {
  echo $a['title'] . ' -> ' . $a['url'] . "\n";
  $md .= '- [' . $a['title'] . '](https://datachild.net' . $a['url'] . ')' . "\n";
}

file_put_contents(__DIR__ . '/../README.md', $md);
