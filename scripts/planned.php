<?php

echo "\n";

foreach ( glob(__DIR__ . '/../articles/*.md') as $md_file ) {
  $md = file_get_contents($md_file);
  if ( !preg_match_all('/\(@plan:(.+?)\)/', $md, $m) ) {
    continue;
  }
  echo '# ' . basename($md_file) . ":\n";
  foreach ( $m[1] as $planned ) {
    echo '  > ' . trim($planned) . "\n";
  }

  echo "\n";
}
