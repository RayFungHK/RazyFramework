<?php
return [
  'pattern' => '/(?:(?<=\n)(?:(?:    |\t)[^\n]+\n*))+/s',
  'callback' => function($matches) {
    $content = $matches[0];
    $content = str_replace('/^\R+|\R+$/', '', $content);
    $content = preg_replace('/(?<=\n)(?:    |\t)/', '', $content);
    return '<pre>' . htmlspecialchars(trim($content)) . '</pre>';
  }
];
?>
