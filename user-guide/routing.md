# Routing
When a user visits your website, Razy will follow the requested path and search the matched route, to pass the request to the mapped controller method.

By default, your module routing URI should be:

    http://example.com/[module-code]/[route]/[arguments]

Under security factors, only the controller method in the route list should declare as a public method. Razy provides a friendly route setting by following the rules:

    '[route-mapping]' => '[controller-class].[controller-method]'

You can add some route setting in the config file so that you can specify every route to the different controller method, even different controller class in the module.

```
<?php
return [
  'module_code' => 'user',
  'author' => 'Your name',
  'version' => '1.0.0',
  'route' => array(
    'add' => 'user.addAndEditUser',
    'edit' => 'user.addAndEditUser',
    'delete' => 'user.deleteUser',
    'report' => 'report.user'
  )
];
?>
```

As above example config file, it has ##four## route setting. Razy allows you to map the different ##route## to the same controller method, even a different controller class. Like the ``'add' => 'user.addAndEditUser'``, ``add`` is **##route-mapping##**, ``user`` is **##controller-class##** and ``addAndEditUser`` is **##controller-method##**, thus ``http://example.com/add/argumentA`` will route to ``user.addAndEditUser`` with parameter ``argumentA``.

It is very straightforward, only followed by 3 step, ``module_code``, ``route`` and ``method``. You don't need to use the regular expression to match the routing by the path, Razy will divide the URI path and pass to the specified method as arguments.

For example:

    http://example.com/user/report/user-activities/2018/08/01

Razy will extract the path variable from the URI. For example, we assume Razy framework located in ``webroot`` so that ``/user/report/user-activities/2018/08/01`` is the path variable which passes to Razy.

First, Razy finds out which module mapping path matched with the path variable. Of course, we assume all module has no ``remap`` setting so that all module is using the default mapping path ``/[module-code]``. Next, the second path variable ``report`` to get the ``method`` from the routing list that you set up in the config file.

Finally, because ``user`` and ``report`` defined as ``module-code`` and ``route`` so that the remaining path variable `/user-activities/2018/08/01` will pass to method ``report.user`` as arguments like:

    $report->user('user-activities', '2018', '08', '01');

Also, you can set up a route as (:any) to accepts other unmatched ``path variable``. For example, if there is only ``'(:any)' => 'user.acceptAllPath'`` in routing list, it means Razy will pass the path variable ``report/user-activities/2018/08/01`` to ``user.acceptAllPath`` directly such as:

    $user->acceptAllPath('report', 'user-activities', '2018', '08', '01');

Beware that, because there is no route matched so that the route ``report`` still pass to the controller method.
