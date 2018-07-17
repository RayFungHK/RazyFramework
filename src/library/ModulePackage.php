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
  	private $routeMapping = array();
  	private $callableList = array();
    private $controllerList = array();

  	public function __construct($modulePath, $settings)
    {
      $this->moduleRoot = $modulePath;
      $this->callableList = array(
        'route' => array(),
        'command' => array(),
        'event' => array()
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
  			foreach ($settings['route'] as $routeName => $namespace) {
          if (!$this->parseMethodName($routeName, $namespace)) {
            new ThrowError('ModulePackage', '3001', 'Invalid route\'s class mapping format');
          }
          $this->routeMapping[$routeName] = $namespace;
  			}

  			// If the remap parameter is not set, set the remap path by module code
  			if (!$this->remapPath) {
  				$this->remapPath = '/' . $this->moduleCode . '/';
  			}
  		}

      // Add callable method into whitelist
  		if (isset($settings['callable']) && is_array($settings['callable'])) {
  			foreach ($settings['callable'] as $commandName => $namespace) {
          if (!$this->parseMethodName($commandName, $namespace)) {
  					new ThrowError('ModulePackage', '3002', 'Cannot add ' . $funcName . ' to whitelist.');
  				}
          $this->callableList[$routeName] = $namespace;
  			}
  		}
    }

    private function parseMethodName($routeName, $namespace)
    {
      $routeName = trim($routeName);
      $funcName = trim($namespace);

      if ($routeName && $namespace) {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+\.[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/', $namespace)) {
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
      $routeName = '';
      // Search method route if there is at least one argument provided
  		$routeName = (count($args)) ? $args[0] : '(:root)';

      // If method route mapping matched, return the contoller
			if (isset($this->routeMapping[$routeName])) {
				$method = array_shift($args);
				list($className, $funcName) = explode('.', $this->routeMapping[$routeName]);
				$moduleController = $this->getController($className);
			} else {
        $routeName = '(:any)';
        // If no method route matched, re-route all argument to (:any).
        if (isset($this->routeMapping['(:any)'])) {
  				list($className, $funcName) = explode('.', $this->routeMapping['(:any)']);
  				$moduleController = $this->getController($className);
  			}

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
      $this->routeName = $routeName;

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
  		if (isset($this->callableList['event'][$event])) {
  			list($className, $funcName) = explode('.', $this->callableList['event'][$event]);
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
			if (isset($this->callableList['command'][$commandMethod])) {
				list($className, $funcName) = explode('.', $this->callableList['command'][$commandMethod]);
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
