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
  class ModulePackage
  {
  	const MODULE_STATUS_UNLOADED     = -1;
  	const MODULE_STATUS_PENDING      = 0;
  	const MODULE_STATUS_LOADED       = 1;
  	const MODULE_STATUS_READY        = 2;
  	const MODULE_STATUS_ROUTABLE     = 3;
  	const MODULE_STATUS_NOT_ROUTABLE = 4;

  	private $moduleRoot        = '';
  	private $moduleCode        = '';
  	private $author            = '';
  	private $version           = '';
  	private $remapPath         = '';
  	private $routeName         = '';
  	private $isRouted          = false;
  	private $require           = [];
  	private $routeMapping      = [];
  	private $callableList      = [];
  	private $controllerList    = [];
  	private $coreController    = [];
  	private $eventListner      = [];
  	private $additionalSetting = [];
  	private $preloadStatus     = self::MODULE_STATUS_PENDING;

  	public function __construct(string $modulePath, array $settings)
  	{
  		// Get the module path from SYSTEM_ROOT
  		preg_match('/^' . preg_quote(SYSTEM_ROOT, '/') . '(.+)/', $modulePath, $matches);
  		$this->moduleRoot = $matches[1];

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

  		if (array_key_exists('require', $settings)) {
  			if (is_array($settings['require'])) {
  				foreach ($settings['require'] as $moduleName => $version) {
  					if (!preg_match('/^[\w-]+$/i', $moduleName)) {
  						new ThrowError('ModulePackage', '3005', $moduleName . ' is not a valid module name.');
  					}
  					$this->require[$moduleName] = $version;
  				}
  			}
  			unset($settings['require']);
  		}

  		$this->additionalSetting = $settings;

  		// Preload module core controller
  		$this->coreController  = $this->getController($this->moduleCode);
  		if (!$this->coreController) {
  			new ThrowError('ModulePackage', '3004', $this->moduleCode . ' core controller not declared');
  		}
  		$this->preloadStatus   = ($this->coreController->moduleLoaded) ? self::MODULE_STATUS_LOADED : self::MODULE_STATUS_UNLOADED;
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

  	public function getRequire()
  	{
  		return $this->require;
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

  	public function getVersion()
  	{
  		return $this->version;
  	}

  	public function isRouted()
  	{
  		return $this->isRouted;
  	}

  	public function getModuleRoot()
  	{
  		return SYSTEM_ROOT . $this->moduleRoot;
  	}

  	public function getModuleRootURL()
  	{
  		return URL_BASE . $this->getRemapPath();
  	}

  	public function getViewPath()
  	{
  		return $this->getModuleRoot() . 'view';
  	}

  	public function getViewPathURL()
  	{
  		return URL_BASE . preg_replace('/\\+/', '/', $this->moduleRoot) . 'view';
  	}

  	public function getRemapPath()
  	{
  		return $this->remapPath;
  	}

  	public function prepareRouting()
  	{
  		if (self::MODULE_STATUS_READY === $this->preloadStatus) {
  			if ($this->coreController->__onPrepareRouting()) {
  				$this->preloadStatus = self::MODULE_STATUS_ROUTABLE;
  			} else {
  				$this->preloadStatus = self::MODULE_STATUS_NOT_ROUTABLE;
  			}
  		}

  		return $this;
  	}

  	public function getRoute()
  	{
  		$path = preg_replace('/[\\\\\/]+/', '/', '/' . trim(REQUEST_ROUTE) . '/');
  		if (0 === strpos($path, $this->remapPath)) {
  			// Get the relative path and remove the last slash
  			$argsString = preg_replace('/\/*$/', '', substr($path, strlen($this->remapPath)));

  			// Extract the path into an arguments array
  			$args       = ($argsString) ? explode('/', $argsString) : [];
  			$routeName  = (count($args)) ? $args[0] : '/';

  			// If method route mapping matched, return the contoller
  			if (isset($this->routeMapping[$routeName])) {
  				return $routeName;
  			}

  			$routeName = '(:any)';
  			// If no method route matched, re-route all argument to (:any).
  			if (isset($this->routeMapping['(:any)'])) {
  				return $routeName;
  			}

  			return '';
  		}

  		// Not matches the remap route, return false
  		return false;
  	}

  	public function route($args)
  	{
  		if (self::MODULE_STATUS_ROUTABLE !== $this->preloadStatus) {
  			if (self::MODULE_STATUS_NOT_ROUTABLE === $this->preloadStatus) {
  				new ThrowError('ModulePackage', '4001', 'The module is denied to routing in.');
  			}

  			new ThrowError('ModulePackage', '4002', 'System is not ready, you cannot route in preload stage.');
  		}

  		$moduleController = null;

  		// If there is no $args, find / (root) route
  		$routeName = (count($args)) ? $args[0] : '/';

  		// If method route mapping matched, return the contoller
  		if (isset($this->routeMapping[$routeName])) {
  			$routeName                = (count($args)) ? array_shift($args) : '/';
  			list($className, $method) = explode('.', $this->routeMapping[$routeName]);

  			$moduleController         = $this->getController($className);
  		} else {
  			// If no route matched, try to find (:any) wildcard route
  			$routeName = '(:any)';
  			// If no method route matched, re-route all argument to (:any).
  			if (isset($this->routeMapping['(:any)'])) {
  				list($className, $method) = explode('.', $this->routeMapping['(:any)']);
  				$moduleController         = $this->getController($className);
  			}
  		}

  		if (!$moduleController) {
  			return false;
  		}

  		$methodExists = false;
  		// Check the methed is callable or not, protected and private method is not executeable
  		if (method_exists($moduleController, $method)) {
  			// Method Reflection, get the method type
  			$reflection = new \ReflectionMethod($moduleController, $method);
  			if (!$reflection->isPublic()) {
  				// Error: Controller function not callable
  				new ThrowError('ModulePackage', '4002', 'Cannot execute the method, maybe it is not a public method');
  			}
  		}

  		// Set the matched mapping name as route name
  		$this->routeName = $routeName;

  		$this->isRouted = true;

  		// Trigger __onBeforeRoute event
  		$moduleController->__onBeforeRoute();

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
  		$controllerPath = $this->getModuleRoot() . 'controller' . \DIRECTORY_SEPARATOR;

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
  				if (!is_subclass_of($this->controllerList[$className], 'RazyFramework\IController')) {
  					// Error: Controller's class should inherit IController
  					new ThrowError('ModulePackage', '1002', 'Controller\'s class should inherit IController');
  				}

  				return $this->controllerList[$className];
  			}
  			// Error: Controller's class not found
  			new ThrowError('ModulePackage', '1001', 'Controller\'s class not exists');
  		} else {
  			new ThrowError('ModulePackage', '1004', 'Controller\'s class file not found');
  		}

  		return null;
  	}
  }
}
