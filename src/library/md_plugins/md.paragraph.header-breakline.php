<?php
return [
  'pattern' => '([^\n]+)\n([\-=])\2+(?=\Z)\n?',
  'callback' => function($matches) {
    $level = ($matches[2] == '=') ? 1 : 2;
    return '<h' . $level . '>' . $this->parseModifier($this->parseVariable(trim($matches[1]))) . '</h' . $level . '>';
  }
];
?>
