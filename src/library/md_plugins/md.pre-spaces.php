<?php
return [
  'type' => 'paragraph',
  'pattern' => '/\B(?:(?:    |\t).+\n?)+/',
  'callback' => function($matches) {
    $result = '';
    $contents = preg_split('/\r\n|\r|\n/', $matches[0]);
    foreach ($contents as $content) {
      $result .= preg_replace('/^    /', '', $content);
    }
    return '<pre>' . $result . '</pre>';
  }
];
?>
