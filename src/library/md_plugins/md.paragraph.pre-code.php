<?php
return [
  'pattern' => 's<3 `3 [ws]* +? n s<3 `3 s*`',
  'pattern' => '/(?<=\n|\A)\h{0,3}```([\w\h]*)\n(.+?)(?>\n\h{0,3}```\h*(?=\n)|\Z)/s',
  'callback' => function($matches) {
    $content = $matches[2];
    $content = preg_replace('/^\R+|\R+$/', '', $content);
    return '<pre' . (($matches[1]) ? ' language="' . $matches[1] . '"' : '') . '><code>' . htmlspecialchars($content) . '</code></pre>';
  }
];
?>
