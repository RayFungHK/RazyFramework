<?php
return [
  'pattern' => '* n [-=]++',
  'pattern' => '/(?<=\n)([^\n]+)\n([\-=])\2*\n?/s',
  'callback' => function($matches) {
    $level = ($matches[2] == '=') ? 1 : 2;
    return '<h' . $level . '>' . $this->parseModifier($this->parseVariable(trim($matches[1]))) . '</h' . $level . '>';
  }
];
?>
