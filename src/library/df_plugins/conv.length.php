<?php
return function() {
	if (is_array($this) || $this->dataType == 'array' || $this->value instanceof \ArrayAccess) {
		return count($this->value);
	} elseif ($this->dataType == 'string') {
		return strlen($this->value);
	}
	return (isset($this->value)) ? 1 : 0;
}
?>
