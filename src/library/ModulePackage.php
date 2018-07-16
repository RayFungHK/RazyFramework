<?php
namespace Core
{
  class ModulePackage
  {
  	private $moduleRoot = '';
  	private $moduleCode = '';
  	private $author = '';
  	private $version = '';
  	private $remapPath = '';
    private $routeName = '';
  	private $methodMapping = array();
    private $controllerList = array();

  	public function __construct($modulePath, array $settings)
    {
      $this->moduleRoot = $modulePath;
      $this->methodMapping = array(
        'route' => array(),
        'command' => array(),
        'event' => array(),
        'cli' => array()
      );

      if (isset($settings['module_code']) && trim($settings['module_code'])) {
  			$this->moduleCode = $settings['module_code'];
  		}

      if (isset($settings['author']) && trim($settings['author'])) {
  			$this->authur = $settings['author'];
  		}

  		if (isset($settings['version']) && trim($settings['version'])) {
  			$this->version = $settings['version'];
  		}

  		if (isset($settings['remap']) && trim($settings['remap'])) {
  			$this->remapPath = rtrim(preg_replace('/([\/\\\\]+|\\\\)/', '/', '/' . $settings['remap']), '/') . '/';
  		} else {
        $this->remapPath = '/' . $this->moduleCode . '/';
      }

  		if (isset($settings['route']) && is_array($settings['route'])) {
  			foreach ($settings['route'] as $mappingName => $funcName) {
          if (!$this->parseMethodName($mappingName, $funcName, 'route')) {
            new ThrowError('ModulePackage', '3001', 'Invalid route\'s class mapping format');
          }
  			}

  			// If the remap parameter is not set, set the remap path by module code
  			if (!$this->remapPath) {
  				$this->remapPath = '/' . $this->moduleCode . '/';
  			}
  		}

  		if (isset($settings['command']) && is_array($settings['command'])) {
  			foreach ($settings['command'] as $commandName => $funcName) {
          if (!$this->parseMethodName($commandName, $funcName, 'command')) {
  					new ThrowError('ModulePackage', '3002', 'Invalid command\'s class mapping format');
  				}
  			}
  		}

  		if (isset($settings['event']) && is_array($settings['event'])) {
  			foreach ($settings['event'] as $eventName => $funcName) {
          if (!$this->parseMethodName($eventName, $funcName, 'event')) {
  					new ThrowError('ModulePackage', '3003', 'Invalid event\'s class mapping format');
  				}
  			}
  		}

      if (isset($settings['cli']) && is_array($settings['cli'])) {
  			foreach ($settings['cli'] as $commandName => $funcName) {
          if (!$this->parseMethodName($commandName, $funcName, 'cli')) {
  					new ThrowError('ModulePackage', '3004', 'Invalid cli\'s class mapping format');
  				}
  			}
  		}
    }

    private function parseMethodName($mappingName, $funcName, $mapping)
    {
      $mappingName = trim($mappingName);
      $funcName = trim($funcName);

      if ($mappingName && $funcName) {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\.[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $funcName)) {
          $this->methodMapping[$mapping][$mappingName] = $funcName;
          return true;
        }
      }
      return false;
    }

  	public function getCode()
    {
  		return $this->moduleCode;
  	}

  	public function getModuleRoot($relativePath = false)
    {
      if ($relativePath) {
        return preg_replace('/^' . preg_quote(SYSTEM_ROOT, '/') . '/', '', $this->moduleRoot);
      } else {
        return $this->moduleRoot;
      }
  	}

  	public function getRemapPath()
    {
  		return $this->remapPath;
  	}

  	public function execute($args)
    {
  		$moduleController = null;
      $mappingName = '';
      // Search method route if there is at least one argument provided
  		if (count($args)) {
        // Get the route name from first element of arguments
  			$mappingName = $args[0];
      } else {
        $mappingName = '(:root)';
      }

      // If method route mapping matched, return the contoller
			if (isset($this->methodMapping['route'][$mappingName])) {
				$method = array_shift($args);
				list($className, $funcName) = explode('.', $this->methodMapping['route'][$mappingName]);
				$moduleController = $this->getController($className);
			} else {
        // If no method route matched, re-route all argument to (:any).
        if (isset($this->methodMapping['route']['(:any)'])) {
  				list($className, $funcName) = explode('.', $this->methodMapping['route']['(:any)']);
  				$moduleController = $this->getController($className);
  			}
        $mappingName = '(:any)';

        if (!$moduleController) {
          // No (:any) route exists, return 404 not found
          return false;
        }
      }

			$methodExists = false;
      // Check the methed is callable or not, protected and private method is not executeable
			if (method_exists($moduleController, $funcName)) {
        // Method Reflection, get the method type
				$reflection = new \ReflectionMethod($moduleController, $funcName);
		    if (!$reflection->isPublic()) {
					// Error: Controller function not callable
					new ThrowError('ModulePackage', '2002', 'Cannot execute the method, maybe it is not a public method');
		    }
			}

      // Set the matched mapping name as route name
      $this->routeName = $mappingName;

      // Pass all arguments to routed method
			call_user_func_array(array($moduleController, $funcName), $args);

			return true;
  	}

