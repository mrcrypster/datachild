<?php

function published($ts) {
  $t = strtotime($ts);
  $n = strtotime(date('Y-m-d'));

  $ago = $n - $t;

  if ( $ago <= 0 ) {
    return 'today';
  }
  else if ( $ago <= 60*60*24 * 1 ) {
    return 'yesterday';
  }
  else if ( $ago <= 60*60*24 * 7 ) {
    return 'this week';
  }
  else if ( $ago <= 60*60*24 * 14 ) {
    return 'a week ago';
  }
  else if ( $ago <= 60*60*24 * 21 ) {
    return 'this month';
  }
  else if ( $ago <= 60*60*24 * 45 ) {
    return 'a month ago';
  }
  else if ( $ago <= 60*60*24 * 70 ) {
    return '2 months ago';
  }
  else if ( $ago <= 60*60*24 * 100 ) {
    return '3 months ago';
  }
  else if ( $ago <= 60*60*24 * 200 ) {
    return 'half a year ago';
  }
  else if ( $ago <= 60*60*24 * 400 ) {
    return 'a year ago';
  }
  else if ( $ago <= 60*60*24 * 800 ) {
    return '2 years ago';
  }
  else {
    return 'years ago';
  }
}


function tags($tags, $linked = false) {
  if ( !$tags ) return;
  $tags = preg_split('/\s*[,;]\s*/', $tags);

  if ( $linked ) {
    foreach ( $tags as $k => $v ) {
      $tags[$k] = "<a href=\"/{$v}\">{$v}</a>";
    }
  }

  if ( count($tags) > 1 ) {
    $last_tag = array_pop($tags);
  }

  return ' about <b>#' . implode('</b>, <b>#', $tags) . '</b>' .
         ( isset($last_tag) ? ' and <b>#' . $last_tag . '</b>' : '');
}

function preg_fetch($regex, $text) {
  if ( preg_match($regex, $text, $m) ) {
    return $m[1];
  }
}
