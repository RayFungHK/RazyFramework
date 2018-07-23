<?php
return [
  'type' => 'modifier',
  'tag' => '__',
  'callback' => function($text) {
    return '<u>' . $this->parseModifier($text) . '</u>';
  }
];
?>
