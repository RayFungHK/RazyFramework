<?php
return function() {
	$this->value = floatval($this->value);
	return $this->value;
}
?>
