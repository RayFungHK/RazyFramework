<?php
return [
  'pattern' => '``',
  'callback' => function($text) {
    return '<code>' . $this->parseModifier($text) . '</code>';
  }
];
?>
