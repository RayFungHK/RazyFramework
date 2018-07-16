<?php
namespace Module
{
  return function($client, $object) {
    $client->send(\Core\WebSocketIO::Mask(json_encode(array(
      'result' => 1,
      'module' => 'login',
    )), 'text', false));
    return true;
    $client->send(\Core\WebSocketIO::Mask(json_encode(array(
      'result' => 0,
      'module' => 'login',
      'error' => 'Invalid email or password'
    )), 'text', false));
  };
}
?>
