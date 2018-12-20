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
  class ModuleManager
  {
  	const STATUS_PRELOAD_STAGE = 1;
  	const STATUS_READY_STAGE   = 2;
  	const STATUS_ROUTING_STAGE = 3;

  	const REGEX_WILDCARD = '/\{(?::(any|digi|word|alpha)|:\\?((?>[^{}\\\\]|\\\\.)+))\}/';

  	private static $instance            = null;
  	private static $moduleFolder        = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'module' . \DIRECTORY_SEPARATOR;
  	private static $moduleDistributions = [];
  	private static $distribution        = '';
  	private static $ignorePath          = [];

  	private $routeModule;
  	private $remapSorted         = [];
  	private $routeArguments      = [];
  	private $remapMapping        = [];
  	private $moduleLoaded        = [];
  	private $moduleReady         = [];
  	private $moduleUnloaded      = [];
  	private $moduleInRequire     = [];
  	private $scriptPath          = '';
  	private $scriptRoute         = '/';
  	private $scriptParams        = [];
  	private $stage               = '';
  	private $reroute             = '';
  	private $urlQuery            = '';
  	private $target;

  	public function __construct()
  	{
  		if (null === self::$instance) {
  			self::$instance = $this;
  		} else {
  			// Error: Loaded Twice
  			new ThrowError('ModuleManager has loaded already');
  		}

  		// Obtain the URL request query string
  		if (!CLI_MODE) {
  			$urlQuery = (URL_ROOT) ? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], URL_ROOT) + strlen(URL_ROOT)) : $_SERVER['REQUEST_URI'];

  			$pathInfo       = parse_url($urlQuery);
  			$this->urlQuery = rtrim($pathInfo['path'], '/') . '/';
  			$this->urlQuery = preg_replace('/^\/index.php/', '', $this->urlQuery);
  		} else {
  			$argv = $_SERVER['argv'];
  			if (count($argv)) {
  				$this->urlQuery = array_shift($argv);
  			}
  		}

  		$this->stage = self::STATUS_PRELOAD_STAGE;

  		// Creating Loader method in preload stage
  		// Loader: view
  		Loader::CreateMethod('view', function (string $filepath, bool $useSharedView = false) {
  			// If there is no extension provided, default as .tpl
  			if (!preg_match('/\.[a-z]+$/i', $filepath)) {
  				$filepath .= '.tpl';
  			}

  			$tplManager = new TemplateManager((($useSharedView) ? SHARED_VIEW_PATH : $this->getViewPath()) . \DIRECTORY_SEPARATOR . $filepath, $this->getCode());
  			$tplManager->globalAssign([
  				'system_root_url' => SYSTEM_ROOT_URL,
  				'module_root_url' => $this->getModuleRootURL(),
  				'module_view_url' => (($useSharedView) ? SHARED_VIEW_URL : $this->getViewPathURL()) . \DIRECTORY_SEPARATOR,
  				'shared_view_url' => SHARED_VIEW_URL . '/',
  			]);

  			return $tplManager;
  		});

  		// Loader: config
  		Loader::CreateMethod('config', function (string $filename) {
  			return new Configuration($this, $filename);
  		});

  		// Loader: db
  		Loader::CreateMethod('db', function (string $connectionName) {
  			return Database::GetConnection($connectionName);
  		});

  		// Match the module distribution, if no module distribution declared, use default module path.
  		if (count(self::$moduleDistributions)) {
  			// If there is distribution matched, start load module
  			// If no distribution matched, no module will be loaded
  			if ($this->matchDistribution()) {
  				// Declare `SYSTEM_ROOT_URL`
  				define('SYSTEM_ROOT_URL', CORE_BASE_URL . self::$distribution);

  				$this->loadModule(self::$moduleFolder);
  			}
  		} else {
  			// Declare `SYSTEM_ROOT_URL`
  			define('SYSTEM_ROOT_URL', CORE_BASE_URL);

  			// If no distribution declared, load default module folder
  			$this->loadModule(self::$moduleFolder);
  		}

  		$moduleUnloaded = [];

  		// Load event: __onReady
  		while ($module = array_shift($this->moduleLoaded)) {
  			if (ModulePackage::MODULE_STATUS_LOADED === $module->getPreloadStatus()) {
  				$this->doReady($module);
  			}
  		}

  		if (count($this->moduleInRequire)) {
  			new ThrowError('The following module is required but load failed: ' . implode(', ', array_keys($this->moduleInRequire)));
  		}

  		// If all module ready, change to ready stage
  		$this->stage = self::STATUS_READY_STAGE;

  		// Prepare Routing
  		foreach ($this->moduleReady as $module) {
  			$module->prepareRouting();
  		}

  		if (CLI_MODE) {
  			// Cli Mode, get arguments and parameters
  			$argv             = $_SERVER['argv'];
  			$this->scriptPath = array_shift($argv);
  			$paramName        = '';
  			$args             = [];

  			foreach ($argv as $key => $value) {
  				if (preg_match('/^(-){1,2}([^\s]+)$/', $value, $matches, PREG_OFFSET_CAPTURE)) {
  					$paramName                      = $matches[2][0];
  					$this->scriptParams[$paramName] = true;
  				} else {
  					if ($paramName) {
  						$this->scriptParams[$paramName] = $value;
  						$paramName                      = '';
  					} else {
  						if (count($this->scriptParams)) {
  							new ThrowError('Invalid command syntax.');
  						}
  						$args[] = $value;
  					}
  				}
  			}

  			// Console line mode
  			if (isset($this->scriptParams['console'])) {
  				new Console();
  			} else {
  				$this->scriptRoute = preg_replace('/\/+/', '/', '/' . implode('/', $args) . '/');
  			}
  		}
  	}

  	public function getURLQuery()
  	{
  		return $this->urlQuery;
  	}

  	public static function GetInstance()
  	{
  		return self::$instance;
  	}

  	public function locate(string $path)
  	{
  		if (preg_match('/^https?\:\/\//', $path)) {
  			header('location: ' . $path);
  		} else {
  			$path = rtrim(preg_replace('/[\\\\\/]+/', '/', '/' . $path), '/');
  			header('location: ' . SYSTEM_ROOT_URL . $path);
  		}
  		die();
  	}

  	public function getStage()
  	{
  		return $this->stage;
  	}

  	public function moduleisReady(string $moduleCode)
  	{
  		return isset($this->moduleReady[$moduleCode]);
  	}

  	public function loadLibrary(string $class)
  	{
  		$classPath = str_replace('\\', \DIRECTORY_SEPARATOR, $class);
  		foreach ($this->moduleReady as $module_code => $module) {
  			$libraryPath = $module->getModuleRoot() . 'library' . \DIRECTORY_SEPARATOR . $classPath . '.php';

  			if (file_exists($libraryPath)) {
  				include $libraryPath;

  				return class_exists($class);
  			}
  		}

  		return false;
  	}

  	public function getScriptPath()
  	{
  		return $this->scriptPath;
  	}

  	public function getScriptRoute()
  	{
  		return $this->scriptRoute;
  	}

  	public function getScriptParameters()
  	{
  		return $this->scriptParams;
  	}

  	public function execute()
  	{
  		$args                       = func_get_args();
  		$command                    = trim(array_shift($args));
  		list($moduleName, $mapping) = explode('.', $command);

  		if ($moduleName && $mapping) {
  			if (isset($this->moduleReady[$moduleName])) {
  				$this->target = $this->moduleReady[$moduleName];

  				return $this->moduleReady[$moduleName]->execute($mapping, $args);
  			}
  		}

  		return false;
  	}

  	public function trigger()
  	{
  		$args  = func_get_args();
  		$event = array_shift($args);
  		$event = trim($event);

  		foreach ($this->moduleReady as $moduleCode => $module) {
  			$module->trigger($event, $args);
  		}

  		return $this;
  	}

  	public function getTarget()
  	{
  		return $this->target;
  	}

  	public function getRouteArguments()
  	{
  		return $this->routeArguments;
  	}

  	public function reroute(string $path)
  	{
  		$path          = preg_replace('/[\\\\\/]+/', '/', '/' . trim($path) . '/');
  		$this->reroute = $path;

  		return $this;
  	}

  	public function route(string $path)
  	{
  		$path = ($this->reroute) ? $this->reroute : preg_replace('/[\\\\\/]+/', '/', '/' . trim($path) . '/');
  		if (count($this->remapMapping)) {
  			// Sort the remap path list, the deepest route first
  			if (!$this->remapSorted) {
  				uksort($this->remapMapping, function ($path_a, $path_b) {
  					$count_a = substr_count($path_a, '/');
  					$count_b = substr_count($path_b, '/');
  					if ($count_a === $count_b) {
  						return 0;
  					}

  					return ($count_a < $count_b) ? 1 : -1;
  				});

  				$this->remapSorted = true;
  			}

  			foreach ($this->remapMapping as $remap => $module) {
  				if (0 === strpos($path, $remap)) {
  					// Get the relative path and remove the last slash
  					$argsString = preg_replace('/\/*$/', '', substr($path, strlen($remap)));

  					// Extract the path into an arguments array
  					$args = ($argsString) ? explode('/', $argsString) : [];

  					if ($this->routeModule) {
  						// If Razy has routed already, redirect it
  						header('location: ' . CORE_BASE_URL . $path);
  					} else {
  						// Save the current route module and arguments for internal use
  						$this->routeArguments = $args;
  						$this->routeModule    = $module;

  						// Save the current module for event
  						$this->target = $module;

  						// Execute and pass the arguments to module
  						if ($module->route($args)) {
  							return true;
  						}
  					}
  				}
  			}
  		}

  		return false;
  	}

  	public static function SetDefaultModulePath(string $path)
  	{
  		$path = realpath(preg_replace('/[\/\\\\]+/', \DIRECTORY_SEPARATOR, \DIRECTORY_SEPARATOR . trim($path) . \DIRECTORY_SEPARATOR));
  		if (!$path || !file_exists($modulePath) || !is_dir($modulePath)) {
  			new ThrowError('The default module path does not exist or not a directory.');
  		}

  		self::$moduleFolder = $path;
  	}

  	public static function GetDistribution()
  	{
  		return self::$distribution;
  	}

  	public static function SetModuleDistribution(array $distributions)
  	{
  		foreach ($distributions as $route => $modulePath) {
  			// If the path is a callback function, execute once and obtain the return value
  			if (is_callable($modulePath)) {
  				$modulePath = $modulePath();
  			}

  			if (!is_string($modulePath)) {
  				new ThrowError($route . ' is not a valid path string.');
  			}

  			// Tidy route and module path
  			$route      = preg_replace('/[\\\\\/]+/', '/', '/' . trim($route) . '/');
  			$modulePath = preg_replace('/[\\\\\/]+/', \DIRECTORY_SEPARATOR, $modulePath . \DIRECTORY_SEPARATOR);

  			// Add distribution and ignore path
  			self::$moduleDistributions[$route] = $modulePath;
  			self::$ignorePath[$modulePath]     = true;
  		}

  		// Sort module distribution mapping
  		if (count(self::$moduleDistributions)) {
  			uksort(self::$moduleDistributions, function ($path_a, $path_b) {
  				$count_a = substr_count($path_a, '/');
  				$count_b = substr_count($path_b, '/');
  				if ($count_a === $count_b) {
  					return 0;
  				}

  				return ($count_a < $count_b) ? 1 : -1;
  			});
  		}
  	}

  	private function matchDistribution()
  	{
  		if (count(self::$moduleDistributions)) {
  			foreach (self::$moduleDistributions as $route => $path) {
  				if (preg_match(self::REGEX_WILDCARD, $route)) {
  					// If the path is a wildcard selector
  					$clips = explode('/', $route);
  					foreach ($clips as &$clip) {
  						$clip = preg_replace_callback(self::REGEX_WILDCARD, function ($matches) {
  							if (isset($matches[2])) {
  								return '(' . $matches[2] . ')';
  							}

  							if ('any' === $matches[1]) {
  								return '(.+?)';
  							}

  							if ('digi' === $matches[1]) {
  								return '(\d+)';
  							}

  							if ('alpha' === $matches[1]) {
  								return '([a-zA-Z]+)';
  							}

  							if ('word' === $matches[1]) {
  								return '(\w+)';
  							}
  						}, $clip);
  					}
            // Join all clip and convert to regex pattern
  					$route = '/^' . implode('\\/', $clips) . '(.*)/';

  					if (preg_match_all($route, $this->urlQuery, $matches, PREG_SET_ORDER)) {
    					// Update the URL query
    					$this->urlQuery = array_pop($matches[0]);

    					// If module distribution is found
    					if (is_string($path)) {
    						self::$distribution = $route;
    						self::$moduleFolder = $path;
    					}

  						return true;
  					}
  				} elseif (0 === strpos($this->urlQuery, $route)) {
  					if (is_callable($path)) {
  						$path = $path();
  					}

  					// If module distribution is found
  					if (is_string($path)) {
  						self::$distribution = $route;
  						self::$moduleFolder = $path;
  					}

  					// Update the URL query
  					$this->urlQuery = substr($this->urlQuery, strlen($route) - 1);

  					return true;
  				}
  			}
  		}

  		return false;
  	}

  	private function doReady(ModulePackage $module)
  	{
  		if (ModulePackage::MODULE_STATUS_LOADED === $module->getPreloadStatus()) {
  			// Get module require list
  			$require = $module->getRequire();

  			unset($this->moduleLoaded[$module->getCode()]);

  			// Trigger the require module' ready event first if there is any module cannot be loaded
  			// Ignore module ready stage
  			if (count($require)) {
  				foreach ($require as $requireModuleName => $version) {
  					if ($requireModuleName === $module->getCode()) {
  						new ThrowError('You cannot require module itself');
  					}

  					// If the module is ready, skip require
  					if (isset($this->moduleReady[$requireModuleName])) {
  						unset($require[$requireModuleName]);
  					} else {
  						// Add require module to list
  						if (!isset($this->moduleInRequire[$requireModuleName])) {
  							$this->moduleInRequire[$requireModuleName] = [];
  						}
  						$this->moduleInRequire[$requireModuleName][$module->getCode()] = true;

  						// If the unprocessed module is found, try to trigger its ready event
  						if (isset($this->moduleLoaded[$requireModuleName])) {
  							if (!$this->doReady($this->moduleLoaded[$requireModuleName])) {
  								// If the module loads failed, return false
  								$this->moduleUnloaded[$moduleName] = $requireModule;

  								return false;
  							}
  							// Delete the module in require list
  							unset($this->moduleInRequire[$requireModuleName]);
  						}
  					}
  				}
  			}

  			if (ModulePackage::MODULE_STATUS_UNLOADED === $module->ready()) {
  				// Unload the module if the status is unloaded.
  				// Put the module to unload list
  				$this->moduleUnloaded[$module->getCode()] = $module;

  				return false;
  			}

  			if (ModulePackage::MODULE_STATUS_READY === $module->ready()) {
  				if (isset($this->moduleInRequire[$module->getCode()])) {
  					unset($this->moduleInRequire[$module->getCode()]);
  				}
  				$this->moduleReady[$module->getCode()] = $module;
  				$this->setRemap($module);

  				return true;
  			}
  		}

  		return false;
  	}

  	private function setRemap(ModulePackage $module)
  	{
  		// If Module is ready, setup remap path
  		if ($remapPath = $module->getRemapPath()) {
  			if (isset($this->remapMapping[$remapPath])) {
  				// Error: Remap path registered
  				new ThrowError('Remap path [' . $remapPath . '] was registered.');
  			}
  			$this->remapMapping[$remapPath] = $module;
  		}

  		return $this;
  	}

  	private function loadModule(string $moduleFolder)
  	{
  		if (!file_exists($moduleFolder) || !is_dir($moduleFolder)) {
  			return false;
  		}

  		foreach (scandir($moduleFolder) as $node) {
  			if ('.' === $node || '..' === $node) {
  				continue;
  			}

  			// Get the module path
  			$subModuleFolder = $moduleFolder . $node . \DIRECTORY_SEPARATOR;
  			if (isset(self::$ignorePath[$subModuleFolder])) {
  				// Skip scanning the module distribution folder
  				continue;
  			}

  			if (is_dir($subModuleFolder)) {
  				// Search the setting file
  				if (file_exists($subModuleFolder . 'config.php')) {
  					try {
  						// Create Module Package and load the setting
  						$modulePackage = new ModulePackage($subModuleFolder, require $subModuleFolder . 'config.php');
  						if (ModulePackage::MODULE_STATUS_LOADED === $modulePackage->getPreloadStatus()) {
  							// Register the module if the module is loaded
  							if ('RazyFramework\ModulePackage' === get_class($modulePackage)) {
  								if (!isset($this->moduleReady[$modulePackage->getCode()])) {
  									$this->moduleLoaded[$modulePackage->getCode()] = $modulePackage;
  								} else {
  									// Error: Duplicated Module
  									new ThrowError('Duplicated Module Code');
  								}
  							} else {
  								// Error: Invalid Class
  								new ThrowError('Invalid Module File');
  							}
  						}
  					} catch (Exception $e) {
  						// Error: Fail to load module file
  						new ThrowError('Fail to load module, maybe the setting file was corrupted');
  					}
  				} else {
  					$this->loadModule($subModuleFolder);
  				}
  			}
  		}
  	}
  }
}