    public function getRouteName()
    {
      return $this->routeName;
    }

  	public function invoke($event, $args)
    {
  		if (isset($this->methodMapping['event'][$event])) {
  			list($className, $funcName) = explode('.', $this->methodMapping['event'][$event]);
  			if (!($moduleController = $this->getController($className))) {
  				// Error: Controller Not Found [Class]
  				new ThrowError('ModulePackage', '4002', 'Controller Not Found [Class]');
  			}

        // Check the methed is callable or not, protected and private method is not executeable
  			if (method_exists($moduleController, $funcName)) {
          // Method Reflection, get the method type
  				$reflection = new \ReflectionMethod($moduleController, $funcName);
  		    if (!$reflection->isPublic()) {
  					// Error: Controller function not callable
  					new ThrowError('ModulePackage', '3002', 'Cannot execute the method, maybe it is not a public method');
  		    }
  			}
        // Pass all arguments to routed method
  			call_user_func_array(array($moduleController, $funcName), $args);
  		}
  	}

    public function trigger($commandMethod, $args = array())
    {
  		$moduleController = null;

      // If method route mapping matched, return the contoller
			if (isset($this->methodMapping['command'][$commandMethod])) {
				list($className, $funcName) = explode('.', $this->methodMapping['command'][$commandMethod]);
				$moduleController = $this->getController($className);
			} else {
        new ThrowError('ModulePackage', '3001', 'Command not found');
      }

      // Check the methed is callable or not, protected and private method is not executeable
			if (method_exists($moduleController, $funcName)) {
        // Method Reflection, get the method type
				$reflection = new \ReflectionMethod($moduleController, $funcName);
		    if (!$reflection->isPublic()) {
					// Error: Controller function not callable
					new ThrowError('ModulePackage', '3002', 'Cannot execute the method, maybe it is not a public method');
		    }
			}
      // Pass all arguments to routed method
			return call_user_func_array(array($moduleController, $funcName), $args);
    }

    public function command($commandMethod, $args = array())
    {
      $moduleController = null;

      // If method route mapping matched, return the contoller
      if (isset($this->methodMapping['cli'][$commandMethod])) {
        list($className, $funcName) = explode('.', $this->methodMapping['cli'][$commandMethod]);
        $moduleController = $this->getController($className);
      } else {
        return false;
      }

      // Check the methed is callable or not, protected and private method is not executeable
      if (method_exists($moduleController, $funcName)) {
        // Method Reflection, get the method type
        $reflection = new \ReflectionMethod($moduleController, $funcName);
        if (!$reflection->isPublic()) {
          // Error: Controller function not callable
          return false;
        }
      }

      // Pass all arguments to routed method
      call_user_func_array(array($moduleController, $funcName), $args);
      return true;
    }

  	private function getController($className)
    {
      // Search defined controller from list
  		if (isset($this->controllerList[$className])) {
  			return $this->controllerList[$className];
  		} else {
        $controllerPath = $this->moduleRoot . 'controller' . DIRECTORY_SEPARATOR;

        // Check the class file is exists or not
  			if (file_exists($controllerPath . $className . '.php')) {
          // Load the class file, all module controller class MUST under 'Module' namespace
  				include($controllerPath . $className . '.php');
          $classNameNS = 'Module\\' . $className;

  				if (class_exists($classNameNS)) {
            // Create controller object and put into controller list
  					$this->controllerList[$className] = new $classNameNS($this);

            // Check the controller class has inherit IController class or not
  					if (!is_subclass_of($this->controllerList[$className], 'Core\\IController')) {
  						// Error: Controller's class should inherit IController
  						new ThrowError('ModulePackage', '1002', 'Controller\'s class should inherit IController');
  					}
  					return $this->controllerList[$className];
  				} else {
  					// Error: Controller's class not found
  					new ThrowError('ModulePackage', '1001', 'Controller\'s class not exists');
  				}
  			}
  		}
  		return null;
  	}
  }
}
?>
