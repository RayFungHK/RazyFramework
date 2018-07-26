<?php
return [
  'pattern' => '/(?<=\n)\h{0,3}([-*+])\h*(?:[^\n]+\n?)+((?<=\n)\h{0,3}(\1)\h*(?:[^\n]+\n?)+)*/s',
  'callback' => function($matches) {
    $content = $matches[0];
    print_r($matches);
    $content = preg_replace_callback(
      '/(?<=\n)\h{0,3}([-*+])\h*([^\n]+\n?)+/',
      function ($matches) {
        $text = $this->parseModifier($matches[2]);
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
