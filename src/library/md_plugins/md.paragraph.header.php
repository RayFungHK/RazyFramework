<?php
return [
  'pattern' => '/(?<=\n)\h*(#{1,6})\h+([^#\n]+)/',
  'callback' => function($matches) {
    $level = strlen($matches[1]);
    return '<h' . $level . '>' . $this->parseModifier($matches[2]) . '</h' . $level . '>';
  }
];
?>
