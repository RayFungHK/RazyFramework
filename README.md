# Razy
- Supports PHP5+
- Fast & easy way to build a website
- Lightweight, clean structure
- Separated module folder, you can clone the module to another Razy framework without any modification
- Cross-module communication
- Easy routing
- Supports CLI mode
- Pluggable controller, better coding management

# Task List
- New CLI Mode (Done)
- Markdown library, Class (Done)
- FTP library, Class
- HTTP library, Class
- Image Manipulation Library, Class
- Mail library, Class
- Logging library, Tarit

# Resource
[Razy User Guide](http://rayfung.hk/Razy)

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
Razy will deep scan the module folder for a flie called **config.php**, Razy will assume it is a module. **config.php** is an array-return file, example:
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
    // (:any)	 Pass all arguments to 'any' route if there is no route matched
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
Every module must contain **one** class in **controller** folder, which is named as **module code**. For example, if the module code is assigned to **user**, you should have a controller file in:
```
/module/example/controller/user.php
```
The file must contain only one class and the class name should be same as the file name, and it must also extend the **IController** class:
```
<?php
class user extends IController
{
  public function main()
  {
    $this->loadview('main');
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
?>
```
Also, you can separate any method into another file. For example, if there is **getName** in **user** class, you can create a function-return file named:
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
```
# CLI Mode
Razy supports CLI, and it can be executed via Windows or Linux command line. Razy assumes script arguments as a routing path, and you can get the script parameters by:
```
$this->manager->getScriptParameters()
```
Razy defines **CLI_MODE** to allow the developer to identify command line calls to the script or or to open by the browser. So we can modify above module sample method **main** to separate CLI Mode and Browser Mode:
```
public function main()
{
  if (CLI_MODE) {
    echo 'Welcome to CLI mode';
    foreach ($this->manager->getScriptParameters() as $param => $value) {
      echo "\n$param:" . str_repeat(' ', 12 - strlen($param)) . $value;
    }
  } else {
    $this->loadview('main');
  }
}
```
Now, let's call the script via command line like:
```
php index.php admin example -v 1.0.0 --message "Hello World"
```
Then, it results:
```
Welcome to CLI mode
v:           1.0.0
message:     Hello World
```

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
Every module must contain **one** class in **controller** folder, which is named as **module code**. For example, if the module code assigned **user**, you should have a controller file in:
```
/module/example/controller/user.php
```
The file must contain only one class and the class name should be same as the file name, also it must extends the **IController** class:
```
<?php
class user extends IController
{
  public function main()
  {
    $this->loadview('main');
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
?>
```
Also, you can separate any method into another file. Such as there is a **getName** in **user** class, you can create a function-return file named:
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
```
# CLI Mode
Razy supports CLI, and it can execute via Windows or Linux command line. Razy assume script arguments as a routing path, and you can get the script parameters by:
```
$this->manager->getScriptParameters()
```
Razy defined **CLI_MODE** to let developer identify command line calls the script or open by the browser. So we can modify above module sample method **main** to separate CLI Mode and Browser Mode:
```
public function main()
{
  if (CLI_MODE) {
    echo 'Welcome to CLI mode';
    foreach ($this->manager->getScriptParameters() as $param => $value) {
      echo "\n$param:" . str_repeat(' ', 12 - strlen($param)) . $value;
    }
  } else {
    $this->loadview('main');
  }
}
```
Now, let's call the script via command line like:
```
php index.php admin example -v 1.0.0 --message "Hello World"
```
Then, it results:
```
Welcome to CLI mode
v:           1.0.0
message:     Hello World
```
