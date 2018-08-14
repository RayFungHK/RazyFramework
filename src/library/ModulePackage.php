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
  class ModulePackage
  {
  	const MODULE_STATUS_UNLOADED = -1;
  	const MODULE_STATUS_PENDING  = 0;
  	const MODULE_STATUS_LOADED   = 1;
  	const MODULE_STATUS_READY    = 2;

  	private $moduleRoot        = '';
  	private $moduleCode        = '';
  	private $author            = '';
  	private $version           = '';
  	private $remapPath         = '';
  	private $routeName         = '';
  	private $routeMapping      = [];
  	private $callableList      = [];
  	private $controllerList    = [];
  	private $coreController    = [];
  	private $eventListner      = [];
  	private $additionalSetting = [];
  	private $preloadStatus     = self::MODULE_STATUS_PENDING;

  	public function __construct(string $modulePath, array $settings)
  	{
  		$this->moduleRoot = $modulePath;

  		if (array_key_exists('module_code', $settings)) {
  			$this->moduleCode = trim($settings['module_code']);
  			unset($settings['module_code']);
  		}

  		if (!$this->moduleCode) {
  			new ThrowError('ModulePackage', '3001', 'Module code is required.');
  		}

  		if (array_key_exists('author', $settings)) {
  			$this->authur = trim($settings['author']);
  			unset($settings['author']);
  		}

  		if (array_key_exists('version', $settings)) {
  			$this->version = trim($settings['version']);
  			unset($settings['version']);
  		}

  		// Add callable method into whitelist
  		if (array_key_exists('callable', $settings)) {
  			if (is_array($settings['callable'])) {
  				foreach ($settings['callable'] as $commandName => $namespace) {
  					$commandName = trim($commandName);
  					if (!$commandName || !$this->isValidNamespace($namespace)) {
  						new ThrowError('ModulePackage', '3002', 'Cannot add ' . $method . ' to whitelist.');
  					}
  					$this->callableList[$commandName] = $namespace;
  				}
  			}
  			unset($settings['callable']);
  		}

  		// Add event listener
  		if (array_key_exists('event', $settings)) {
  			if (is_array($settings['event'])) {
  				foreach ($settings['event'] as $eventName => $namespace) {
  					$eventName = trim($eventName);
  					if (!$eventName || !$this->isValidNamespace($namespace)) {
  						new ThrowError('ModulePackage', '3003', 'Cannot add ' . $method . ' event listener.');
  					}
  					$this->eventListner[$eventName] = $namespace;
  				}
  			}
  			unset($settings['event']);
  		}

  		if (array_key_exists('remap', $settings)) {
  			if (trim($settings['remap'])) {
  				$this->remapPath = preg_replace('/[\/\\\\]+/', '/', '/' . $settings['remap'] . '/');
  				// Replace $1 as module code
  				$this->remapPath = str_replace('$1', $this->moduleCode, $this->remapPath);
  			}
  			unset($settings['remap']);
  		} else {
  			$this->remapPath = '/' . $this->moduleCode . '/';
  		}

  		if (array_key_exists('route', $settings)) {
  			if (is_array($settings['route'])) {
  				foreach ($settings['route'] as $routeName => $namespace) {
  					$routeName = trim($routeName);
  					if (!$routeName || !$this->isValidNamespace($namespace)) {
  						new ThrowError('ModulePackage', '3004', 'Invalid route\'s class mapping format');
  					}
  					$this->routeMapping[$routeName] = $namespace;
  				}

  				// If the remap parameter is not set, set the remap path by module code
  				if (!$this->remapPath) {
  					$this->remapPath = '/' . $this->moduleCode . '/';
  				}
  			}
  			unset($settings['remap']);
  		}

  		$this->additionalSetting = $settings;

  		// Preload module core controller
  		$this->coreController  = $this->getController($this->moduleCode);
  		$this->preloadStatus   = ($this->coreController->isLoaded()) ? self::MODULE_STATUS_LOADED : self::MODULE_STATUS_UNLOADED;
  	}

  	public function ready()
  	{
  		if (self::MODULE_STATUS_LOADED === $this->preloadStatus) {
  			if ($this->coreController->__onReady()) {
  				$this->preloadStatus = self::MODULE_STATUS_READY;
  			} else {
  				$this->preloadStatus = self::MODULE_STATUS_UNLOADED;
  			}
  		}

  		return $this->preloadStatus;
  	}

    public function getPreloadStatus()
    {
      return $this->preloadStatus;
    }

  	public function getSetting(string $variable)
  	{
  		return (isset($this->additionalSetting[$variable])) ? $this->additionalSetting[$variable] : null;
  	}

  	public function getCode()
  	{
  		return $this->moduleCode;
  	}

  	public function getModuleRoot($relativePath = false)
  	{
  		if ($relativePath) {
  			return preg_replace('/^' . preg_quote(SYSTEM_ROOT, '/') . '/', '', $this->moduleRoot);
  		}

  		return $this->moduleRoot;
  	}

  	public function getRemapPath()
  	{
  		return $this->remapPath;
  	}

  	public function route($args)
  	{
  		if ($this->preloadStatus <= 1) {
  			new ThrowError('ModulePackage', '2003', 'System is not ready, you cannot route in preload stage.');
  		}

  		$moduleController = null;
  		$routeName        = (count($args)) ? $args[0] : '(:any)';

  		// If method route mapping matched, return the contoller
  		if (isset($this->routeMapping[$routeName])) {
  			$method                   = array_shift($args);
  			list($className, $method) = explode('.', $this->routeMapping[$routeName]);
  			$moduleController         = $this->getController($className);
  		} else {
  			$routeName = '(:any)';
  			// If no method route matched, re-route all argument to (:any).
  			if (isset($this->routeMapping['(:any)'])) {
  				list($className, $method) = explode('.', $this->routeMapping['(:any)']);
  				$moduleController         = $this->getController($className);
  			}

  			if (!$moduleController) {
  				// No (:any) route exists, return 404 not found
  				return false;
  			}
  		}

  		$methodExists = false;
  		// Check the methed is callable or not, protected and private method is not executeable
  		if (method_exists($moduleController, $method)) {
  			// Method Reflection, get the method type
  			$reflection = new \ReflectionMethod($moduleController, $method);
  			if (!$reflection->isPublic()) {
  				// Error: Controller function not callable
  				new ThrowError('ModulePackage', '2002', 'Cannot execute the method, maybe it is not a public method');
  			}
  		}

  		// Set the matched mapping name as route name
  		$this->routeName = $routeName;

  		// Pass all arguments to routed method
  		$result = call_user_func_array([$moduleController, $method], $args);
  		if (false === $result) {
  			return false;
  		}

  		return true;
  	}

  	public function getRouteName()
  	{
  		return $this->routeName;
  	}

  	public function trigger($mapping, $args)
  	{
  		if ($this->preloadStatus <= 1) {
  			new ThrowError('ModulePackage', '4006', 'System is not ready, you cannot trigger in preload stage.');
  		}

  		if (isset($this->eventListner[$mapping])) {
  			list($className, $method) = explode('.', $this->eventListner[$mapping]);

  			if (!($moduleController = $this->getController($className))) {
  				// Error: Controller Not Found
  				new ThrowError('ModulePackage', '4004', 'Controller Not Found');
  			}

  			// Check the methed is callable or not, protected and private method is not executable
  			if (method_exists($moduleController, $method)) {
  				// Method Reflection, get the method type
  				$reflection = new \ReflectionMethod($moduleController, $method);
  				if (!$reflection->isPublic()) {
  					// Error: Controller function not callable
  					new ThrowError('ModulePackage', '4005', 'Cannot trigger the event, maybe it is not a public method');
  				}
  			}

  			call_user_func_array([$moduleController, $method], $args);
  		}

  		return $this;
  	}

  	public function execute($mapping, $args)
  	{
  		if ($this->preloadStatus <= 1) {
  			new ThrowError('ModulePackage', '4007', 'System is not ready, you cannot execute in preload stage.');
  		}

  		if (isset($this->callableList[$mapping])) {
  			list($className, $method) = explode('.', $this->callableList[$mapping]);

  			if (!($moduleController = $this->getController($className))) {
  				// Error: Controller Not Found
  				new ThrowError('ModulePackage', '4002', 'Controller Not Found');
  			}

  			// Check the methed is callable or not, protected and private method is not executable
  			if (method_exists($moduleController, $method)) {
  				// Method Reflection, get the method type
  				$reflection = new \ReflectionMethod($moduleController, $method);
  				if (!$reflection->isPublic()) {
  					// Error: Controller function not callable
  					new ThrowError('ModulePackage', '4003', 'Cannot execute the method, maybe it is not a public method');
  				}
  			}

  			// Pass all arguments to routed method
  			return call_user_func_array([$moduleController, $method], $args);
  		}

  		return null;
  	}

  	private function isValidNamespace($namespace)
  	{
  		$namespace = trim($namespace);
  		if ($namespace && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+\.[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/', $namespace)) {
  			return true;
  		}

  		return false;
  	}

  	private function getController($className)
  	{
  		// Search defined controller from list
  		if (isset($this->controllerList[$className])) {
  			return $this->controllerList[$className];
  		}
  		$controllerPath = $this->moduleRoot . 'controller' . \DIRECTORY_SEPARATOR;

  		// Check the class file is exists or not
  		if (file_exists($controllerPath . $className . '.php')) {
  			// Load the class file, all module controller class MUST under 'Module' namespace
  			include $controllerPath . $className . '.php';

  			// Get the lasy declared class name, assume one file contain one class
  			$declaredClass = get_declared_classes();
  			$declaredClass = end($declaredClass);

  			// Get the class name without namespace
  			$_className = explode('\\', $declaredClass);
  			$_className = end($_className);

  			if ($_className !== $className) {
  				new ThrowError('ModulePackage', '1003', 'Controller\'s class ' . $className . ' not found, or the declared class name not match as the file name.');
  			}

  			if (class_exists($declaredClass)) {
  				// Create controller object and put into controller list
  				$this->controllerList[$className] = new $declaredClass($this);

  				// Check the controller class has inherit IController class or not
  				if (!is_subclass_of($this->controllerList[$className], 'RazyFramework\\IController')) {
  					// Error: Controller's class should inherit IController
  					new ThrowError('ModulePackage', '1002', 'Controller\'s class should inherit IController');
  				}

  				return $this->controllerList[$className];
  			}
  			// Error: Controller's class not found
  			new ThrowError('ModulePackage', '1001', 'Controller\'s class not exists');
  		}

  		return null;
  	}
  }
}
