<?php
return [
  'pattern' => '\h{0,3}([-*_])\1{2,}\h*(?=\n|\Z)',
  'callback' => function($matches) {
    return '<hr />';
  }
];
?>
