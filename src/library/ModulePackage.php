<?php
namespace RazyFramework
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
  	private $eventListner = array();

  	public function __construct($modulePath, $settings)
    {
      $this->moduleRoot = $modulePath;

      if (isset($settings['module_code']) && trim($settings['module_code'])) {
  			$this->moduleCode = $settings['module_code'];
  		}

      if (isset($settings['author']) && trim($settings['author'])) {
  			$this->authur = $settings['author'];
  		}

  		if (isset($settings['version']) && trim($settings['version'])) {
  			$this->version = $settings['version'];
  		}

      // Add callable method into whitelist
  		if (isset($settings['callable']) && is_array($settings['callable'])) {
  			foreach ($settings['callable'] as $commandName => $namespace) {
          $commandName = trim($commandName);
          if (!$commandName || !$this->isValidNamespace($namespace)) {
  					new ThrowError('ModulePackage', '3002', 'Cannot add ' . $method . ' to whitelist.');
  				}
          $this->callableList[$commandName] = $namespace;
  			}
  		}

      // Add event listener
  		if (isset($settings['event']) && is_array($settings['event'])) {
  			foreach ($settings['event'] as $eventName => $namespace) {
          $eventName = trim($eventName);
          if (!$eventName || !$this->isValidNamespace($namespace)) {
  					new ThrowError('ModulePackage', '3003', 'Cannot add ' . $method . ' event listener.');
  				}
          $this->eventListner[$eventName] = $namespace;
  			}
  		}

  		if (isset($settings['remap']) && trim($settings['remap'])) {
  			$this->remapPath = preg_replace('/[\/\\\\]+/', '/', '/' . $settings['remap'] . '/');
        // Replace $1 as module code
        $this->remapPath = str_replace('$1', $this->moduleCode, $this->remapPath);
  		} else {
        $this->remapPath = '/' . $this->moduleCode . '/';
      }

  		if (isset($settings['route']) && is_array($settings['route'])) {
  			foreach ($settings['route'] as $routeName => $namespace) {
          $routeName = trim($routeName);
          if (!$routeName || !$this->isValidNamespace($namespace)) {
            new ThrowError('ModulePackage', '3001', 'Invalid route\'s class mapping format');
          }
          $this->routeMapping[$routeName] = $namespace;
  			}

  			// If the remap parameter is not set, set the remap path by module code
  			if (!$this->remapPath) {
  				$this->remapPath = '/' . $this->moduleCode . '/';
  			}
  		}
    }

    private function isValidNamespace($namespace)
    {
      $namespace = trim($namespace);
      if ($namespace && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+\.[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/', $namespace)) {
        return true;
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

  	public function route($args)
    {
      $routeName = $args[0];
      // If method route mapping matched, return the contoller
			if (isset($this->routeMapping[$routeName])) {
				$method = array_shift($args);
				list($className, $method) = explode('.', $this->routeMapping[$routeName]);
				$moduleController = $this->getController($className);
			} else {
        $routeName = '(:any)';
        // If no method route matched, re-route all argument to (:any).
        if (isset($this->routeMapping['(:any)'])) {
  				list($className, $method) = explode('.', $this->routeMapping['(:any)']);
  				$moduleController = $this->getController($className);
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
			$result = call_user_func_array(array($moduleController, $method), $args);
      if ($result === false) {
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

        call_user_func_array(array($moduleController, $method), $args);
  		}

      return $this;
  	}

  	public function execute($mapping, $args)
    {
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
        return call_user_func_array(array($moduleController, $method), $args);
  		}
      return null;
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

          // Get the lasy declared class name, assume one file contain one class
          $declaredClass = get_declared_classes();
          $declaredClass = end($declaredClass);

          // Get the class name without namespace
          $_className = explode('\\', $declaredClass);
          $_className = end($_className);

          if ($_className != $className) {
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
