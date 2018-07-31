<?php
return [
  'pattern' => '(?:\h{0,3}\d+\.\h+(?:[^\n]+\n?)+)+',
  'callback' => function($matches) {
    $contents = explode("\n", $matches[0]);

    $result = '<ol>';
    $first = false;
    foreach ($contents as $line) {
      if (preg_match('/\h{0,3}(\d+\.)\h*(.+)/', $line, $matches)) {
        $content = $this->parseModifier($this->parseVariable($matches[2]));
        if (!$first) {
          $result .= '<li>' . $content;
        } else {
          $result .= (!$matches[1]) ? '<br />' . $content : '</li><li>' . $content;
        }
      }
    }
    $result .= '</ol>';

    return $result;
  }
];
?>
