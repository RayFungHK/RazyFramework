<?php
return [
  'type' => 'modifier',
  'tag' => '**',
  'callback' => function($text) {
    return '<strong>' . $this->parseModifier($text) . '</strong>';
  }
];
?>
