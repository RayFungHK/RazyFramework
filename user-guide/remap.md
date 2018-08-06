# Remap

Sometimes, you don't want the module root path to be preset using ``/[module-code]`` that suppose it should be ``/user`` by default. So can we change the module root path at this moment? Yes! You can change it in the config file, with the parameter ``remap``.

Let's take a look on sample config file:

```
<?php
return [
  'module_code' => 'user',
  'author' => 'Ray Fung',
  'version' => '1.0.0',
  'remap' => '/admin/user',
  'route' => array(
    '(:any)' => 'user.main'
  )
];
?>
```

Did you see that ``remap`` parameter with a ``path`` value? Changing the module root path with routing list is simple. When the path variable starts from ``/admin/user/``, like ``/admin/user/add/1``, that ``add`` becomes the route and pass ``1`` to the controller method as an argument.

Besides that, regarding the ``remap`` parameter, you can use ``$1`` as the ``module-code``, like ``/admin/$1``.

Noted that, Razy will add a ``/`` in the end of remap path.

## Routing and Remap workflow

1) Pass the path variable to Razy

    /module/root/path/route/argument

2) Razy starts matching the path variable with all module, by following module list

```
  moduleA     /moduleA/
  moduleB     /custom/module/    (Remapped)
  moduleC     /it/is/moduleC/    (Remapped with /it/is/$1)
  moduleD     /module/root/path/
```

3) Reorder the list by longest path

```
  moduleC     /it/is/moduleC/
  moduleD     /module/root/path/
  moduleB     /custom/module/
  moduleA     /moduleA/
```

4) Matching the path variable one by one

```
  (fail)      moduleC     /it/is/moduleC/
  (matched)   moduleD     /module/root/path/
              moduleB     /custom/module/
              moduleA     /moduleA/
```

5) Get the arguments after ``/module/root/path/`` path variable

    // Path vaiable: route/argument
    $args = ['route', 'argument'];

6) Matching the first argument with routing list

```
  (fail)          'beforeany' => 'moduleD.beforeany'
  (reserved)      '(:any)' => 'moduleD.hubs'         -------
  (fail)          'default' => 'moduleD.default'           |
  (matched)       'route' => 'moduleD.route'               |
                  'otherclass' => 'otherclass.hubs'        |
                  '(:any)' => 'moduleD.hubs'        <-------
```

7) Routing matched ``'route' => 'moduleD.route'``, remove ``route`` from the arguments

    $args = ['argument'];

8) Pass the argument to ``route`` controller method under ``moduleD`` controller class

    $moduleD->route('argument')
