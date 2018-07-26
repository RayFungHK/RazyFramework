<?php
return [
  'pattern' => '/(?<=\n)\h*```(\w*)\h*\R(.+?)\h{0,3}\n```\h*/s',
  'callback' => function($matches) {
    $content = $matches[2];
    $content = preg_replace('/^\R+|\R+$/', '', $content);
    return '<pre' . (($matches[1]) ? ' language="' . $matches[1] . '"' : '') . '><code>' . htmlspecialchars($content) . '</code></pre>';
  }
];
?>
