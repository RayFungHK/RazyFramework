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
  class Utility
  {
  	private const SCALEUNIT = ['byte', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'bb'];
  	private static $storage = [];
  	private static $dynamicFunction = [];

  	public static function __callStatic(string $name, array $arguments)
  	{
  		$name = trim($name);
  		if (!$name) {
  			new ThrowError('Utility function name cannot be empty.');
  		}

  		if (!array_key_exists($name, self::$dynamicFunction)) {
  			self::$dynamicFunction[$funcName] = null;
  			$pluginFile = __DIR__ . \DIRECTORY_SEPARATOR . 'utility_plugins' . \DIRECTORY_SEPARATOR . 'utility.' . $funcName . '.php';
  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$dynamicFunction[$name] = $callback;
  				}
  			}
  		}

  		if (!isset(self::$dynamicFunction[$name])) {
  			new ThrowError('Cannot load [' . $name . '] utility function.');
  		}

      self::$storage[$name] = (object) [];

  		return call_user_func_array(self::$dynamicFunction[$name]->bindTo(self::$storage[$name]), $arguments);
  	}

  	public static function ConvertSizeUnit(float $size, int $decimal = 2, bool $upperCase = false, string $separator = '')
  	{
  		$scale     = 0;
  		$decimal   = ($decimal < 1) ? 0 : $decimal;
  		while ($size >= 1024 && isset(self::SCALEUNIT[$scale])) {
  			$size /= 1024;
  			++$scale;
  		}

  		$unit = self::SCALEUNIT[$scale];
  		$size = ($decimal) ? number_format($size, $decimal) : (int) $size;

  		if ($upperCase) {
  			$unit = strtoupper($unit);
  		}

  		return $size . $separator . $unit;
  	}
  }
}
