<?php
return function($array = array(), $definekey = false) {
	if (is_array($array) || is_a($array, '\Core\DataFactory')) {
		foreach ($array as $index => $value) {
			$key = ($definekey) ? $value : $index;
			if (!$definekey) {
				$this[$key] = $value;
			} elseif (!array_key_exists($key, $this)) {
				$this[$key] = null;
			}
		}
	}
	return $this;
}
?>
