<?php
namespace Core
{
  class ModuleManager
  {
  	static private $instance = NULL;
    private $remapSorted = array();
    private $routeModule = null;
    private $routeArguments = array();
    private $remapMapping = array();
    private $moduleRegistered = array();

  	public static function GetInstance()
    {
  		return self::$instance;
  	}

    public function __construct()
    {
  		if (self::$instance === NULL) {
  			self::$instance = $this;
        $moduleFolder = SYSTEM_ROOT . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR;
  			$this->scanModule($moduleFolder);
  		} else {
  			// Error: Loaded Twice
  			new ThrowError('ModuleManager', '1001', 'ModuleManager has loaded already');
  		}
    }

    private function scanModule($moduleFolder)
    {
  		$contents = array();
  		foreach (scandir($moduleFolder) as $node) {
  			if ($node == '.' || $node == '..') {
  				continue;
  			}

        // Get the module path
        $subModuleFolder = $moduleFolder . $node . DIRECTORY_SEPARATOR;
  			if (is_dir($subModuleFolder)) {
          // Search the setting file
  				if (file_exists($subModuleFolder . 'config.php')) {
            try {
              // Create Module Package and load the setting
              $modulePackage = new ModulePackage($subModuleFolder, require $subModuleFolder . 'config.php');
              $this->register($modulePackage);
            } catch (Exception $e) {
  						// Error: Fail to load module file
  						new ThrowError('ModuleManager', '2001', 'Fail to load module, maybe the setting file was corrupted');
            }
  				} else {
  					$this->scanModule($subModuleFolder);
  				}
  			}
  		}
    }

  	private function register($modulePackage)
    {
  		if (get_class($modulePackage) == 'Core\\ModulePackage')   {
  			if (!isset($this->moduleRegistered[$modulePackage->getCode()])) {
  				$this->moduleRegistered[$modulePackage->getCode()] = $modulePackage;
  				if ($remapPath = $modulePackage->getRemapPath()) {
  					if (isset($this->remapMapping[$remapPath])) {
  						// Error: Remap path registered
  						new ThrowError('ModuleManaer', '1003', 'Remap path [' . $remapPath . '] was registered.');
  					} else {
  						$this->remapMapping[$remapPath] = $modulePackage;
  					}
  				}
  			} else {
  				// Error: Duplicated Module
  				new ThrowError('ModuleManaer', '1004', 'Duplicated Module Code');
  			}
  		} else {
  			// Error: Invalid Class
  			new ThrowError('ModuleManaer', '1005', 'Invalid Module File');
  		}
  	}

    public function cli($command, $args = array())
    {
      $command = trim($command);
      list($moduleName, $funcName) = explode('.', $command);

      if ($moduleName && $funcName) {
        if (isset($this->moduleRegistered[$moduleName])) {
          return $this->moduleRegistered[$moduleName]->command($funcName, $args);
        }
      }

      return false;
    }

    public function trigger()
    {
      $args = func_get_args();
      $command = trim(array_shift($args));
      list($moduleName, $funcName) = explode('.', $command);

      if ($moduleName && $funcName) {
        if (isset($this->moduleRegistered[$moduleName])) {
          return $this->moduleRegistered[$moduleName]->trigger($funcName, $args);
        }
      }

      return false;
    }

  	public function event()
    {
  		$args = func_get_args();
  		$event = array_shift($args);
  		$event = trim($event);

  		foreach ($this->moduleRegistered as $moduleCode => $module) {
  			$module->invoke($event, $args);
  		}

      return $this;
  	}

    public function getRouteArguments()
    {
      return $this->routeArguments;
    }

  	public function route($path)
    {
  		if (count($this->remapMapping)) {
        // Sort the remap path list by nearest root
  			if (!$this->remapSorted) {
  				uksort($this->remapMapping, function($path_a, $path_b) {
  					$count_a = substr_count($path_a, '/');
  					$count_b = substr_count($path_b, '/');
  			    if ($count_a == $count_b) {
  			        return 0;
  			    }
  			    return ($count_a < $count_b) ? 1 : -1;
    			});

  				$this->remapSorted = true;
  			}

  			foreach ($this->remapMapping as $remap => $module) {
  				if (strpos($path, $remap) === 0) {
            // Get the relative path and remove the last slash
  					$argsString = preg_replace('/\/*$/', '', substr($path, strlen($remap)));

            // Extract the path into an arguments array
  					$args = ($argsString) ? explode('/', $argsString) : array();

            // Save the current route module and arguments for internal use
  					$this->routeArguments = $args;
  					$this->routeModule = $module;

            // Execute and pass the arguments, if no route mapping was matched
  					if ($module->execute($args)) {
  						return true;
  					}
  				}
  			}
  		}

  		return false;
  	}
  }
}
?>
