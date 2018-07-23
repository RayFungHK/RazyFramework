<?php
return [
  'type' => 'modifier',
  'tag' => '*',
  'callback' => function($text) {
    return '<i>' . $this->parseModifier($text) . '</i>';
  }
];
?>
