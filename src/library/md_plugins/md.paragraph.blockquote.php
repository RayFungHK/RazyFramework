<?php
return [
  'pattern' => '/(?<=\n)\h{0,3}>(?:[^\n]+\n?)+/s',
  'callback' => function($matches) {
    $content = preg_replace('/(?<=\n)|\B\h{0,3}>/', '', $matches[0]);
    return '<blockquote>' . str_replace("\n", '<br />', trim($content)) . '</blockquote>';
  }
];
?>
