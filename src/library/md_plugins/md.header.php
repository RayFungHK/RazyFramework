<?php
return [
  'type' => 'paragraph',
  'pattern' => '^\s*(#{1,6})(.+)',
  'callback' => function($matches) {
    $level = strlen($matches[1]);
    return '<h' . $level . '>' . $this->parseModifier($matches[2]) . '</h' . $level . '>';
  }
];
?>
