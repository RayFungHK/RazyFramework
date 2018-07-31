<?php
return [
  'pattern' => '\h{0,3}```(\w*\h*)\n(.+?)(?>\n```(?!\h{4})|\Z)',
  'callback' => function($matches) {
    $content = $matches[2];
    $content = preg_replace('/^\R+|\R+$/', '', $content);
    return '<pre' . (($matches[1]) ? ' language="' . $matches[1] . '"' : '') . '><code>' . htmlspecialchars($content) . '</code></pre>';
  }
];
?>
