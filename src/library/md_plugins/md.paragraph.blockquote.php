<?php
return [
  'pattern' => '\h{0,3}>(?:[^\n]+\n?)+',
  'callback' => function($matches) {
    $content = preg_replace('/(?<=\n)|\B\h{0,3}>/', '', $matches[0]);
    return '<blockquote>' . str_replace("\n", '<br />', $this->parseVariable(trim($content))) . '</blockquote>';
  }
];
?>
