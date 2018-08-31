<?php
return [
  'module_code' => 'example',
  'author' => 'Ray Fung',
  'version' => '1.0.0',
  'route' => array(
    // (:any)					Pass all arguments to 'any' method if there is no route was matched
    '(:any)' => 'example.main',
    'reroute' => 'example.reroute',
    'custom' => 'example.custom'
  ),
  'callable' => array(
    'method' => 'example.method',
    'onMessage' => 'example.onMessage'
  )
];
?>
