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
  abstract class IController
  {
  	public $moduleLoaded      = false;
  	protected $declaredClass  = '';
  	protected $methodList     = [];
  	protected $load;

  	protected $module;
  	protected $manager;
  	protected $reflection;

  	final public function __construct(ModulePackage $module)
  	{
  		$this->reflection    = new \ReflectionClass($this);
  		$this->declaredClass = $this->reflection->getShortName();
  		$this->manager       = ModuleManager::GetInstance();
  		$this->module        = $module;

  		// Load Preload Event
  		$this->moduleLoaded = ($this->__onModuleLoaded()) ? true : false;

  		// Setup Loader
  		$this->load          = new Loader($module);
  	}

  	private function __methodExists($methodName)
  	{
  		if (!isset($this->methodList[$methodName])) {
  			// Search method is exists in method list or not
  			// Load method file if it is exists <Filename Pattern: classname.method>
  			$controllerPath = $this->module->getModuleRoot() . 'controller' . \DIRECTORY_SEPARATOR . $this->declaredClass . '.' . $methodName . '.php';
  			if (file_exists($controllerPath)) {
  				try {
  					$closure = require $controllerPath;
  					if (!is_callable($closure)) {
  						new ThrowError('IController', '2001', 'The object was not a function');
  					}
  					$this->methodList[$methodName] = $closure;
  				} catch (Exception $e) {
  					// Error: The object was not callable
  					new ThrowError('IController', '2002', 'Cannot load method file, maybe the method file was corrupted');
  				}
  			} else {
  				return false;
  			}
  		}

  		return true;
  	}

  	final public function __call($method, $arguments)
  	{
  		if (!$this->__methodExists($method)) {
  			// Error: ControllerClosure not found
  			new ThrowError('IController', '3001', '[' . $method . '] ControllerClosure not found');
  		}
  		$closure = $this->methodList[$method];

  		$closure->bindTo($this, get_class($this));

  		return call_user_func_array($closure, $arguments);
  	}

  	protected function __onModuleLoaded()
  	{
  		// true:    Module is loaded
  		// false:   Module false to loaded
  		return true;
  	}

  	public function __onReady()
  	{
  		// true:    Module is ready
  		// false:   Module is not ready and unloaded
  		return true;
  	}

  	public function __onPrepareRouting()
  	{
  		// true:    Module is routable
  		// false:   Module is not routable
  		return true;
  	}

  	public function __onBeforeRoute()
  	{
  		return true;
  	}
  }
}
