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
  class DataConvertor
  {
  	protected $value;
  	protected $dataType;
  	protected $chainable;
  	private $dataFactory;
  	private $index                         = '';
  	private static $functions              = [];
  	private static $dynamicFunctions       = [];

  	public function __construct(DataFactory $df, $index)
  	{
  		$this->dataFactory = $df;
  		$this->index       = $index;
  		$value             = $df[$index] ?? null;
  		$this->value       = $value;
  		$this->dataType    = strtolower(gettype($value));
  		$this->chainable   = false;
  	}

  	public function __call($funcName, $args)
  	{
  		$funcName        = trim($funcName);
  		$closureFunction = null;

  		if (!array_key_exists($funcName, self::$functions)) {
  			self::$functions[$funcName] = null;

  			$pluginFile  = __DIR__ . \DIRECTORY_SEPARATOR . 'df_plugins' . \DIRECTORY_SEPARATOR . 'conv.' . $funcName . '.php';
  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$functions[$funcName] = $callback;
  					$closureFunction            = $callback;
  				}
  			}
  		} else {
  			$closureFunction = self::$functions[$funcName];
  		}

  		if (!$closureFunction) {
  			if (isset(self::$dynamicFunctions[$funcName])) {
  				$closureFunction = self::$dynamicFunctions[$funcName];
  			} else {
  				new ThrowError('Cannot load [' . $funcName . '] convertor function.');
  			}
  		}

  		// Bind convertor object to closure function
  		$result = call_user_func_array($closureFunction->bindTo($this, __CLASS__), $args);

  		$this->dataFactory[$this->index] = $this->value;

  		// Obtain the chainable value and reset the setting
  		$chainable       = $this->chainable;
  		$this->chainable = false;

  		return (!$chainable) ? $result : $this;
  	}

    public function getValue()
    {
      return $this->dataFactory[$this->index];
    }

  	public static function CreateConvertor(string $name, callable $callback)
  	{
  		$name = trim($name);
  		if (preg_match('/^[\w-]+$/', $name)) {
  			if (!isset(self::$filters[$name])) {
  				self::$dynamicFunctions[$name] = null;
  			}

  			if (is_callable($callback)) {
  				self::$dynamicFunctions[$name] = $callback;
  			}
  		}
  	}
  }
}
