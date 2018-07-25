<?php
return [
  'type' => 'paragraph',
  'pattern' => '/(?:^[\t ]*|(?<=\n)[\t ]*)```(\w*)\n(.+?)\n```\n?/s',
  'callback' => function($matches) {
    return '<pre' . (($matches[1]) ? ' language="' . $matches[1] . '"' : '') . '><code>' . $matches[2] . '</code></pre>';
  }
];
?>
