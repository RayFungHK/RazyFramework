<?php
return [
  'pattern' => '##',
  'callback' => function($text) {
    return '<i>' . $this->parseModifier($text) . '</i>';
  }
];
?>
