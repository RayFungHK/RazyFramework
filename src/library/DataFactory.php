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
  class DataFactory extends \ArrayObject
  {
  	private static $preventWarning  = true;
  	private static $functionMapping = [];
  	private $pointer;
  	private $iterator;

  	public function __construct($data = [])
  	{
  		if (null === $data) {
  			$data = [];
  		} elseif (!is_array($data) && !array_key_exists('ArrayAccess', class_implements($data))) {
  			$data = [$data];
  		}
  		$this->iterator  = $this->getIterator();

  		parent::__construct($data);
  	}

  	public function __call($funcName, $args)
  	{
  		$funcName = trim($funcName);
  		if (!array_key_exists($funcName, self::$functionMapping)) {
  			self::$functionMapping[$funcName] = null;
  			$pluginFile                       = __DIR__ . \DIRECTORY_SEPARATOR . 'df_plugins' . \DIRECTORY_SEPARATOR . 'factory.' . $funcName . '.php';
  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$functionMapping[$funcName] = $callback;
  				}
  			}
  		}

  		if (!isset(self::$functionMapping[$funcName])) {
  			new ThrowError('DataFactory', '1001', 'Cannot load [' . $funcName . '] factory function.');
  		}

  		return call_user_func_array(self::$functionMapping[$funcName]->bindTo($this), $args);
  	}

  	public function __invoke(string $index)
  	{
  		$index = trim($index);
  		if (!$index) {
  			new ThrowError('DataFactory', '2001', 'The invoke index cannot be empty.');
  		}

  		return new DataConvertor($this, $index);
  	}

  	public static function DisableWarning()
  	{
  		self::$preventWarning = true;
  	}

  	public static function EnableWarning()
  	{
  		self::$preventWarning = false;
  	}

  	public function &offsetGet($index)
  	{
  		// Prevent display undefine warning
  		if (!self::$preventWarning || $this->offsetExists($index)) {
  			return $this->iterator[$index];
  		}

  		return null;
  	}
  }
}
