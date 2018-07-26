<?php
return [
  'pattern' => '**',
  'callback' => function($text) {
    return '<strong>' . $this->parseModifier($text) . '</strong>';
  }
];
?>
