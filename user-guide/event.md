# Event

Events are a very convenient feature, and Razy also provides a function to you trigger it in any module. You can use the ``event`` parameter in config.php to set different event listeners. The setting is similar to ``callable`` parameter, also provide a ``method`` corresponding to a controller class and controller method in the event list. Such as:

    'event' => 'controller-class.controller.method'

``event`` is different from ``callable``, it is a chainable function, and it will not returns any results from the executed event. You can assume that ##**module**## is like a ##**dom element**##, you can iterate over them and trigger their event.

Here is the sample config.php with event parameter:

```
return [
  'module_code' => 'user',
  'author' => 'Ray Fung',
  'version' => '1.0.0',
  'remap' => '/admin/$1',
  'route' => array(
    '(:any)' => 'user.main'
  ),
  'callable' => array(
    'getUser' => 'user.getUser'
  ),
  'event' => array(
    'onScriptStart' => 'user.onScriptStart'
  )
];
```

Of course, for security reasons, you can't traverse all modules to trigger their events. However, you can access ModuleManager by ``$this->manager`` in your module, and call ``trigger`` to pass the arguments to all modules event listener.

	$this->manager->trigger('onScriptStart', 'Hello', 'world');

In the above code, you will find that the first parameter does not need to provide ``controller-class``, you only need to provide the event name.

Razy event like callback whitelist to pass the parameters from the second to the controller method of each module, but how can you know what module has triggered the event? ModuleManager has ``getTarget()`` function to get the module which is triggered the event so that you can identify what module is triggered your event.
