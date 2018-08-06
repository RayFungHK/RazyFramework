# Controller Closure Function

When we started our development in a while, we may encounter a problem that there are too many routes or callable method in the controller class, and it may be hard to manage or modify. At this moment, a well managed coding system can speed up and smooth your development.

Razy allows you to extract any method into an individual file so that you do not need to modify your method in a large and boring controller class.

```
<?php
namespace Core
{
  class user extends IController
  {
    public function main()
    {

    }

    public function add()
    {

    }

    public function edit()
    {

    }

    public function delete()
    {

    }

    public function header()
    {

    }

    public function footer()
    {

    }
  }
}
?>
```

From above sample controller class, you can extract ``add``, ``edit``, ``delete``, ``header`` and ``footer`` to their respective files that we call it as ##**closure function file**##, and your ##**controller**## folder in module look like:

```
/controller/user.php
/controller/user.add.php
/controller/user.edit.php
/controller/user.delete.php
/controller/user.header.php
/controller/user.footer.php
```

The file name should follow the pattern ``[controller-class].[controller-method].php``, and it should be a ##**callback-return**## file, such as:

```
return funtion($arg) {
  echo 'Hello world';
};
```

We call the extracted method as ##**closure function**##, or you can call it as ##**anonymous function**## as well. When Razy starts routing or execute the controller method, module controller will try to access the method under the class. If the method is not declared, Razy will load and execute the ##**closure function**## in the controller folder.

In PHP, a method in the class can access another method internally by using ``$this``, but how about the closure function? Don't worry, Razy will bind the ``$this`` as the controller class to ##**closure function**## that you can use ``$this`` to access another method like a class method.  For example:

```
<?php
namespace Core
{
  class user extends IController
  {
    public function main()
    {

    }

    private function removeUser($id)
    {
	// blah blah blah
    }
  }
}
```

And you can call ``$this`` to access ``removeUser`` like:

```
return function() {
  $this->removeUser(1);
};
```

Awesome! Now you can develop your module without waste time to search the method you need to modify in a thousand line file.
