# Configure your module
## Step 1: Create module folder and config file
Razy provides a clean and powerful module system to let you more comfortable to manage your project. Each module folder contains a config file, to control route, callback whitelist, remap and event.

First, we have to create a new folder in Razy module folder, now we create a ``user`` module in this example.

    /webroot/module/user/

Now we have the folder named ``user``, but we still need have to give the module a config file.

    /webroot/module/user/config.php

``config.php`` is a array-return file, contains route, remap, callback whitelist and event setting. By default, the ``/user/`` module route is:

    http://yourwebsite.com/user/

In this example, we will not change any route mapping so that you can ignore the remap parameter, and we will explain it in other tutorials. Following code is the sample of ``user`` module:

```
<?php
return [
  'module_code' => 'user',
  'author' => 'Your name',
  'version' => '1.0.0',
  'route' => array(
    '(:any)' => 'user.main'
  )
];
?>
```

Beware that, the ``module_code`` must unique.

The config file has a parameter named ``route``, uses to control the route mapping. According to the ``'(:any)' => 'user.main'``, ``(:any)`` means it will pass anything to ``user.main`` that no route has matched.

    http://yourwebsite.com/user/example/value

Above path will pass the parameters ``example`` and ``value`` to the ``main`` method in ``user`` controller. As the following code in PHP:

    // $user is an object
    $user->main('example', 'value');

## Step 2: Create controller

Now the config file route has a mapping which is pointing to ``user.main``, the ``user.main`` means ``user`` is the class, and the ``main`` is the method which is under ``user`` class. Thus, we need to create a ``user.php`` controller file with a ``main`` method, and the file path should be:

    /webroot/module/user/controller/user.php

Rule:
1. Each controller file only allows one class and the class name must same as the file name.
2. The class name and file name are case sensitive.
3. It must ##extends## the ``iController`` abstract class.
4. The method which has mapped in route list must be a public method.
5. You can use ``namespace`` to separate module class and Razy class, Razy  built-in class always in ``\Core`` namespace.

For example:

```
<?php
namespace Core
{
  class user extends IController
  {
    public function main()
    {
      echo 'Hello world';
    }
  }
}
?>
```

Congratulations! Now you can try to visit ``http://yourwebsite.com/user/``, and it should display ``Hello world`` on the screen.
