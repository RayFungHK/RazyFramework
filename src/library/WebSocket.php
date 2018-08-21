<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  class WebSocket
  {
  	private $master;
  	private $clients         = [];
  	private $maxClient       = 100;
  	private $eventList       = [];
  	private $tickDuration    = 1000;
  	private $serverStartTick = 0;

  	public function __construct($address, $port = 8080, $maxClient = 100)
  	{
  		$this->maxClient = $maxClient;
  		$this->master    = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  		socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
  		if (!is_resource($this->master)) {
  			echo 'socket_create() failed: ' . socket_strerror(socket_last_error()) . "\n";
  		}

  		if (!socket_bind($this->master, $address, $port)) {
  			echo 'socket_bind() failed: ' . socket_strerror(socket_last_error()) . "\n";
  		}

  		if (!socket_listen($this->master, 20)) {
  			echo 'socket_listen() failed: ' . socket_strerror(socket_last_error()) . "\n";
  		}
  		$this->sockets = [$this->master];
  	}

  	public function tickDuration()
  	{
  	}

  	public function startListen()
  	{
  		$this->serverStartTick = round(microtime(true) * 1000);
  		$this->startAsync();
  		$this->looping();

  		return $this;
  	}

  	public function registerEvent($eventName, callable $callback)
  	{
  		$this->eventList[$eventName] = $callback;

  		return $this;
  	}

  	public function getClients()
  	{
  		return $this->client;
  	}

  	private function startAsync()
  	{
  		if (function_exists('pcntl_async_signals')) {
  			pcntl_async_signals(true);
  		} else {
  			declare(ticks=1);
  		}
  	}

  	private function trigger()
  	{
  		$arguments = func_get_args();
  		$eventName = array_shift($arguments);

  		if (isset($this->eventList[$eventName])) {
  			$closure = $this->eventList[$eventName];

  			return call_user_func_array($closure->bindTo($this, get_class($this)), $arguments);
  		}
  	}

  	private function looping()
  	{
  		while (true) {
  			$liveSockets                      = [];
  			$liveSockets[(int) $this->master] = $this->master;
  			foreach ($this->clients as $index => $client) {
  				$liveSockets[(int) $client->getSocket()] = $client->getSocket();
  			}

  			/*
  			if ($bytes === false) {
  				// onClientDisconnect
  				unset($this->clients[(int) $client->getSocket()]);
  				$this->trigger('onClientDisconnect', $client->getSocket());
  			}
  			*/

  			// onTick
  			$this->trigger('onTick');

  			// Select active socket
  			if (socket_select($liveSockets, $write, $except, 0) < 1) {
  				continue;
  			}

  			// If master socket active, means new client connected
  			if (isset($liveSockets[(int) $this->master])) {
  				$client                                    = new WebSocketPeer($this->master);
  				$this->clients[(int) $client->getSocket()] = $client;
  			}

  			foreach ($this->clients as $index => $client) {
  				if (isset($liveSockets[(int) $client->getSocket()])) {
  					if (!$client->isHandshaked()) {
  						if (!$client->doHandshake()) {
  							unset($this->clients[(int) $client->getSocket()]);
  						} else {
  							// onClientConnect
  							$this->trigger('onClientConnect', $client);
  						}
  					} else {
  						$decoded_data = '';
  						$response     = null;
  						$bytes        = false;
  						while ($bytes = @socket_recv($client->getSocket(), $data, 2048, MSG_DONTWAIT)) {
  							WebSocketIO::Unmask($data, $response);
  						}
  						if ($response) {
  							// onMessageReceive
  							$this->trigger('onMessageReceive', $client, $response);
  						}
  						//socket_close($client);
  					}
  				}
  			}
  		}
  	}
  }
}
