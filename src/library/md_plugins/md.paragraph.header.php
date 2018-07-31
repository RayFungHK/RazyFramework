<?php
return [
  'pattern' => '\h{0,3}(#{1,6})\h+([^#\n]+)',
  'callback' => function($matches) {
    $level = strlen($matches[1]);
    return '<h' . $level . '>' . $this->parseModifier($this->parseVariable($matches[2])) . '</h' . $level . '>';
  }
];
?>
