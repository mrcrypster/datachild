<?php

require_once 'helpers.php';

class app {
  public $endpoint;

  public function __construct() {
    $this->endpoint = parse_url($_SERVER['REQUEST_URI'])['path'];
  }

  public function run() {
    $data = $this->load();
    if ( $data === null ) {
      header('HTTP/1.0 404 Not Found', true, 404);
      exit;
    }

    $tpl = $_SERVER['REQUEST_METHOD'] == 'POST'
           ? $data['view']
           : ('layout' . (isset($_SERVER['HTTP_X_BODY_ONLY']) ? '.body' : ''));

    header('X-PAGE-TITLE:' . $this->title($data));
    include __DIR__ . '/../views/' . $tpl . '.phtml';
  }

  public function load() {
    $this->endpoint = urldecode($this->endpoint);
    if ( $this->endpoint == '/' ) {
      return [
        'view' => 'default',
        'articles' => $this->get_articles()
      ];
    }
    else if ( preg_match('/^\/[a-z\-0-9 ]+$/', $this->endpoint) ) {
      return [
        'category' => $this->endpoint,
        'articles' => $this->get_articles(['category' => trim($this->endpoint, '/')]),
        'view' => 'cat'
      ];
    }
    else if ( preg_match('/^\/[a-z\-0-9]+\/[a-z\-0-9]+$/', $this->endpoint) ) {
      return [
        'view' => 'post',
        'article' => $this->get_article($this->endpoint)
      ];
    }
  }



  /* utility layer */
  public function title($data) {
    $title = 'Data programming and ML';

    if ( isset($data['article']) ) {
      $title = $data['article']['title'];
    }
    else if ( isset($data['category']) ) {
      $title = $data['category'];
    }

    return $title . ' - DataChild';
  }



  /* data layer */
  public function get_articles($filter = []) {
    $list = [];
    foreach ( glob(__DIR__ . '/../articles/*.md') as $md ) {
      $a = $this->get_article($md);

      if ( $filter ) {
        if ( $filter['category'] ) {
          if ( $a['category'] != $filter['category'] ) {
            $tags = preg_split('/\s*[,;]\s*/', $a['tags']);
            if ( !in_array($filter['category'], $tags) ) {
              continue;
            }
          }
        }
      }

      $list[] = $a;
    }

    usort($list, function($a, $b) {
      return $a['publish'] < $b['publish'];
    });

    return $list;
  }

  public function get_article($url_or_md) {
    if ( !strpos($url_or_md, '.md') ) {
      foreach ( glob(__DIR__ . '/../articles/*.md') as $f ) {
        $md = file_get_contents($f);
        $url = trim(preg_fetch('/\* url: .+\.[^\.\/]+(\/.+)/', $md));
        if ( $url == $url_or_md ) {
          $md_file = $f;
          break;
        }
      }

      if ( !isset($md_file) ) return;
      $url_or_md = $md_file;
    }

    $cache_file = __DIR__ . '/../cache/' . md5($url_or_md);
    if ( is_file($cache_file) ) {
      $data = json_decode(file_get_contents($cache_file), true);
      if ( $data['ts'] != filemtime($url_or_md) ) {
        $data = null;
      }
    }

    if ( !isset($data) || !$data ) {
      $md = file_get_contents($url_or_md);
      $body_md = trim(preg_fetch('/\r?\n\r?\n(.+)$/misu', $md));
      ob_start();
      passthru('echo ' . escapeshellarg($body_md) . '| pandoc -t html');
      $html = ob_get_clean();

      $html = preg_replace('/\*\*\*(.+)\*\*\*/', '<b>$1</b>', $html);
      $html = str_replace('</code> -', '</code> &mdash; ', $html);

      $data = [
        'title' => trim(preg_fetch('/^#(.+)/', $md)),
        'html' => $html,
        'url' => trim(preg_fetch('/\* url: .+\.[^\.\/]+(\/.+)/', $md)),
        'description' => trim(preg_fetch('/\* description: (.+)/', $md)),
        'category' => trim(preg_fetch('/\* category: (.+)/', $md)),
        'tags' => trim(preg_fetch('/\* tags: (.+)/', $md)),
        'publish' => trim(preg_fetch('/\* published: (.+)/', $md)),
        'ts' => filemtime($url_or_md)
      ];

      file_put_contents($cache_file, json_encode($data));
    }

    return $data;
  }
}
