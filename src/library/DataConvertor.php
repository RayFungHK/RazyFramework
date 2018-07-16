<?php
namespace Core
{
  class DataConvertor
  {
		static private $functionMapping = array();
    protected $object = '';
    protected $reflection = '';

    public function setEngine($value, $reflection)
    {
      // Create an object for closure binding
  		$this->object = (object) [
        'value' => $value,
        'dataType' => strtolower(gettype($value)),
      ];
      $this->reflection = $reflection;

      return $this;
    }

  	public function __call($funcName, $args)
    {
			$funcName = trim($funcName);
			if (!array_key_exists($funcName, self::$functionMapping)) {
				$functionMapping[$funcName] = null;
				$pluginFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'df_plugins' . DIRECTORY_SEPARATOR . 'conv.' . $funcName . '.php';
				if (file_exists($pluginFile)) {
					$callback = require $pluginFile;
					if (is_callable($callback)) {
						$functionMapping[$funcName] = $callback;
					}
				}
			}

			if (!isset($functionMapping[$funcName])) {
				new ThrowError('DataFactory', '1001', 'Cannot load [' . $funcName . '] convertor function.');
			}

      // Bind convertor object to closure function
			$result = call_user_func_array($functionMapping[$funcName]->bindTo($this->object), $args);

      // Call DataFactory reflection function to change the value
      $dd = call_user_func($this->reflection, $this->object->value);

      return $result;
  	}
  }
}
?>
