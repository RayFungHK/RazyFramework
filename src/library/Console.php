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
  class Console
  {
  	private static $commands = [];
  	private $distribution    = '/';

  	public function __construct()
  	{
  		echo '======================================' . NEW_LINE;
  		echo 'Razy Framework console mode is started' . NEW_LINE;
  		echo '======================================' . NEW_LINE;
  		$this->readline();
  	}

  	public function command($command, $args)
  	{
  		$closureFunction = null;
  		if (!array_key_exists($command, self::$commands)) {
  			self::$commands[$command] = null;

  			$pluginFile  = __DIR__ . \DIRECTORY_SEPARATOR . 'console_plugins' . \DIRECTORY_SEPARATOR . 'console.' . $command . '.php';
  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$commands[$command] = $callback;
  					$closureFunction          = $callback;
  				}
  			}
  		} else {
  			$closureFunction = self::$commands[$command];
  		}

  		if (!$closureFunction) {
  			echo 'Command ' . $command . ' not found' . NEW_LINE;

  			return $this->readline();
  		}
  		call_user_func($closureFunction->bindTo((object) $args), $args['arguments']);
  	}

  	private function readline()
  	{
      echo 'Razy@' . $this->distribution . ': ';
  		$handle   = fopen('php://stdin', 'r');
  		$response = fgets($handle);
  		$this->receive(trim($response));
  		$this->readline();
  	}

  	private function receive($response)
  	{
  		if (preg_match('/(\w+)((?:\s+(?:(\w+|([\'"]).*?\4)|--?\w+(?:\s+(?3))?))*)/', $response, $matches)) {
  			list($script, $command, $argString) = $matches;
  			if ('exit' === $command) {
  				exit();
  			}

  			$args = [
  				'arguments'  => [],
  				'parameters' => [],
  			];
  			preg_match_all('/\s+(?:(\w+|([\'"]).*?\2)|--?(\w+)(?:\s+((?1)))?)/', $argString, $matches, PREG_SET_ORDER);
  			foreach ($matches as $clip) {
  				if (!isset($clip[3])) {
  					if (count($args['parameters'])) {
  						echo 'Syntax error' . NEW_LINE;

  						return $this->readline();
  					}
  					// Arguments
  					$args['arguments'][] = $clip[2];
  				} else {
  					// Parameters
  					$args['parameters'][$clip[3]] = $clip[4] ?? '';
  				}
  			}
  			$this->command($command, $args);
  		} else {
  			echo 'Invalid command' . NEW_LINE;

  			return $this->readline();
  		}
  	}
  }
}
