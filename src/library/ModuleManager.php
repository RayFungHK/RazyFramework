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
  class ModuleManager
  {
  	const STATUS_PRELOAD_STAGE = 1;
  	const STATUS_READY_STAGE   = 2;
  	const STATUS_ROUTING_STAGE = 3;

  	private static $instance     = null;
  	private static $moduleFolder = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'module' . \DIRECTORY_SEPARATOR;

  	private $routeModule;
  	private $remapSorted      = [];
  	private $routeArguments   = [];
  	private $remapMapping     = [];
  	private $moduleRegistered = [];
  	private $scriptPath       = '';
  	private $scriptRoute      = '/';
  	private $scriptParams     = [];
  	private $stage            = '';
  	private $target;

  	public function __construct()
  	{
  		if (null === self::$instance) {
  			self::$instance = $this;

  			$this->stage = self::STATUS_PRELOAD_STAGE;

  			// Creating Loader method in preload stage
  			// Loader: view
  			Loader::CreateMethod('view', function ($filepath, $rootview = false) {
  				// If there is no extension provided, default as .tpl
  				if (!preg_match('/\.[a-z]+$/i', $filepath)) {
  					$filepath .= '.tpl';
  				}

  				$root       = (($rootview) ? VIEW_PATH : $this->getViewPath()) . \DIRECTORY_SEPARATOR;
  				$tplManager = new TemplateManager($root . $filepath, $this->getCode());
  				$tplManager->globalAssign([
  					'view_path' => $root,
  				]);
  				$tplManager->addToQueue();

  				return $tplManager;
  			});

  			// Loader: config
  			Loader::CreateMethod('config', function ($filename) {
  				return new Configuration($this, $filename);
  			});

  			$this->loadModule(self::$moduleFolder);

  			// Load event: __onReady
  			foreach ($this->moduleRegistered as $moduleCode => $module) {
  				if (ModulePackage::MODULE_STATUS_UNLOADED === $module->ready()) {
  					// Unload the module if the status is unloaded
  					unset($this->moduleRegistered[$moduleCode]);
  				} elseif (ModulePackage::MODULE_STATUS_READY === $module->ready()) {
  					// If Module is ready, setup remap path
  					if ($remapPath = $module->getRemapPath()) {
  						if (isset($this->remapMapping[$remapPath])) {
  							// Error: Remap path registered
  							new ThrowError('ModuleManager', '1003', 'Remap path [' . $remapPath . '] was registered.');
  						}
  						$this->remapMapping[$remapPath] = $module;
  					}
  				}
  			}
  		} else {
  			// Error: Loaded Twice
  			new ThrowError('ModuleManager', '1001', 'ModuleManager has loaded already');
  		}

  		// If all module ready, change to ready stage
  		$this->stage = self::STATUS_READY_STAGE;

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

  	public function getStage()
  	{
  		return $this->stage;
  	}

  	public function loadLibrary($class)
  	{
  		$classPath = str_replace('\\', \DIRECTORY_SEPARATOR, $class);
  		foreach ($this->moduleRegistered as $module_code => $module) {
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
  			if (isset($this->moduleRegistered[$moduleName])) {
  				$this->target = $this->moduleRegistered[$moduleName];

  				return $this->moduleRegistered[$moduleName]->execute($mapping, $args);
  			}
  		}

  		return false;
  	}

  	public function trigger()
  	{
  		$args  = func_get_args();
  		$event = array_shift($args);
  		$event = trim($event);

  		foreach ($this->moduleRegistered as $moduleCode => $module) {
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

  	public function route($path)
  	{
  		$path = preg_replace('/[\\\\\/]+/', '/', '/' . trim($path) . '/');
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
  								if (!isset($this->moduleRegistered[$modulePackage->getCode()])) {
  									$this->moduleRegistered[$modulePackage->getCode()] = $modulePackage;
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
