<?php
return [
  'pattern' => '/(?:(?<=\n)\h*(?:\|[^\n]+)+\|\h*\R?)+/s',
  'callback' => function($matches) {
    $columnCount = 0;
    $result = '';
    $contents = explode("\n", trim($matches[0]));
    $separater = false;
    $alignment = array();

    foreach ($contents as $content) {
      $content = trim($content);
      if (!$content) {
        continue;
      }

      $column = explode('|', $content);
      array_shift($column);
      array_pop($column);

      if (!$separater) {
        if (!$columnCount) {
          $columnCount = count($column);
          $tableContent[] = $column;
        } else {
          if (preg_match('/(?:\|\h*:?-+:?\h*)+\|?/', $content)) {
            if ($columnCount != count($column)) {
              // Not a valid table format, return the original content
              return $matches[0];
            } else {
              $separater = true;
              foreach ($column as $align) {
                if (preg_match('/(:)?-+(:)?/', $align, $aMatches)) {
                  if (isset($aMatches[1]) && isset($aMatches[2])) {
                    $alignment[] = 'center';
                  } elseif (isset($aMatches[2])) {
                    $alignment[] = 'right';
                  } else {
                    $alignment[] = 'left';
                  }
                }
              }
            }
          }
        }
      } else {
        $tableContent[] = $column;
      }
    }

    if (!$separater) {
      return $matches[0];
    }

    foreach ($tableContent as $rIndex => $row) {
      if (!$rIndex) {
        $result .= '<thead><tr>';
        for ($cIndex = 0; $cIndex < $columnCount; $cIndex++) {
          $result .= '<th>' . $this->parseModifier($row[$cIndex]) . '</th>';
        }
        $result .= '</tr></thead><tbody>';
      } else {
        $result .= '<tr>';
        for ($cIndex = 0; $cIndex < $columnCount; $cIndex++) {
          $result .= '<td align="' . $alignment[$cIndex] . '">' . ((isset($row[$cIndex])) ? $this->parseModifier($row[$cIndex]) : '') . '</td>';
        }
        $result .= '</tr>';
      }
    }
    $result .= '</tbody>';

    return '<table>' . $result . '</table>';
  }
];
?>
