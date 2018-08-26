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

  	private static $instance     = null;
  	private static $moduleFolder = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'module' . \DIRECTORY_SEPARATOR;

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
  	private $target;

  	public function __construct()
  	{
  		if (null === self::$instance) {
  			self::$instance = $this;
  		} else {
  			// Error: Loaded Twice
  			new ThrowError('ModuleManager', '1001', 'ModuleManager has loaded already');
  		}

  		$this->stage = self::STATUS_PRELOAD_STAGE;

  		// Creating Loader method in preload stage
  		// Loader: view
  		Loader::CreateMethod('view', function (string $filepath, $rootview = false) {
  			// If there is no extension provided, default as .tpl
  			if (!preg_match('/\.[a-z]+$/i', $filepath)) {
  				$filepath .= '.tpl';
  			}

  			$root = (($rootview) ? VIEW_PATH : $this->getViewPath()) . \DIRECTORY_SEPARATOR;
  			$viewUrl = (($rootview) ? VIEW_PATH : $this->getViewPathURL()) . \DIRECTORY_SEPARATOR;

  			$tplManager = new TemplateManager($root . $filepath, $this->getCode());
  			$tplManager->globalAssign([
  				'url_base'       => URL_BASE,
  				'module_root'    => URL_BASE . $this->getRemapPath(),
  				'view_path'      => $viewUrl,
  				'root_view_path' => VIEW_PATH_URL . '/',
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

  		$this->loadModule(self::$moduleFolder);

  		$moduleUnloaded = [];

  		// Load event: __onReady
  		while ($module = array_shift($this->moduleLoaded)) {
  			if (ModulePackage::MODULE_STATUS_LOADED === $module->getPreloadStatus()) {
  				$this->doReady($module);
  			}
  		}

  		if (count($this->moduleInRequire)) {
  			new ThrowError('ModuleManager', '1002', 'The following module is required but load failed: ' . implode(', ', array_keys($this->moduleInRequire)));
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
  							new ThrowError('ModuleManager', '3001', 'Invalid command syntax.');
  						}
  						$args[] = $value;
  					}
  				}
  			}

  			$this->scriptRoute = preg_replace('/\/+/', '/', '/' . implode('/', $args) . '/');
  		}
  	}

  	public static function GetInstance()
  	{
  		return self::$instance;
  	}

  	public function locate(string $path)
  	{
  		$path = rtrim(preg_replace('/[\\\\\/]+/', '/', '/' . $path), '/');
  		header('location: ' . URL_BASE . $path);
  		die();
  	}

  	public function getStage()
  	{
  		return $this->stage;
  	}

  	public function moduleisReady($moduleCode)
  	{
  		return isset($this->moduleReady[$moduleCode]);
  	}

  	public function loadLibrary($class)
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
  						header('location: ' . URL_BASE . $path);
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

  	public static function SetModuleFolder(string $path)
  	{
  		$modulePath = trim($path);
  		$modulePath = realpath(preg_replace('/[\\\\\/]+/', \DIRECTORY_SEPARATOR, $modulePath));
  		if (!file_exists($modulePath) || !is_dir($modulePath)) {
  			new ThrowError('ModuleManager', '4001', $path . ' does not exist or not a directory.');
  		}
  		self::$moduleFolder = $modulePath . \DIRECTORY_SEPARATOR;
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
  						new ThrowError('ModuleManager', '1004', 'You cannot require module itself');
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
  				new ThrowError('ModuleManager', '1003', 'Remap path [' . $remapPath . '] was registered.');
  			}
  			$this->remapMapping[$remapPath] = $module;
  		}

  		return $this;
  	}

  	private function loadModule($moduleFolder)
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
  									new ThrowError('ModuleManaer', '1004', 'Duplicated Module Code');
  								}
  							} else {
  								// Error: Invalid Class
  								new ThrowError('ModuleManaer', '1005', 'Invalid Module File');
  							}
  						}
  					} catch (Exception $e) {
  						// Error: Fail to load module file
  						new ThrowError('ModuleManager', '2001', 'Fail to load module, maybe the setting file was corrupted');
  					}
  				} else {
  					$this->loadModule($subModuleFolder);
  				}
  			}
  		}
  	}
  }
}
