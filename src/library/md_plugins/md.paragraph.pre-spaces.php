<?php
return [
  'pattern' => '(?:(?>    |\t)[^\n]+\n*)+',
  'callback' => function($matches) {
    $content = $matches[0];
    $content = str_replace('/^\R+|\R+$/', '', $content);
    $content = preg_replace('/(?<=\n)(?:    |\t)/', '', $content);
    return '<pre>' . htmlspecialchars(trim($content)) . '</pre>';
  }
];
?>
