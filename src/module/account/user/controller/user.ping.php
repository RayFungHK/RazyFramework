<?php
namespace Module
{
  return function($obj, $object) {
    $obj->client->send(\Core\WebSocketIO::Mask(json_encode(array(
      'result' => 1
    )), 'text', false));
    return true;
  };
}
?>
