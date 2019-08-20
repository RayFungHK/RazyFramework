<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Modular
{
	use RazyFramework\ErrorHandler;
	use RazyFramework\Wrapper;

	/**
	 * The module package. Used for event trigger, API and routing, also you can pack your module into a .rfmp file via Console mode.
	 */
	class Package
	{
		use \RazyFramework\Injector;

		/**
		 * Module pending status, ready to load the controller.
		 *
		 * @var int
		 */
		const STATUS_PENDING = 0;

		/**
		 * Module initializing status
		 *
		 * @var int
		 */
		const STATUS_INITIALIZING = 1;

		/**
		 * Module Unloaded status, fail to initialize in onInit stage.
		 *
		 * @var int
		 */
		const STATUS_UNLOADED = 2;

		/**
		 * Module loaded status, routing, API and event available.
		 *
		 * @var int
		 */
		const STATUS_LOADED = 3;

		/**
		 * Module disabled status, it still can communicate with other modules. In this status it can not be routed, the event cannot be trggered and the API is not available.
		 *
		 * @var int
		 */
		const STATUS_DISABLED = 4;

		/**
		 * Module done status. In this status it can be routed, the event can be trggered and the API is available.
		 *
		 * @var int
		 */
		const STATUS_ACTIVE = 5;

		/**
		 * The module code, it is unique in distribution.
		 *
		 * @var string
		 */
		private $code = '';

		/**
		 * The module version.
		 *
		 * @var string
		 */
		private $version = '';

		/**
		 * The module author.
		 *
		 * @var string
		 */
		private $author = '';

		/**
		 * The module package path.
		 *
		 * @var string
		 */
		private $modulePath;

		/**
		 * The module package controller.
		 *
		 * @var Controller
		 */
		private $controller;

		/**
		 * The routing routing prefix, it can be overrided by remap parameter.
		 *
		 * @var string
		 */
		private $routingPrefix = '';

		/**
		 * An array contains routing setting.
		 *
		 * @var array
		 */
		private $routes = [];

		/**
		 * An array contains the registered API method.
		 *
		 * @var array
		 */
		private $api = [];

		/**
		 * An array contains the registered Event method.
		 *
		 * @var array
		 */
		private $events = [];

		/**
		 * The module manager.
		 *
		 * @var Manager
		 */
		private $manager;

		/**
		 * The package load status.
		 *
		 * @var int
		 */
		private $status = 0;

		/**
		 * An array contains the module code and its requireversion.
		 *
		 * @var array
		 */
		private $require = [];

		/**
		 * An array contains the module package property.
		 *
		 * @var array
		 */
		private $property = [];

		/**
		 * The module root url.
		 *
		 * @var string
		 */
		private $moduleRootURL = '';

		/**
		 * The routed path.
		 *
		 * @var string
		 */
		private $routedPath = '';

		/**
		 * If true, it means the package is routed.
		 *
		 * @var bool
		 */
		private $routed = false;

		/**
		 * An Wrapper object.
		 *
		 * @var Wrapper
		 */
		private $wrapper;

		/**
		 * Module package constructor.
		 *
		 * @param Manager $manager    The module manager
		 * @param string  $modulePath The path of the module package
		 * @param array   $setting    An array contains package setting
		 * @param Wrapper $wrapper    An Wrapper object to communicate with Manager
		 */
		public function __construct(Manager $manager, string $modulePath, array $setting, Wrapper $wrapper)
		{
			$this->manager    = $manager;
			$this->modulePath = $modulePath;
			if (0 === strpos($modulePath, SYSTEM_ROOT)) {
				$this->moduleRelativePath = substr($modulePath, strlen(SYSTEM_ROOT));
			}

			if (isset($setting['module_code'])) {
				$code = trim($setting['module_code']);
				if (!is_string($code)) {
					throw new ErrorHandler('The module code should be a string');
				}

				if (!preg_match('/^\w+$/', $code)) {
					throw new ErrorHandler('The module code ' . $code . ' is not a correct format.');
				}

				$this->code = $code;
			} else {
				throw new ErrorHandler('Missing module code.');
			}

			$this->wrapper    = $wrapper;
			$this->wrapper->preset($this)->exchange($this->code, $this->wrapper(['execute', 'trigger', 'route', 'prepare', 'touch', 'ready', 'rewrite', 'notify', 'standby']));

			$this->version = trim($setting['version']);
			if (!$this->version) {
				throw new ErrorHandler('Missing module version.');
			}

			$this->author = trim($setting['author']);
			if (!$this->author) {
				throw new ErrorHandler('Missing module author.');
			}

			if (isset($setting['require']) && is_array($setting['require'])) {
				foreach ($setting['require'] as $moduleCode => $version) {
					$moduleCode = trim($moduleCode);
					if (preg_match('/^[\w-]+$/', $moduleCode) && is_string($version)) {
						$this->require[$moduleCode] = trim($version);
					}
				}
			}
		}

		/**
		 * Get the require list.
		 *
		 * @return array An array contains the require package
		 */
		public function getRequire()
		{
			return $this->require;
		}

		/**
		 * Initialize the module package and bind the controller.
		 *
		 * @return bool Return true if the module package has initialized success
		 */
		public function initialize()
		{
			if (!$this->controller) {
				// Load the controller
				$controllerPath = append($this->modulePath, 'controller', $this->code . '.php');
				if (is_file($controllerPath)) {
					$this->status = self::STATUS_INITIALIZING;

					// Load the class file, all module controller class MUST under 'Module' namespace
					include $controllerPath;

					// Get the lasy declared class name, assume one file contain one class
					$declaredClass = get_declared_classes();
					$controller    = end($declaredClass);

					if (class_exists($controller)) {
						// Create controller instance, the routing, event and API will be configured in controller
						$this->controller = new $controller($this);
						// Ensure the controller is inheritd by Controller class
						if (!$this->controller instanceof Controller) {
							throw new ErrorHandler('The controller must instance of Controller');
						}

						$this->status = ($this->controller->__onInit()) ? self::STATUS_LOADED : self::STATUS_UNLOADED;

						return true;
					}

					throw new ErrorHandler('The class ' . $declaredClass . ' does not declared.');
				}

				throw new ErrorHandler('The controller ' . $controllerPath . ' does not exists.');
			}

			return true;
		}

		/**
		 * If the current package is diffreent with previous version, it will exceute the controller onVersionControl method.
		 *
		 * @param string $previousVersion The previous package version
		 *
		 * @return bool Return true to update the package version
		 */
		public function checkUpdate(string $previousVersion)
		{
			if (self::STATUS_ACTIVE === $this->status && $previousVersion !== $this->version) {
				return $this->controller->__onVersionControl($previousVersion);
			}

			return false;
		}

		/**
		 * Set lock signel to Manager to lock the package.
		 *
		 * @return self Chainable
		 */
		public function lock()
		{
			$this->wrapper->lock();

			return $this;
		}

		/**
		 * Update the package version in the sites package version file.
		 *
		 * @return self Chainable
		 */
		public function update()
		{
			$this->wrapper->updateVersion();

			return $this;
		}

		/**
		 * Redirect to specified routing path.
		 *
		 * @param string $path The route path
		 *
		 * @return self Chainable
		 */
		public function move(string $path)
		{
			return $this->wrapper->routeTo($path);
		}

		/**
		 * Check the current URL Query is able to route into this module package.
		 *
		 * @param string $route The test route path
		 *
		 * @return bool Return true if the route is matched
		 */
		public function routable(string $route)
		{
			$route    = tidy('/' . $route, true, '/');
			$urlQuery = $this->manager->getURLQuery();
			if (0 === ($pos = strpos($urlQuery, $this->routingPrefix))) {
				$urlQuery = substr($urlQuery, strlen($this->routingPrefix) - 1);
				if (0 === ($pos = strpos($urlQuery, $route))) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Save the config to the sites config file.
		 *
		 * @param array $config An array contains the package configuration
		 *
		 * @return self Chainable
		 */
		public function saveConfig(array $config)
		{
			$this->wrapper->saveConfig($config);

			return $this;
		}

		/**
		 * Get the config from the sites config file.
		 *
		 * @return array An array contains the package configuration
		 */
		public function getConfig()
		{
			return $this->wrapper->getConfig();
		}

		/**
		 * Add module package routing.
		 *
		 * @param array|string $route  A string of the route path or an array contains a set of routing
		 * @param string       $method The method will be called in controller if the route is match
		 *
		 * @return self Chainable
		 */
		public function addRoute($route, string $method = '')
		{
			if (is_array($route)) {
				foreach ($route as $routeName => $method) {
					$this->addRoute($routeName, $method);
				}
			} else {
				$route                = tidy('/' . $route, true, '/');
				$this->routes[$route] = $method;
			}

			return $this;
		}

		/**
		 * Add property to module package.
		 *
		 * @param array|string $property The name of the property or an array contains a set of property
		 * @param string       $value    The property value
		 *
		 * @return self Chainable
		 */
		public function addProperty($property, string $value = '')
		{
			if (is_array($property)) {
				foreach ($property as $name => $value) {
					$this->addProperty($name, $value);
				}
			} else {
				$property = trim($property);
				if ($property) {
					$this->property[$property] = $value;
				}
			}

			return $this;
		}

		/**
		 * Get the properyu value by given name.
		 *
		 * @param string $name The name of the property
		 *
		 * @return mixed The value of the property
		 */
		public function getProperty(string $name)
		{
			$name = trim($name);

			return $this->property[$name] ?? null;
		}

		/**
		 * Add module package API method.
		 *
		 * @param array|string $api    The name of the API or an array contains a set of API method
		 * @param string       $method The method will be called in controller via API
		 *
		 * @return self Chainable
		 */
		public function addAPI($api, string $method = '')
		{
			if (is_array($api)) {
				foreach ($api as $name => $method) {
					$this->addAPI($name, $method);
				}
			} else {
				$api = trim($api);
				if (preg_match('/^\w+$/', $api) && preg_match('/^\w+$/i', $method)) {
					$api             = trim($api);
					$this->api[$api] = $method;
				}
			}

			return $this;
		}

		/**
		 * Add module package Event method.
		 *
		 * @param array|string $event  The name of the event or an array contains a set of event method
		 * @param string       $method The method will be called via event trigger
		 *
		 * @return self Chainable
		 */
		public function listen($event, string $method = '')
		{
			if (is_array($event)) {
				foreach ($event as $name => $method) {
					$this->listen($name, $method);
				}
			} else {
				$event = trim($event);
				if (preg_match('/^\w+$/', $event) && preg_match('/^\w+$/i', $method)) {
					$event                = trim($event);
					$this->events[$event] = $method;
				}
			}

			return $this;
		}

		/**
		 * Load the library from the module folder ./library/.
		 *
		 * @param string $class The class name
		 *
		 * @return bool Return true if the library is loaded
		 */
		public function loadLibrary(string $class)
		{
			$classes     = str_replace('\\', \DIRECTORY_SEPARATOR, $class);
			$libraryPath = append($this->getRootPath(), 'library', $classes) . '.php';

			if (is_file($libraryPath)) {
				include $libraryPath;

				return class_exists($class);
			}

			return false;
		}

		/**
		 * Check if the specific event is listening.
		 *
		 * @param string $event [description]
		 *
		 * @return bool [description]
		 */
		public function hasEvent(string $event)
		{
			return isset($this->events[$event]);
		}

		/**
		 * Set the module package entry path for routing. It only been used before the system routing.
		 *
		 * @param string $path [description]
		 *
		 * @return self Chainable
		 */
		public function setRoutingPrefix(string $path)
		{
			$this->routingPrefix = tidy('/' . $path, true, '/');

			return $this;
		}

		/**
		 * Get the routing prefix.
		 *
		 * @return string The path of routing
		 */
		public function getRoutingPrefix()
		{
			return $this->routingPrefix;
		}

		/**
		 * Get the package code.
		 *
		 * @return string The module code
		 */
		public function getCode()
		{
			return $this->code;
		}

		/**
		 * Get the package version.
		 *
		 * @var string
		 */
		public function getVersion()
		{
			return $this->version;
		}

		/**
		 * Get the package author.
		 *
		 * @var string
		 */
		public function getAuthor()
		{
			return $this->author;
		}

		/**
		 * Get the package load status.
		 *
		 * @return int The load status
		 */
		public function getStatus()
		{
			return $this->status;
		}

		/**
		 * Get the module manager.
		 *
		 * @return Manager The module manager
		 */
		public function getManager()
		{
			return $this->manager;
		}

		/**
		 * Get the module controller.
		 *
		 * @return Controller The package controller
		 */
		public function getController()
		{
			return $this->controller;
		}

		/**
		 * Get the module root file path.
		 *
		 * @return string The file path
		 */
		public function getRootPath()
		{
			return $this->modulePath;
		}

		/**
		 * Get the module root url.
		 *
		 * @return string The root url
		 */
		public function getRootURL()
		{
			return append($this->manager->getSiteURL(), $this->routingPrefix);
		}

		/**
		 * Get the module view system path.
		 *
		 * @return string The module view system path
		 */
		public function getViewPath()
		{
			return append($this->modulePath, 'view');
		}

		/**
		 * Get the module view URL.
		 *
		 * @return string The module view URL
		 */
		public function getViewURL()
		{
			return append($this->manager->getRootURL(), RELATIVE_ROOT, $this->moduleRelativePath, 'view') . '/';
		}

		/**
		 * Get the routed path after the module has routed to.
		 *
		 * @return string The routed path
		 */
		public function getRoutedPath()
		{
			return $this->routedPath;
		}

		/**
		 * Get the Template Manager.
		 *
		 * @return \RazyFramework\Template\Manager The Template Manager object
		 */
		public function getTplManager()
		{
			return $this->wrapper->getTplManager();
		}

		/**
		 * Execute the registered API method.
		 *
		 * @param string $name  The API name
		 * @param array  $args  The argument of the API
		 * @param array  $trace The caller stack
		 *
		 * @return mixed The result returned by the API method
		 */
		private function execute(string $name, array $args, array $trace = [])
		{
			if (self::STATUS_ACTIVE !== $this->status) {
				throw new ErrorHandler('The package is not active, you cannot execute the method via API.');
			}

			if (isset($this->api[$name])) {
				$method = $this->api[$name];

				// Check the methed is callable or not, protected and private method is not executable
				if (method_exists($this->controller, $method)) {
					// Method Reflection, get the method type
					$reflection = new \ReflectionMethod($this->controller, $method);
					if (!$reflection->isPublic()) {
						// Error: Controller function not callable
						throw new ErrorHandler('Cannot execute the method, maybe it is not a public method');
					}
				}

				if ($this->controller->__onAPICall($trace, $name, $args)) {
					// Pass all arguments to routed method
					return call_user_func_array([$this->controller, $method], $args);
				}
			}

			return null;
		}

		/**
		 * Fire the registered Event method.
		 *
		 * @param string $name  The event name
		 * @param array  &$args The argument of the event
		 * @param array  $trace The caller stack
		 *
		 * @return mixed The result returned by event method
		 */
		private function trigger(string $name, array &$args, array $trace = [])
		{
			$result = null;
			if (self::STATUS_ACTIVE === $this->status) {
				if (isset($this->events[$name])) {
					$method = $this->events[$name];

					// Check the methed is callable or not, protected and private method is not executable
					if (method_exists($this->controller, $method)) {
						// Method Reflection, get the method type
						$reflection = new \ReflectionMethod($this->controller, $method);
						if (!$reflection->isPublic()) {
							// Error: Controller function not callable
							return $result;
						}
					}

					if ($this->controller->__onEventTrigger($trace, $name, $args)) {
						// Pass all arguments to routed method
						$result = call_user_func_array([$this->controller, $method], $args);
					}
				}
			}

			return $result;
		}

		/**
		 * Prepare the package, it will call the controller onPrepare and onVersionControl if necessary.
		 *
		 * @return bool Return true if the module is ready
		 */
		private function prepare()
		{
			if (self::STATUS_LOADED === $this->status) {
				if ($this->controller->__onPrepare()) {
					$this->status = self::STATUS_ACTIVE;

					return true;
				}
			}

			$this->status = self::STATUS_DISABLED;

			return false;
		}

		/**
		 * Tigger the onBeforeRoute in Controller before start routes matching.
		 *
		 * @param string $urlQuery The url query ready to route
		 *
		 * @return bool Return true to start route matching
		 */
		private function touch(string $urlQuery)
		{
			return $this->controller->__onTouch($urlQuery);
		}

		/**
		 * Route and execute the controller method by the givan query url.
		 *
		 * @param string $urlQuery The query url after the routing prefix
		 *
		 * @return bool Return true if the route is matched
		 */
		private function route(string $urlQuery)
		{
			sort_path_level($this->routes);
			foreach ($this->routes as $route => $method) {
				if (0 === ($pos = strpos($urlQuery, $route))) {
					$this->moduleRootURL = append($this->manager->getSiteURL(), $route);
					$urlQuery            = rtrim(substr($urlQuery, strlen($route)), '/');
					$args                = explode('/', $urlQuery);
					$this->routedPath    = $route;

					$this->wrapper->broadcast();

					return call_user_func_array([$this->controller, $method], $args);
				}
			}

			return false;
		}

		/**
		 * Notify all module that the route is done.
		 *
		 * @param string $moduleCode The routed module's code
		 *
		 * @return self chainable
		 */
		private function notify(string $moduleCode)
		{
			$this->controller->__onAfterRoute($moduleCode);

			return $this;
		}

		/**
		 * Rewrite the url query before routing.
		 *
		 * @param string $urlQuery The original URL Query
		 *
		 * @return string The rewritten URL Query
		 */
		private function rewrite(string $urlQuery)
		{
			return $this->controller->__onBeforeRoute($urlQuery);
		}

		/**
		 * Standby until start to routing in
		 *
		 * @return self Chainable
		 */
		private function standby()
		{
			$this->controller->__onRouteStandby();

			return $this;
		}

		/**
		 * Trigger module package onReady.
		 *
		 * @return self Chainable
		 */
		private function ready(string $urlQuery)
		{
			$this->controller->__onReady($urlQuery);

			return $this;
		}
	}
}
