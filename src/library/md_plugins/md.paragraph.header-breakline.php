<?php
return [
  'pattern' => '/(?<=\n)([^\n]+)\n([\-=])\2*\n?/s',
  'callback' => function($matches) {
    $level = ($matches[2] == '=') ? 1 : 2;
    return '<h' . $level . '>' . $this->parseModifier(trim($matches[1])) . '</h' . $level . '>';
  }
];
?>
