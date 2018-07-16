<?php
namespace Core
{
  abstract class IController
  {
    protected $calledClass = '';
    protected $module = null;
    protected $manager = null;
    protected $methodList = array();

    public final function __construct($module)
    {
      $classname = explode('\\', get_class($this));
      $package = array_shift($classname);
      if ($package != 'Module') {
        new ThrowError('IController', '1001', 'The module class was not in Module namespace');
      }
      // Get called class, remove the 'Module' namespace
      $calledClass = explode('\\', get_called_class());
      $this->calledClass = $calledClass[1];

      $this->manager = ModuleManager::GetInstance();
      $this->module = $module;
    }

    public final function __methodExists($methodName)
    {
      if (!isset($this->methodList[$methodName])) {
        // Search method is exists in method list or not
        // Load method file if it is exists <Filename Pattern: classname.method>
        $controllerPath = $this->module->getModuleRoot() . 'controller' . DIRECTORY_SEPARATOR . $this->calledClass . '.' . $methodName . '.php';
        if (file_exists($controllerPath)) {
          try {
            $closure = require $controllerPath;
            if (!is_callable($closure)) {
              new ThrowError('IController', '2001', 'The object was not a callable function');
            }
            $this->methodList[$methodName] = $closure;
          } catch (Exception $e) {
            // Error: The object was not callable
            new ThrowError('IController', '2002', 'Cannot load method file, maybe the method file was corrupted');
          }
        } else {
          return false;
        }
      }
      return true;
    }

    public final function __call($method, $arguments)
    {
      if (!$this->__methodExists($method)) {
        // Error: ControllerClosure not found
        new ThrowError('IController', '3001', 'ControllerClosure not found');
      }
      $closure = $this->methodList[$method];

      $closure->bindTo($this, get_class($this));
      return call_user_func_array($closure, $arguments);
    }
  }
}
?>
