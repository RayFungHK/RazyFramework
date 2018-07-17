# Razy
- Supports PHP5+
- Fast & easy to build a website
- Weight light, structure clean
- Separated module folder, you can clone the module to other Razy framework without any modification
- Cross-module communication
- Easy routing
- Supports CLI mode
- Pluggable controller, better coding management
# Task List
- New CLI Mode (Working in progress)
- FTP library
- HTTP library
- Image Manipulation Library
- Mail library
# Razy Files Structure
```
- .
 - library/
 - module/
  - your_module_name/
   - controller/ (Place your controller here)
   - view/ (Module based view folder)
   - library/ (auto-load library)
   - config.php (Module configuration)
 - view/ (Global view folder)
 - system/
 - material/
```
# Configuration File
Razy will deep scan the module folder, when the module folder contain a **config.php**, Razy will assume it is a module. **config.php** is a array-return file, example:
```
<?php
return [
  // Module code must unique
  'module_code' => 'example',
  // The Module author name
  'author' => 'Ray Fung',
  // The Module version
  'version' => '1.0.0',
  // The remap to change the route path, default `/module_code/`
  'remap' => '/admin/$1', // Put $1 as a module_code
  // Route setting, for example `reroute` is mapped to method `reroute` under class `example`
  'route' => array(
    // (:any)	 Pass all arguments to 'any' route if there is no route was matched
    '(:any)' => 'example.main',
    'reroute' => 'example.reroute',
    'custom' => 'example.custom'
  ),
  // Callable method mapping, for cross-module communication and event trigger
  'callable' => array(
    'method' => 'example.method',
    'onMessage' => 'example.onMessage'
  )
];
?>
```
# Controller Rule
Every module must contain **one** class in **controller** folder, which is named as **module code**. For example if the module code named **user**, you should have:
```
/module/example/controller/user.php
```
The file must a class file, under **Module** namespace, and extends **IController** class:
```
<?php
namespace Module
{
  class user extends \Core\IController
  {
    public function main()
    {
      $this->loadview('main', true);
    }

    public function reroute()
    {
      echo 'Re-Route';
    }

    public function onMessage()
    {
      echo 'onMessage Event';
    }

    public function method()
    {
      return 'Callable Method';
    }
  }
}
?>
```


Also, you can separate any method into other file. Such as there is a **getName** in **user** class, you can create a function-return file named:
```
/module/example/controller/user.getName.php
```
And the file like:
```
<?php
return function($argA) {
  return $this->name;
};
?>
