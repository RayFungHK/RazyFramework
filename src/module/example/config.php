<?php
return [
  'module_code' => 'example',
  'author' => 'Ray Fung',
  'version' => '1.0.0',
  'remap' => '/',
  'route' => array(
    // (:any)					Pass all arguments to 'any' method if there is no route was matched
    '(:any)' => 'example.main'
  ),
  'callable' => array(
    'getIP' => 'example.getIP',
    'onMessage' => 'example.onMessage'
  )
];
?>
