<?php
return [
  'pattern' => '~~',
  'callback' => function($text) {
    return '<s>' . $this->parseModifier($text) . '</s>';
  }
];
?>
