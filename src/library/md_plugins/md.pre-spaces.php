<?php
return [
  'type' => 'paragraph',
  'pattern' => '^ {4,}(.+)',
  'callback' => function($matches) {
    return '<pre>' . $this->parseModifier($matches[1]) . '</pre>';
  }
];
?>
