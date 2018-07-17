<?php
namespace Module
{
  class user extends \Core\IController
  {
    public function ws($args = array(), $params = array())
    {
      $actionlist = array(
        'login' => 'user.doLogin',
        'createlobby' => 'lobby.create',
        'ping' => 'user.ping'
      );

      $manager = $this->manager;
      $ws = new \Core\WebSocket($params['address'], $params['port']);
      $ws->registerEvent(
        'onMessageReceive',
        function($client, $response) use ($manager, $actionlist) {
          $client->send(\Core\WebSocketIO::Mask("You have entered: " . $response['text'], 'text', false));
          if (($object = json_decode($response['text'], true)) !== FALSE) {
            print_r($object);
            if (isset($object['action'])) {
              if (isset($actionlist[$object['action']])) {
                $manager->trigger($actionlist[$object['action']], $client, $object);
                return true;
              }
            }
          }

          $client->send(\Core\WebSocketIO::Mask(json_encode(array(
            'result' => 0,
            'error' => 'Invalid Data'
          )), 'text', false));
        }
      );

      $ws->registerEvent(
        'onClientConnect',
        function($client) {
          $client->send(\Core\WebSocketIO::Mask("Welcome", 'text', false));
        }
      );

      $ws->registerEvent(
        'onClientDisconnect',
        function($client) {
          echo 'Client disconnected';
        }
      );

      $ws->startListen();
    }
  }
}
?>
