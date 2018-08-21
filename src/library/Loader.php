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
  class Loader
  {
  	private static $loaders    = [];

  	private $module;
  	private $cached = [];

  	public function __construct(ModulePackage $module)
  	{
  		$this->module = $module;
  	}

  	public function __call(string $method, array $arguments)
  	{
  		if (!isset(self::$loaders[$method])) {
  			// Display feter error if the loder is not found
  			trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
  		}

  		// Cache the bound callback
  		if (!isset($this->cached[$method])) {
  			$this->cached[$method] = \Closure::bind(self::$loaders[$method], $this->module);
  		}

  		return call_user_func_array($this->cached[$method], $arguments);
  	}

  	public static function CreateMethod(string $name, callable $callback)
  	{
  		$manager = ModuleManager::GetInstance();
  		if (ModuleManager::STATUS_PRELOAD_STAGE !== $manager->getStage()) {
  			new ThrowError('1001', 'Loader', 'You can only allow creating loader method in preload stage.');
  		}

  		$name = trim($name);
  		if (!preg_match('/^(?![0-9]+)[\w]+$/', $name)) {
  			new ThrowError('1002', 'Loader', 'Invalid loader name, it allows a-z, A-Z and _ (underscore) only, also the name cannot start from digit.');
  		}

  		self::$loaders[$name] = $callback;
  	}

  	public static function GetLoaders(ModulePackage $module)
  	{
  		if (!isset(self::$cached[$module->getCode()])) {
  			$loaders = [];
  			foreach (self::$loaders as $name => $loader) {
  				$loaders[$name] = $loader->getLoader($module);
  			}
  			self::$cached[$module->getCode()] = (object) $loaders;
  		}

  		return self::$cached[$module->getCode()];
  	}
  }
}
