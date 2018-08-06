# Callback Whitelist
Razy is not allowed to access the module controller method directly, and you need to set up the callback whitelist in the config file by ``callable`` parameter.

Why do you need to set up the callback whitelist?

1. Reduce conflicts between modules.
2. Restrict developers from accessing your module source code
3. Make your module more independent.
4. You can write all the module API documentation to outsource the project without having to expose the source code of other modules.
5. Safety as a prerequisite, Razy only allows one instance of a controller class, so that you cannot access the controller directly.
6. You don't need to review the controller class and its methods by open the source anymore, review the callback whitelist in the module config file instead.

Callback whitelist setting rule is the same as the routing list, and it is also a ``method`` corresponding to a controller class and controller method. Such as:

    'method' => 'controller-class.controller.method'

## How it works?

First, we add a method in the callback whitelist, such as the following sample config file:

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
  )
];
```

Now we have a module method called ``getUser``, which has mapped to controller method ``getUser`` under controller class ``user``. Then we need to add a public method in controller class ``user``:

```
namespace Core
{
  class user extends IController
  {
    // blah blah blah...

    public function getUser($user_id)
    {
      if ($user == 1) {
        return 'Peter';
      } elseif ($user == 2) {
        return 'Tom';
      }
      return false;
    }

    // blah blah blah...
  }
}
```

So, how can we call the ``getUser`` in other modules? You can use ``$this->manager`` to access ``ModulerManager`` and using ``execute()`` to call the method. The first argument of ``execute`` is a function name consisting of ``module-code`` and ``method-name``, with ``.``. In this example, the function name should be ``user.getUser``. Then Razy will pass another argument to ``user.getUser``, so if we do:

	$this->manager->execute('user.getUser', 1);

The second argument ``1`` will pass to ``user.getUser`` like ``$user->getUser(1)``, that suppose to returns ``Peter`` at this moment.
