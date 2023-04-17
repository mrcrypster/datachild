<?php

chdir(__DIR__ . '/..');


# pull git
passthru('git pull');


# compile CSS
$web = realpath(__DIR__ . '/../web');
passthru("lessc {$web}/s.less {$web}/s.css");
