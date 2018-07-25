<?php
return [
  'type' => 'paragraph',
  'pattern' => '/(?:^[\t ]*|(?<=\n)[\t ]*)(?:([-+*])).*\n{0,2}(?:[\t ]*\1.*\n{0,2})*/',
  'callback' => function($matches) {
    $content = $matches[0];
    $content = preg_replace_callback(
      '/(?:\B[-+*])(.*\n{0,2})/',
      function ($matches) {
        $text = $this->parseModifier($matches[1]);
        // remove last \note
        $text = preg_replace('/\n$/', '', $text);
        return '<li>' . trim(preg_replace('/\n+/', '<br />', $text)) . '</li>';
      },
      $content
    );
    return '<ul>' . $content . '</ul>';
  }
];
?>
