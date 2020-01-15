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

	/**
	 * The Controller abstract class, the package must inherit this class in order to access the routing, event and API.
	 */
	abstract class Controller
	{
		use \RazyFramework\Injector;

		/**
		 * The module manager.
		 *
		 * @var Manager
		 */
		protected $manager;

		/**
		 * The module package.
		 *
		 * @var Package
		 */
		protected $package;

		/**
		 * The declared class of controller.
		 *
		 * @var string
		 */
		protected $controllerClass;

		/**
		 * The loader for controller to provide shortcut function.
		 *
		 * @var Loader
		 */
		protected $loader;

		/**
		 * The Template Manager object.
		 *
		 * @var \RazyFramework\Template\Manager
		 */
		protected $tplManager;

		/**
		 * An array contains available method.
		 *
		 * @var array
		 */
		private $methodList = [];

		/**
		 * Controller constructor.
		 *
		 * @param Package $package [description]
		 */
		final public function __construct(Package $package)
		{
			$reflection            = new \ReflectionClass($this);
			$this->controllerClass = $reflection->getShortName();

			if ($package->getCode() !== $this->controllerClass) {
				throw new ErrorHandler('Class ' . $this->controllerClass . ' is not the contoller of the package ' . $package->getCode() . '.');
			}

			$this->package = $package;
			$this->manager = $package->getManager();
			$this->package->setRoutingPrefix($package->getCode());
			$this->loader     = new Loader($this->package);
			$this->tplManager = $this->package->getTplManager();
		}

		/**
		 * Initializ event, you can setup the routing, event and API in module package initialize stage.
		 *
		 * @return bool Return true if the module is initialized
		 */
		public function __onInit()
		{
			return true;
		}

		/**
		 * It will be triggered if the module routing prefix is matched.
		 *
		 * @param string $urlQuery The url query ready to route
		 *
		 * @return bool Return true to start route matching
		 */
		public function __onTouch(string $urlQuery)
		{
			return true;
		}

		/**
		 * It will be triggered if the module is matched the routing pattern, but before execute the routed closure function.
		 *
		 * @param array $args The arguments will be passed to routed closure function
		 *
		 * @return array Return the modified arguments
		 */
		public function __onBeforeRoute(array $args)
		{
			return $args;
		}

		/**
		 * It will be triggered and pass the returned result from the routed closure function as a argument when the routed closure function is executed.
		 *
		 * @param mixed $result The returned result from routed closure function
		 *
		 * @return bool Return false to trigger the 404 error page, or return true to response 200 status
		 */
		public function __onAfterRoute(bool $result)
		{
			return $result;
		}

		/**
		 * After module manager routing to matched package by routing prefix, this method will be triggered to rewrite the given URL query.
		 *
		 * @param string $urlQuery The original URL Query
		 *
		 * @return string The rewritten URL Query
		 */
		public function __onRewrite(string $urlQuery)
		{
			return $urlQuery;
		}

		/**
		 * It will be triggered before doing routing.
		 *
		 * @return self Chainable
		 */
		public function __onRouteStandby()
		{
			return $this;
		}

		/**
		 * In this stage, all required modules are loaded and you can disable you module if necessary.
		 *
		 * @return bool Return false to disable the module
		 */
		public function __onPrepare()
		{
			return true;
		}

		/**
		 * It will be triggered if the version is different with the current version.
		 *
		 * @param string $version The current version
		 *
		 * @return bool Return true to save the new version
		 */
		public function __onVersionControl(string $version)
		{
			return true;
		}

		/**
		 * It will be triggered before execute the API method.
		 *
		 * @param array  $trace  An array contains the package module calling chain
		 * @param string $method The name of the API method
		 * @param array  &$args  An array contains the arguments
		 *
		 * @return bool Return true to allow execute the API method
		 */
		public function __onAPICall(array $trace, string $method, array &$args)
		{
			return true;
		}

		/**
		 * It will be triggered before the event has been triggered.
		 *
		 * @param array  $trace  An array contains the package module calling chain
		 * @param string $method The name of the API method
		 * @param array  &$args  An array contains the arguments
		 *
		 * @return bool Return false to prevent trigger the event
		 */
		public function __onEventTrigger(array $trace, string $method, array &$args)
		{
			return true;
		}

		public function __onBeforeSendAPI(array $trace, string $method, array &$args)
		{
		 return true;
		}

		public function __onAfterSendAPI(array $trace, string $method, array $result)
		{
		 return true;
		}

		/**
		 * It will be triggered if all module has prepared.
		 *
		 * @return self Chainable
		 */
		public function __onReady(string $urlQuery)
		{
			return $this;
		}

		/**
		 * It will be triggered if some other module has routed.
		 *
		 * @param string $moduleCode The route module's code
		 *
		 * @return self Chainable
		 */
		public function __onNotify(string $moduleCode)
		{
			return $this;
		}

		/**
		 * All controller method is extendable, you can create a new closure file and it will auto load if the method is called.
		 *
		 * @param string $method    The method name
		 * @param array  $arguments The arguments
		 *
		 * @return mixed The result returned by closure
		 */
		final public function __call(string $method, array $arguments)
		{
			$closure = null;
			if (!isset($this->methodList[$method])) {
				$controllerFolder = '';
				// If the method name contains "/" slashes, extract it as sub controller folder path
				if (false !== ($pos = strrpos($method, '/'))) {
					// If the closure is under the sub folder, it will not requried to add the contoller class name as its prefix
					$closureFilePath = substr($method, 0, $pos) . '/' . substr($method, $pos + 1) . '.php';
				} else {
					$closureFilePath = $this->controllerClass . '.' . $method . '.php';
				}

				// Load closure file if it is exists <Filename Pattern: classname.method>
				$closurePath = append($this->package->getRootPath(), 'controller', $closureFilePath);
				if (is_file($closurePath)) {
					try {
						$closure = require $closurePath;
						if (!\is_callable($closure) && $closure instanceof \Closure) {
							throw new ErrorHandler('The object is a closure.');
						}
						$this->methodList[$method] = $closure;
					} catch (\Exception $e) {
						// Error: The object is not callable
						throw new ErrorHandler('Cannot load closure file, maybe the closure file was corrupted');
					}
				} else {
					return false;
				}
			}

			if ($closure || $closure = $this->methodList[$method] ?? null) {
				return \call_user_func_array($closure, $arguments);
			}

			throw new ErrorHandler('The Controller Closure ' . $method . ' is not exists.');
		}
	}
}
