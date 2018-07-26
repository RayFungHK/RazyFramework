<?php
return [
  'pattern' => '/(?<=\n)\h*(?:>[^\n]+\n{0,2})+/s',
  'callback' => function($matches) {
    $content = $matches[0];
    $content = preg_replace('/\n*>\h*/', '', $content);
    return '<blockquote>' . preg_replace('/\R/', '<br />', trim($content)) . '</blockquote>';
  }
];
?>
