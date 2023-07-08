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
# ...
