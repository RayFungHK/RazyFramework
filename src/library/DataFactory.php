<?php
namespace Core
{
  class DataFactory extends \ArrayObject
  {
		static private $preventWarning = true;
		static private $functionMapping = array();
    private $convertor = null;
    private $reflection = null;
    private $pointer = null;
    private $iterator = null;

		static public function DisableWarning()
		{
      self::$preventWarning = true;
		}

		static public function EnableWarning()
		{
      self::$preventWarning = false;
		}

		public function __construct($data = array())
		{
			if (is_null($data)) {
				$data = array();
			} elseif (!is_array($data) && !array_key_exists('ArrayAccess', class_implements($data))) {
				$data = array($data);
			}
      $this->convertor = new DataConvertor();
      $this->iterator = $this->getIterator();
      parent::__construct($data);
		}

		public function &offsetGet($index)
		{
      // Prevent display undefine warning
      if (!self::$preventWarning || $this->offsetExists($index)) {
        return $this->iterator[$index];
      }
      $data = null;
      return $data;
		}

		public function __call($funcName, $args)
		{
			$funcName = trim($funcName);
			if (!array_key_exists($funcName, self::$functionMapping)) {
				self::$functionMapping[$funcName] = null;
				$pluginFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'df_plugins' . DIRECTORY_SEPARATOR . 'factory.' . $funcName . '.php';
				if (file_exists($pluginFile)) {
					$callback = require $pluginFile;
					if (is_callable($callback)) {
						self::$functionMapping[$funcName] = $callback;
					}
				}
			}

			if (!isset(self::$functionMapping[$funcName])) {
				new ThrowError('DataFactory', '1001', 'Cannot load [' . $funcName . '] factory function.');
			}
			return call_user_func_array(self::$functionMapping[$funcName]->bindTo($this), $args);
		}

		public function __invoke($index)
		{
      $this->pointer = $index;
      return $this->convertor->setEngine($this[$index], $this->reflection()->bindTo($this));
		}

    private function reflection()
    {
      if (!$this->reflection) {
        $this->reflection = function($value) {
          $this[$this->pointer] = $value;
        };
      }
      return $this->reflection;
    }
	}
}
?>
