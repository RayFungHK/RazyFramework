<?php
class Controller {
	static private $controllers = array();
	static private $loadedControllers = array();
	static private $pointer = array();
	private $callback = null;

	public function __construct($view, $controllerCallback) {
		self::$controllers[$view] = &$this;
		$this->callback = $controllerCallback;
	}

	public function &getCallback() {
		return $this->callback;
	}

	public function exec($args) {
		return call_user_func_array($this->callback, $args);
	}

	static public function SetPointer($controller) {
		self::$pointer = $controller;
	}

	static public function GetPointer() {
		return self::$pointer;
	}

	static public function Reset() {
		self::$loadedControllers = array();
	}

	static public function Load($controllerName) {
		if (isset(self::$controllers[$controllerName]) && !isset(self::$loadedControllers[$controllerName])) {
			self::$loadedControllers[$controllerName] = true;

			$args = func_get_args();
			array_shift($args);

			return self::$controllers[$controllerName]->exec($args);
		}
		return false;
	}
}
?>
