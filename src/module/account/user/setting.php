<?php
return [
  'module_code' => 'user',
  'author' => 'Ray Fung',
  'version' => '1.0.0',
  'route' => array(
    // (:any)					Pass all arguments to 'any' method if there is no route was matched
    '(:any)' => 'user.main'
  ),
  'cli' => array(
    'ws' => 'user.ws'
  ),
  'command' => array(
    'doLogin' => 'user.doLogin',
    'ping' => 'user.ping'
  ),
  'event' => array(
    'onload' => 'user.onload'
  )
];
?>
