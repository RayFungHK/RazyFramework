<?php
return [
  'type' => 'modifier',
  'tag' => '~~',
  'callback' => function($text) {
    return '<s>' . $this->parseModifier($text) . '</s>';
  }
];
?>
