<?php
return [
  'pattern' => '__',
  'callback' => function($text) {
    return '<u>' . $this->parseModifier($text) . '</u>';
  }
];
?>
