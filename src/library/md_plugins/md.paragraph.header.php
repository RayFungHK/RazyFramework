<?php
return [
  'pattern' => 's4 s<3 *',
  'pattern' => '/(?<=\n)\h*(#{1,6})\h+([^#\n]+)/',
  'callback' => function($matches) {
    $level = strlen($matches[1]);
    return '<h' . $level . '>' . $this->parseModifier($this->parseVariable($matches[2])) . '</h' . $level . '>';
  }
];
?>
