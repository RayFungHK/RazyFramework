<?php
return [
  'type' => 'paragraph',
  'pattern' => '/(?:^[\t ]*|(?<=\n)[\t ]*)(?:>.*\n{0,2})+/',
  'callback' => function($matches) {
    $content = $matches[0];
    $content = preg_replace('/(?:^[\t ]*|(?<=\n)[\t ]*)>/', '', $content);
    return '<blockquote>' . $content . '</blockquote>';
  }
];
?>
