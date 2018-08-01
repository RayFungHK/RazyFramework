<?php
namespace Core
{
  abstract class IController
  {
    protected $declaredClass = '';
    protected $module = null;
    protected $manager = null;
    protected $methodList = array();
    protected $reflection = null;

    public final function __construct(ModulePackage $module)
    {
      $this->reflection = new \ReflectionClass($this);
      $this->declaredClass = $this->reflection->getShortName();
      $this->manager = ModuleManager::GetInstance();
      $this->module = $module;
    }

    public final function getReflection()
    {
      return $this->reflection;
    }

    private final function __methodExists($methodName)
    {
      if (!isset($this->methodList[$methodName])) {
        // Search method is exists in method list or not
        // Load method file if it is exists <Filename Pattern: classname.method>
        $controllerPath = $this->module->getModuleRoot() . 'controller' . DIRECTORY_SEPARATOR . $this->declaredClass . '.' . $methodName . '.php';
        if (file_exists($controllerPath)) {
          try {
            $closure = require $controllerPath;
            if (!is_callable($closure)) {
              new ThrowError('IController', '2001', 'The object was not a function');
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
        new ThrowError('IController', '3001', '[' . $method . '] ControllerClosure not found');
      }
      $closure = $this->methodList[$method];

      $closure->bindTo($this, get_class($this));
      return call_user_func_array($closure, $arguments);
    }

    protected final function getViewPath($rootview = false)
    {
      return ($rootview) ? SYSTEM_ROOT . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->module->getModuleRoot() . 'view' . DIRECTORY_SEPARATOR;
    }

    protected final function getRelatedViewPath($rootview = false)
    {
      return ($rootview) ? URL_BASE . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : URL_BASE . $this->module->getModuleRoot(true) . 'view' . DIRECTORY_SEPARATOR;
    }

    protected final function loadview($filepath, $rootview = false)
    {
      // If there is no extension provided, default as .tpl
      if (!preg_match('/\.[a-z]+$/', $filepath)) {
        $filepath .= '.tpl';
      }

      $root = $this->getViewPath($rootview);
      $tplManager = new TemplateManager($root . $filepath, $this->module->getCode());
      $tplManager->globalAssign(array(
        'view_path' => $root
      ));
      $tplManager->addToQueue();

      return $tplManager;
    }
  }
}
?>
