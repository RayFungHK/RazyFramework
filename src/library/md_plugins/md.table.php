<?php
return [
  'type' => 'paragraph',
  'pattern' => '^\s*\|.+\|\s*$',
  'group' => true,
  'ignore' => true,
  'rule' => function($content, $paragraphContent) {
    if (count($paragraphContent) == 1) {
      // If the first line exists and next line is a separater
      if (preg_match('/^\s*(?:\|-+)+\|\s*$/', $content)) {
        return true;
      }
      return false;
    } elseif (preg_match('/^\s*(?:\|-+)+\|\s*$/', $content)) {
      // Separater cannot duplicated or no header exists
      return false;
    }
    return true;
  },
  'callback' => function($matches) {
    return '<pre>' . $this->parseModifier($matches[1]) . '</pre>';
  }
];
?>
