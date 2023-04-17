<?php

if ( pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION) == 'css' ) {
  header("Content-type: text/css");
  passthru('lessc /var/www/datachild/web/s.less');
}
