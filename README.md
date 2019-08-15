# Razy
- Supports PHP 7
- Fast & easy way to build a website
- Lightweight, clean structure
- Separated module folder, you can clone the module to another RazyFramework framework without any modification
- Cross-module communication
- Easy routing
- Supports CLI mode
- Pluggable controller, better coding management

# v0.3.0 Multiple site and Protector
The RazyFramework major update, I have rewrite most of the exisiting class to provide higher performance and security.
- **Rearrange** the same namespace class file into different subfolder.
- More **intuitive** and **clear** workflow of the controller and it's event.
- The new **Wrapper class** provides **private method tunnel** between two classes for closure communication. Controller **no longer available to access** Package or Manager sensitive method that aer public before.
- Rewrite the structure of **Template Engine**, higher performance and more functions, such as paramater **support array value**, **bookmark tag** for paramater tag wrapping, **basic comparision** for paramater tag.
- Template Engine support **Resursion Block**! Generate your nested block easier!
- **Where-Syntax** and **TableJoin-Syntax** on fire! Support **JSON function**, allow to **override** the table name into a custom statement as a sub-query and **chainable SQL statement setting**.
- Support **multiple site**! You can set the different domain mapping to specified **distrubution**, also allows **alias**! Also it allows setup **multiple distributions** by different begining path!
- Now you can set the **method routing, routing prefix, API, event and property** in controller class.
- **RegexHelper** provides many useful function to help you extract the **nested parentheses structure**, content extractor and **matching & replace**.
- **ErrorHandler** class is a new throwable class to display PHP error message, you can customize the error page in the **error_handling** folder.
- New controller event: **\_\_onAfterRoute, \_\_onRoute, \_\_onAPICall, \_\_onEventTrigger, \_\_onVersionControl**
- Support module **version comperision** now!
- **DocBlocks**!
- You can **rewrite** the URL Query before start the method routing in Controller method \_\_onBeforeRoute!
- **_TOO MANY UPDATES LAZY TO LIST HERE!_**

# What is the big change on next update?
- **Package Manager**:
To install/update Module Package automatically via console mode. You can add repository (default RazyFramework official) to get more module package.

# Task List
- Preload and Ready event (Done)
- New CLI Mode (Done)
- Markdown library, Class (Done)
- Rewrite Database Class (Done)
- Rewrite ThrowError Class (Done)
- FTP library, Class
- HTTP library, Class
- Image Manipulation Library, Class
- Mail library, Class
- Logging library, Tarit

# Resource
[RazyFramework User Guide](http://rayfung.hk/Razy)

# RazyFramework Files Structure
```
- .
 - library/
 - config/
 - sites/
   - your_site_folder/
     - module_foler
       - error_handling/
       - controller/ (Place your controller here)
       - view/ (Module based view folder)
       - library/ (auto-load library)
       - package.php (Module package configuration)
    - dist.php (Distribution configuration)
 - system/
 - sites.inc.php (The multiple site configuration)
```
# Distribution and Module Package
RazyFramework will scan the distribution folder with the **dist.php** config file which is configurate in the sites.inc.php file if the requested domain is matched. Afterthat it will scan all the available module package in distribution folder which is contains the **package.php** file.

```
<?php
return [
  // The module code
	'module_code' => 'example',

  // The author name and the email
	'author'      => 'Ray Fung <hello@rayfung.hk>',

  // The module version
	'version'     => '1.0.0',

  // The required module and version
	'require' => [
		'module_a'  => '>=1.0.0',
		'module_b' => '>=1.5.0',
	],
];
?>
```

# Controller Rule
Every module must contain only **one** class in **controller** folder, which is named as **module code**. For example, if the module code is assigned to **user**, you should have a controller file in:

```
/your_site_folder/user/controller/user.php
```

The file must contain only one class and the class name should be same as the file name, and it must also extend the **Controller** class:

```
<?php
use RazyFramework\Modular\Controller;

class user extends Controller
{
  public function main()
  {
    $source = $this->loader->view('main');

    echo $source->output();

    return true;
  }

  public function __onInit()
  {
    $this->package->addRoute([
      '/' => 'main',
    ])->addAPI([
      'helloworld' => 'method',
    ])->addEvent([
      'onMessage' => 'onMessage',
    ]);
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
