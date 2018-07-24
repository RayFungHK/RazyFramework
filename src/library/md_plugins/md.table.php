<?php
return [
  'type' => 'paragraph',
  'pattern' => '^\s*\|.+\|\s*$',
  'filter' => function() {
    
  },
  'callback' => function($content) {
    return '<pre>' . $this->parseModifier($matches[1]) . '</pre>';
  }
];
?>
