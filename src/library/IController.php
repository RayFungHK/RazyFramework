<?php

/*
 * This file is part of RazyFramwork.
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
  	protected $declaredClass = '';
  	protected $module;
  	protected $manager;
  	protected $methodList = [];
  	protected $reflection;

  	final public function __construct(ModulePackage $module)
  	{
  		$this->reflection    = new \ReflectionClass($this);
  		$this->declaredClass = $this->reflection->getShortName();
  		$this->manager       = ModuleManager::GetInstance();
  		$this->module        = $module;

      // Load Preload Event
      $this->__onModuleLoaded();
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
      return $this;
    }

    public function __onReady()
    {
      return $this;
    }

  	final public function getReflection()
  	{
  		return $this->reflection;
  	}

  	final protected function getViewPath($rootview = false)
  	{
  		return ($rootview) ? SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'view' . \DIRECTORY_SEPARATOR : $this->module->getModuleRoot() . 'view' . \DIRECTORY_SEPARATOR;
  	}

  	final protected function getViewURL($rootview = false)
  	{
  		return ($rootview) ? URL_BASE . \DIRECTORY_SEPARATOR . 'view' . \DIRECTORY_SEPARATOR : URL_BASE . $this->module->getModuleRoot(true) . 'view' . \DIRECTORY_SEPARATOR;
  	}

  	final protected function loadview($filepath, $rootview = false)
  	{
  		// If there is no extension provided, default as .tpl
  		if (!preg_match('/\.[a-z]+$/i', $filepath)) {
  			$filepath .= '.tpl';
  		}

  		$root       = $this->getViewPath($rootview);
  		$tplManager = new TemplateManager($root . $filepath, $this->module->getCode());
  		$tplManager->globalAssign([
  			'view_path' => $root,
  		]);
  		$tplManager->addToQueue();

  		return $tplManager;
  	}
  }
}
