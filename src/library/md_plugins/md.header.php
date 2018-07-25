<?php
return [
  'type' => 'paragraph',
  'pattern' => '/\B(#{1,6})([^#\r\n]+)/',
  'callback' => function($matches) {
    $level = strlen($matches[1]);
    return '<h' . $level . '>' . $this->parseModifier($matches[2]) . '</h' . $level . '>';
  }
];
?>
