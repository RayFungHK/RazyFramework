<?php
return [
  'pattern' => '/(?:(?<=\n)\h{0,3}(?:[-*+])\h+(?:(?:[^\n]+)\n?)+)+/s',
  'callback' => function($matches) {
    $contents = explode("\n", $matches[0]);

    $lastPointer = '';

    $result = '<ul>';
    foreach ($contents as $line) {
      if (preg_match('/\h{0,3}([-*+])\h*(.+)/', $line, $matches)) {
        if (!$lastPointer) {
          $lastPointer = $matches[1];
          $result .= '<li>' . $this->parseModifier($matches[2]);
        } else {
          if (!$matches[1]) {
            $result .= '<br />' . $this->parseModifier($matches[2]);
          } else {
            $result .= ($lastPointer == $matches[1]) ? '</li><li>' . $this->parseModifier($matches[2]) : '</li></ul><li>' . $this->parseModifier($matches[2]);
          }
        }
      }
    }
    $result .= '</ul>';

    return $result;
  }
];
?>
